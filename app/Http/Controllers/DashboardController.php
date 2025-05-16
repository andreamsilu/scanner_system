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
        
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $scannerRoleId = DB::table('roles')->where('name', 'scanner')->value('id');
        $userRoles = DB::table('user_role')->where('user_id', $user->id)->pluck('role_id')->toArray();
        if (in_array($adminRoleId, $userRoles)) {
            return redirect()->route('admin.dashboard');
        } elseif (in_array($scannerRoleId, $userRoles)) {
            return redirect()->route('scanner.dashboard');
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function adminDashboard(): View
    {
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $userRoles = DB::table('user_role')->where('user_id', Auth::id())->pluck('role_id')->toArray();
        if (!in_array($adminRoleId, $userRoles)) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('dashboard.admin');
    }

    public function scannerDashboard(): View
    {
        $scannerRoleId = DB::table('roles')->where('name', 'scanner')->value('id');
        $userRoles = DB::table('user_role')->where('user_id', Auth::id())->pluck('role_id')->toArray();
        if (!in_array($scannerRoleId, $userRoles)) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('dashboard.scanner');
    }
} 