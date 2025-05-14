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
        
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'scanner') {
            return redirect()->route('scanner.dashboard');
        }
        
        return redirect()->route('welcome');
    }

    public function adminDashboard(): View
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        
        return view('dashboard.admin');
    }

    public function scannerDashboard(): View
    {
        if (Auth::user()->role !== 'scanner') {
            abort(403, 'Unauthorized action.');
        }
        
        return view('dashboard.scanner');
    }
} 