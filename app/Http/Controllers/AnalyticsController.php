<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Daily scan trends
        $dailyScans = ScanLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->take(30)
        ->get();

        // Hourly scan distribution
        $hourlyScans = ScanLog::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('count(*) as total')
        )
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

        // Scanner activity
        $scannerActivity = ScanLog::select(
            'scanned_by',
            DB::raw('count(*) as total_scans')
        )
        ->groupBy('scanned_by')
        ->orderBy('total_scans', 'desc')
        ->take(10)
        ->get();

        // Device statistics
        $deviceStats = ScanLog::select(
            DB::raw('SUBSTRING_INDEX(device_info, " ", 1) as device'),
            DB::raw('count(*) as total')
        )
        ->groupBy('device')
        ->orderBy('total', 'desc')
        ->take(5)
        ->get();

        // Overall statistics
        $stats = [
            'total_attendees' => Attendee::count(),
            'scanned_attendees' => Attendee::where('scanned', true)->count(),
            'total_scans' => ScanLog::count(),
            'unique_scanners' => ScanLog::distinct('scanned_by')->count('scanned_by'),
            'avg_scans_per_day' => round(ScanLog::whereDate('created_at', '>=', now()->subDays(30))
                ->count() / 30, 1),
            'scan_rate' => round(Attendee::where('scanned', true)->count() / Attendee::count() * 100, 1)
        ];

        return view('analytics.index', compact(
            'dailyScans',
            'hourlyScans',
            'scannerActivity',
            'deviceStats',
            'stats'
        ));
    }
} 