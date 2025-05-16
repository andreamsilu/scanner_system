<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
                $scannerRoleId = DB::table('roles')->where('name', 'scanner')->value('id');
                $userRoles = DB::table('user_role')->where('user_id', $user->id)->pluck('role_id')->toArray();
                if (in_array($adminRoleId, $userRoles)) {
                    return redirect()->intended(route('admin.dashboard'));
                } elseif (in_array($scannerRoleId, $userRoles)) {
                    return redirect()->intended(route('scanner.dashboard'));
                } else {
                    abort(403, 'Unauthorized action.');
                }
            }
        }

        \Log::info('User is not authenticated, proceeding');
        return $next($request);
    }
} 