<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = ActivityLog::with('causer')
            ->latest()
            ->paginate(20);

        $users = User::all();

        return view('activity-logs.index', compact('logs', 'users'));
    }

    public function show(ActivityLog $activityLog): View
    {
        return view('activity-logs.show', compact('activityLog'));
    }
} 