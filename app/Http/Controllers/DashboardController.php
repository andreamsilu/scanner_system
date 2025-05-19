<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('scanner')) {
            return redirect()->route('scanner.dashboard');
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function adminDashboard(): View
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Get real-time statistics
        $stats = [
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'active')->count(),
            'total_attendees' => Attendee::count(),
            'total_scans' => ScanLog::count(),
            'today_scans' => ScanLog::whereDate('created_at', today())->count(),
            'active_scanners' => User::whereHas('roles', function($query) {
                $query->where('name', 'scanner');
            })->where('is_active', true)->count(),
        ];

        // Get recent scan activity
        $recent_activity = ScanLog::with(['ticket.attendee', 'ticket.event', 'scanner'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function($scan) {
                return [
                    'time' => $scan->created_at->diffForHumans(),
                    'event' => $scan->ticket->event->name ?? 'N/A',
                    'scanner' => $scan->scanner->name ?? 'System',
                    'attendee' => $scan->ticket->attendee->name ?? 'Invalid Ticket',
                    'status' => $scan->status,
                    'status_class' => $scan->status === 'success' ? 'success' : 
                                    ($scan->status === 'duplicate' ? 'warning' : 'danger')
                ];
            });
            

        // Get upcoming events
        $upcoming_events = Event::where('status', 'active')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(5)
            ->get();

        // Get scanner performance
        $scanner_performance = User::whereHas('roles', function($query) {
                $query->where('name', 'scanner');
            })
            ->withCount(['scanLogs' => function($query) {
                $query->whereDate('created_at', today());
            }])
            ->orderByDesc('scan_logs_count')
            ->take(5)
            ->get();

        return view('dashboard.admin', compact(
            'stats',
            'recent_activity',
            'upcoming_events',
            'scanner_performance'
        ));
    }

    public function scannerDashboard(): View
    {
        if (!Auth::user()->hasRole('scanner')) {
            abort(403, 'Unauthorized action.');
        }
        
        $scanner = Auth::user();
        
        // Get scanner's statistics
        $stats = [
            'today_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', today())
                ->count(),
            'valid_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', today())
                ->where('status', 'success')
                ->count(),
            'invalid_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', today())
                ->whereIn('status', ['invalid', 'duplicate'])
                ->count(),
        ];

        // Get active events with their attendees
        $active_events = Event::where('status', 'active')
            ->where('start_date', '>=', now())
            ->with(['attendees' => function($query) {
                $query->with(['ticket' => function($q) {
                    $q->select('id', 'attendee_id', 'status');
                }])
                ->select('id', 'name', 'email', 'event_id', 'token');
            }])
            ->get();

        // Get recent activity
        $recent_activity = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->where('scanner_id', $scanner->id)
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.scanner', compact('stats', 'active_events', 'recent_activity'));
    }
} 