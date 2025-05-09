<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendeeController extends Controller
{
    public function index(): View
    {
        $attendees = Attendee::with('event')->latest()->paginate(15);
        return view('attendees.index', compact('attendees'));
    }

    public function create(): View
    {
        $events = Event::where('status', 'active')->get();
        return view('attendees.create', compact('events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'ticket_type' => 'required|string|max:50',
            'status' => 'required|in:registered,pending,cancelled'
        ]);

        $validated['registration_date'] = now();

        $attendee = Attendee::create($validated);

        return redirect()->route('attendees.show', $attendee)
            ->with('success', 'Attendee registered successfully.');
    }

    public function show(Attendee $attendee): View
    {
        $attendee->load(['event', 'eventCards', 'scanLogs']);
        return view('attendees.show', compact('attendee'));
    }

    public function edit(Attendee $attendee): View
    {
        $events = Event::where('status', 'active')->get();
        return view('attendees.edit', compact('attendee', 'events'));
    }

    public function update(Request $request, Attendee $attendee): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'ticket_type' => 'required|string|max:50',
            'status' => 'required|in:registered,pending,cancelled'
        ]);

        $attendee->update($validated);

        return redirect()->route('attendees.show', $attendee)
            ->with('success', 'Attendee updated successfully.');
    }

    public function destroy(Attendee $attendee): RedirectResponse
    {
        $attendee->delete();

        return redirect()->route('attendees.index')
            ->with('success', 'Attendee deleted successfully.');
    }
} 