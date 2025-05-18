<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $userRoles = $request->user()->roles->pluck('slug')->toArray();
        
        // Check if user has any of the required roles
        if (!array_intersect($roles, $userRoles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Insufficient permissions.'], 403);
            }
            
            // Redirect based on user's highest role
            if (in_array('admin', $userRoles)) {
                return redirect()->route('dashboard')->with('error', 'You do not have permission to access this resource.');
            } elseif (in_array('scanner', $userRoles)) {
                return redirect()->route('scanner.dashboard')->with('error', 'You do not have permission to access this resource.');
            }
            
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
} 