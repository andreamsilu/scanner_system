<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AttendeeRegistrationController extends Controller
{
    public function index()
    {
        return view('attendees.register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:attendees',
            'phone' => 'required|string|max:20',
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        $attendee = Attendee::create($validated);

        return redirect()->route('event-card', $attendee)
            ->with('success', 'Registration successful! Your event card is ready.');
    }
} 