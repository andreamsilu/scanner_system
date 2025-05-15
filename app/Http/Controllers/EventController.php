<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Scanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::withCount(['attendees', 'tickets'])
            ->latest()
            ->paginate(10);

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        return view('events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['draft', 'active', 'cancelled'])],
        ]);

        $event = Event::create($validated);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Event created successfully.');
    }

    public function show(Event $event): View
    {
        $event->load(['attendees', 'tickets', 'scanners']);
        $scannerUsers = \App\Models\User::role('scanner')->get();
        return view('events.show', compact('event', 'scannerUsers'));
    }

    public function edit(Event $event): View
    {
        return view('events.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'status' => ['required', Rule::in(['draft', 'active', 'cancelled'])],
        ]);

        $event->update($validated);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        DB::transaction(function () use ($event) {
            $event->tickets()->delete();
            $event->attendees()->detach();
            $event->delete();
        });

        return redirect()
            ->route('events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function attendees(Event $event): View
    {
        $attendees = $event->attendees()
            ->with('ticket')
            ->latest()
            ->paginate(20);

        return view('events.attendees', compact('event', 'attendees'));
    }

    public function tickets(Event $event): View
    {
        $tickets = $event->tickets()
            ->with('attendee')
            ->latest()
            ->paginate(20);

        return view('events.tickets', compact('event', 'tickets'));
    }

    public function allAttendees(): View
    {
        $attendees = \App\Models\Attendee::with(['events' => function($query) {
                $query->select('events.id', 'name')
                    ->withPivot('registration_status', 'registered_at');
            }, 'ticket'])
            ->latest()
            ->paginate(20);

        return view('attendees.index', compact('attendees'));
    }

    public function getAttendees(Event $event)
    {
        return response()->json($event->attendees);
    }

    /**
     * Assign a scanner to the event.
     */
    public function assignScanner(Event $event, Request $request)
    {
        $request->validate([
            'scanner_id' => 'required|exists:users,id'
        ]);

        $user = \App\Models\User::findOrFail($request->scanner_id);
        
        // Create or get scanner record
        $scanner = \App\Models\Scanner::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => true
            ]
        );

        $event->scanners()->attach($scanner->id, [
            'assigned_by' => auth()->id(),
            'assigned_at' => now()
        ]);

        return redirect()->back()->with('success', 'Scanner assigned successfully.');
    }

    /**
     * Remove a scanner from the event.
     */
    public function removeScanner(Event $event, Scanner $scanner)
    {
        $event->scanners()->detach($scanner->id);

        return redirect()->back()->with('success', 'Scanner removed successfully.');
    }

    /**
     * Update a scanner assignment.
     */
    public function updateScanner(Event $event, Scanner $scanner, Request $request)
    {
        $request->validate([
            'new_scanner_id' => 'required|exists:users,id'
        ]);

        $newUser = \App\Models\User::findOrFail($request->new_scanner_id);
        
        // Create or get new scanner record
        $newScanner = \App\Models\Scanner::firstOrCreate(
            ['user_id' => $newUser->id],
            [
                'name' => $newUser->name,
                'email' => $newUser->email,
                'is_active' => true
            ]
        );

        // Remove old scanner and attach new one
        $event->scanners()->detach($scanner->id);
        $event->scanners()->attach($newScanner->id, [
            'assigned_by' => auth()->id(),
            'assigned_at' => now()
        ]);

        return redirect()->back()->with('success', 'Scanner updated successfully.');
    }
}
