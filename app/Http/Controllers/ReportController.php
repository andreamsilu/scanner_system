<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ScanLog;
use App\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(): View
    {
        // Get overall statistics
        $stats = [
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'active')->count(),
            'total_attendees' => Attendee::count(),
            'total_scans' => ScanLog::count(),
            'successful_scans' => ScanLog::whereNotNull('attendee_id')->count(),
            'failed_scans' => ScanLog::whereNull('attendee_id')->count(),
        ];

        // Get recent events with attendance
        $recent_events = Event::withCount([
            'attendees',
            'scanLogs'
        ])
        ->latest()
        ->take(5)
        ->get();

        // Get daily scan trends
        $daily_scans = ScanLog::select(
            DB::raw('DATE(scanned_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN attendee_id IS NOT NULL THEN 1 ELSE 0 END) as successful'),
            DB::raw('SUM(CASE WHEN attendee_id IS NULL THEN 1 ELSE 0 END) as failed')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->take(30)
        ->get();

        return view('reports.index', compact('stats', 'recent_events', 'daily_scans'));
    }

    public function attendance(Request $request): View
    {
        $query = Event::withCount([
            'attendees',
            'scanLogs'
        ]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $events = $query->latest()->paginate(10);

        return view('reports.attendance', compact('events'));
    }

    public function revenue(Request $request): View
    {
        $query = Event::withSum('tickets', 'price')
            ->withCount([
                'tickets',
                'scanLogs'
            ]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $events = $query->latest()->paginate(10);

        return view('reports.revenue', compact('events'));
    }
} 