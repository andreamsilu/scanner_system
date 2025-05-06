<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_attendees' => Attendee::count(),
            'scanned_attendees' => Attendee::where('scanned', true)->count(),
            'total_scans' => ScanLog::count(),
            'total_users' => User::count(),
        ];

        $recentScans = ScanLog::with('attendee')
            ->latest()
            ->take(5)
            ->get();

        $dailyScans = ScanLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->take(7)
        ->get();

        return view('dashboard.index', compact('stats', 'recentScans', 'dailyScans'));
    }
} 