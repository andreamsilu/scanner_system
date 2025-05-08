<?php

namespace App\Http\Controllers;

use App\Domain\ActivityLog\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = ActivityLog::with('causer')
            ->latest()
            ->paginate(20);

        return view('activity-logs.index', compact('logs'));
    }

    public function show(ActivityLog $activityLog): View
    {
        return view('activity-logs.show', compact('activityLog'));
    }
} 