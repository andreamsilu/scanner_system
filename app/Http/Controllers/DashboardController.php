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

    public function admin()
    {
        // Get event statistics
        $stats = [
            'total_events' => Event::count(),
            'completed_events' => Event::where('end_date', '<', now())->count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'active_scanners' => User::whereHas('roles', function($query) {
                    $query->where('slug', 'scanner');
                })
                ->whereHas('events', function ($query) {
                    $query->where('start_date', '<=', now())
                          ->where('end_date', '>=', now());
                })
                ->count(),
        ];

        // Get recent activity (last 10 scan logs)
        $recent_activity = ScanLog::with(['event', 'scanner', 'attendee'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'time' => $log->created_at->format('M d, H:i'),
                    'event' => $log->event->name,
                    'scanner' => $log->scanner->name,
                    'attendee' => $log->attendee->name,
                    'status' => $log->status,
                    'status_class' => $log->status === 'success' ? 'success' : 'danger'
                ];
            });

        // Get upcoming events (next 5)
        $upcoming_events = Event::where('start_date', '>', now())
            ->orderBy('start_date')
            ->take(5)
            ->get();

        // Get scanner performance for today
        $scanner_performance = User::role('scanner')
            ->withCount(['scanLogs' => function ($query) {
                $query->whereDate('created_at', today());
            }])
            ->having('scan_logs_count', '>', 0)
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

        // Get assigned events with their attendees
        $assigned_events = $scanner->events()
            ->where('status', 'active')
            ->where(function($query) {
                $query->where('start_date', '>=', now())
                    ->orWhere('end_date', '>=', now());
            })
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

        return view('dashboard.scanner', compact('stats', 'assigned_events', 'recent_activity'));
    }
} 