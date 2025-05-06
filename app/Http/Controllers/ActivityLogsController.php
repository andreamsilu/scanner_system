<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by activity type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->latest()->paginate(15);

        // Get activity statistics
        $stats = [
            'total_activities' => ActivityLog::count(),
            'today_activities' => ActivityLog::whereDate('created_at', today())->count(),
            'unique_users' => ActivityLog::distinct('user_id')->count('user_id'),
            'avg_activities_per_day' => round(ActivityLog::whereDate('created_at', '>=', now()->subDays(30))
                ->count() / 30, 1)
        ];

        return view('activity-logs.index', compact('logs', 'stats'));
    }

    public function export(Request $request)
    {
        $query = ActivityLog::with('user');

        // Apply the same filters as index
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->get();

        $csv = \League\Csv\Writer::createFromString('');
        $csv->insertOne(['User', 'Type', 'Description', 'IP Address', 'User Agent', 'Created At']);

        foreach ($logs as $log) {
            $csv->insertOne([
                $log->user ? $log->user->name : 'System',
                $log->type,
                $log->description,
                $log->ip_address,
                $log->user_agent,
                $log->created_at->format('Y-m-d H:i:s')
            ]);
        }

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity-logs.csv"'
        ]);
    }
} 