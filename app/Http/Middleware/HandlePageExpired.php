<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandlePageExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 419) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Page Expired'], 419);
            }

            // If we're already on the login page, just show the error
            if ($request->routeIs('login')) {
                return back()->with('error', 'Your session has expired. Please try logging in again.');
            }

            // For other pages, redirect to login with the expired message
            return redirect()->route('login')->with('page_expired', true);
        }

        return $response;
    }
} 