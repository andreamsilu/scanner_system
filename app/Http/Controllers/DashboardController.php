<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use App\Models\User;
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
        
        return view('dashboard.admin');
    }

    public function scannerDashboard(): View
    {
        if (!Auth::user()->hasRole('scanner')) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('dashboard.scanner');
    }
} 