<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Scanner;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::with('creator')
            ->latest()
            ->paginate(15);

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        return view('events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $this->validateEvent($request);

            $validated['created_by'] = auth()->id();
            $validated['is_public'] = $request->boolean('is_public', false);

            $event = Event::create($validated);

            Log::info('Event created successfully', ['event_id' => $event->id]);

            return redirect()
                ->route('events.show', $event)
                ->with('success', 'Event created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create event. Please try again.');
        }
    }

    public function show(Event $event): View
    {
        $event->load(['creator', 'attendees', 'cards']);
        $scannerUsers = \App\Models\User::where('role', 'scanner')->get();
        return view('events.show', compact('event', 'scannerUsers'));
    }

    public function edit(Event $event): View
    {
        return view('events.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        try {
            $validated = $this->validateEvent($request);

            $validated['is_public'] = $request->boolean('is_public', false);

            $event->update($validated);

            Log::info('Event updated successfully', ['event_id' => $event->id]);

            return redirect()
                ->route('events.show', $event)
                ->with('success', 'Event updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update event. Please try again.');
        }
    }

    public function destroy(Event $event): RedirectResponse
    {
        try {
            $event->delete();

            Log::info('Event deleted successfully', ['event_id' => $event->id]);

            return redirect()
                ->route('events.index')
                ->with('success', 'Event deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to delete event. Please try again.');
        }
    }

    public function attendees(Event $event): View
    {
        $attendees = $event->attendees()
            ->with('user')
            ->latest()
            ->paginate(15);

        return view('events.attendees', compact('event', 'attendees'));
    }

    public function cards(Event $event): View
    {
        $cards = $event->cards()
            ->with('attendee')
            ->latest()
            ->paginate(15);

        return view('events.cards', compact('event', 'cards'));
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

    protected function validateEvent(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'venue' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'status' => 'required|in:draft,active,completed,cancelled',
            'capacity' => 'required|integer|min:1',
            'is_public' => 'boolean'
        ]);
    }
}
