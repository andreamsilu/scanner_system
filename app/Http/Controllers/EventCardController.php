<?php

namespace App\Http\Controllers;

use App\Models\EventCard;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EventCardController extends Controller
{
    public function index(): View
    {
        $cards = EventCard::with(['event', 'attendee'])
            ->latest()
            ->paginate(15);
        return view('event-cards.index', compact('cards'));
    }

    public function create(Request $request): View
    {
        // Get only active events that haven't ended
        $events = Event::where('status', 'active')
            ->where('end_date', '>=', now())
            ->orderBy('start_date', 'desc')
            ->get();

        // Debug information
        \Log::info('Available events:', [
            'count' => $events->count(),
            'events' => $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'status' => $event->status,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date
                ];
            })
        ]);

        $selectedEvent = null;
        if ($request->has('event_id')) {
            $selectedEvent = Event::with('attendees')
                ->where('status', 'active')
                ->where('end_date', '>=', now())
                ->findOrFail($request->event_id);
        }

        return view('event-cards.create', compact('events', 'selectedEvent'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'attendee_id' => 'required|exists:attendees,id',
            'token' => 'required|unique:event_cards,token',
            'is_active' => 'boolean',
        ]);

        EventCard::create($validated);

        return redirect()->route('event-cards.index')
            ->with('success', 'Event card created successfully.');
    }

    public function show(EventCard $card): View
    {
        $card->load(['event', 'attendee', 'scanLogs']);
        return view('event-cards.show', compact('card'));
    }

    public function edit(EventCard $card): View
    {
        $events = Event::where('status', 'active')->get();
        return view('event-cards.edit', compact('card', 'events'));
    }

    public function update(Request $request, EventCard $card): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'attendee_id' => 'required|exists:attendees,id',
            'token' => 'required|unique:event_cards,token,' . $card->id,
            'is_active' => 'boolean',
        ]);

        $card->update($validated);

        return redirect()->route('event-cards.index')
            ->with('success', 'Event card updated successfully.');
    }

    public function destroy(EventCard $card): RedirectResponse
    {
        $card->delete();

        return redirect()->route('event-cards.index')
            ->with('success', 'Event card deleted successfully.');
    }

    public function download(EventCard $card)
    {
        // Generate QR code and return PDF
        return $card->generatePDF();
    }

    public function verify(string $token)
    {
        $card = EventCard::where('token', $token)->firstOrFail();
        return view('event-cards.verify', compact('card'));
    }
} 