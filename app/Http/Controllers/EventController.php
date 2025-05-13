<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
        return view('events.show', compact('event'));
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
