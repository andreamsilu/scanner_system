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
        $recent_activity = ScanLog::with(['ticket.event', 'ticket.attendee', 'scanner'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'time' => $log->created_at->format('M d, H:i'),
                    'event' => $log->ticket?->event?->name ?? 'Unknown Event',
                    'scanner' => $log->scanner?->name ?? 'Unknown Scanner',
                    'attendee' => $log->ticket?->attendee?->name ?? 'Unknown Attendee',
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
        $scanner_performance = User::whereHas('roles', function($query) {
                $query->where('slug', 'scanner');
            })
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
            'total_events' => $scanner->events()->count()
        ];

        // Get assigned events without any checks
        $assigned_events = $scanner->events()
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($event) {
                // Ensure dates are Carbon instances
                if (is_string($event->start_date)) {
                    $event->start_date = \Carbon\Carbon::parse($event->start_date);
                }
                if (is_string($event->end_date)) {
                    $event->end_date = \Carbon\Carbon::parse($event->end_date);
                }
                if (is_string($event->pivot->assigned_at)) {
                    $event->pivot->assigned_at = \Carbon\Carbon::parse($event->pivot->assigned_at);
                }

                // Calculate scan statistics for each event
                $event->scan_stats = [
                    'total_attendees' => $event->attendees->count(),
                    'scanned_count' => $event->attendees->filter(function ($attendee) {
                        return $attendee->ticket && $attendee->ticket->status === 'used';
                    })->count(),
                    'pending_count' => $event->attendees->filter(function ($attendee) {
                        return !$attendee->ticket || $attendee->ticket->status !== 'used';
                    })->count()
                ];
                return $event;
            });

        // Get recent activity
        $recent_activity = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->where('scanner_id', $scanner->id)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($scan) {
                return [
                    'time' => $scan->created_at->diffForHumans(),
                    'event' => $scan->ticket?->event?->name ?? 'N/A',
                    'attendee' => $scan->ticket?->attendee?->name ?? 'Invalid Ticket',
                    'status' => $scan->status,
                    'status_class' => $scan->status === 'success' ? 'success' : 
                        ($scan->status === 'duplicate' ? 'warning' : 'danger')
                ];
            });

        return view('dashboard.scanner', compact('stats', 'assigned_events', 'recent_activity'));
    }
} 