<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        Log::info('Login attempt', [
            'email' => $credentials['email'],
            'ip' => $request->ip()
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            $request->session()->regenerate();

            if ($user->hasRole('admin')) {
                return redirect()->intended(route('admin.dashboard'));
            } elseif ($user->hasRole('scanner')) {
                return redirect()->intended(route('scanner.dashboard'));
            } else {
                abort(403, 'Unauthorized action.');
            }
        }

        Log::warning('Login failed', [
            'email' => $credentials['email'],
            'ip' => $request->ip()
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
} 