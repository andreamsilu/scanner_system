<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScanLogManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = ScanLog::with('attendee');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('attendee', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('scanned_by', 'like', "%{$search}%");
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->latest()->paginate(15);

        // Get scan statistics
        $stats = [
            'total_scans' => ScanLog::count(),
            'today_scans' => ScanLog::whereDate('created_at', today())->count(),
            'unique_scanners' => ScanLog::distinct('scanned_by')->count('scanned_by'),
            'avg_scans_per_day' => round(ScanLog::whereDate('created_at', '>=', now()->subDays(30))
                ->count() / 30, 1)
        ];

        return view('scan-logs.index', compact('logs', 'stats'));
    }

    public function export(Request $request)
    {
        $query = ScanLog::with('attendee');

        // Apply the same filters as index
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('attendee', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('scanned_by', 'like', "%{$search}%");
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->get();

        $csv = \League\Csv\Writer::createFromString('');
        $csv->insertOne(['Attendee', 'Scanned By', 'Device Info', 'Scanned At']);

        foreach ($logs as $log) {
            $csv->insertOne([
                $log->attendee->name,
                $log->scanned_by,
                $log->device_info,
                $log->created_at->format('Y-m-d H:i:s')
            ]);
        }

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="scan-logs.csv"'
        ]);
    }
} 