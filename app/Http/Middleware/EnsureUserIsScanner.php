<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Scanner;

class EnsureUserIsScanner
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isScanner()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Scanner access required.'], 403);
            }
            return redirect()->route('login')->with('error', 'You must be a scanner to access this area.');
        }

        // Ensure the user has an associated scanner record
        if (!$request->user()->scanner) {
            Scanner::createFromUser($request->user());
        }

        return $next($request);
    }
} 