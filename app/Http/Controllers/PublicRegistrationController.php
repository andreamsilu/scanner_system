<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function manage(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate(['action' => 'required|in:generate,enable,disable,regenerate']);

        DB::transaction(function () use ($event, $validated) {
            $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $action = $validated['action'];
            if ($action === 'regenerate' || ! $locked->public_registration_token) {
                $locked->public_registration_token = (string) Str::uuid();
            }
            $locked->public_registration_enabled = $action !== 'disable';
            $locked->saveQuietly();
        });

        return to_route($request->input('return_to') === 'guests' ? 'events.guests.index' : 'events.show', $event)
            ->with('success', 'Public registration settings updated.');
    }

    private function event(string $token): Event
    {
        return Event::where('public_registration_token', $token)
            ->where('public_registration_enabled', true)->firstOrFail();
    }

    private function closedReason(Event $event): ?string
    {
        if ($event->status === Event::STATUS_COMPLETED || $event->end_date->isPast()) {
            return 'Registration for this event has closed.';
        }
        if ($event->attendees()->count() >= $event->capacity) {
            return 'This event is fully booked.';
        }

        return null;
    }

    public function show(string $token): View
    {
        $event = $this->event($token);

        return view('public-registration.form', ['event' => $event, 'closedReason' => $this->closedReason($event)]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $event = $this->event($token);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['bail', 'required', 'string', 'max:25', 'regex:/^\+?[0-9 ()-]{7,25}$/', function ($attribute, $value, $fail) {
                $length = strlen(preg_replace('/[^0-9]/', '', $value));
                if ($length < 7 || $length > 15) {
                    $fail('Enter a valid phone number, including the country code.');
                }
            }],
            'email' => 'nullable|email|max:255',
        ]);
        $validated['phone'] = preg_replace('/[^0-9]/', '', $validated['phone']);
        $validated['name'] = trim($validated['name']);
        $validated['email'] = isset($validated['email']) ? strtolower(trim($validated['email'])) : null;

        $ticket = DB::transaction(function () use ($event, $token, $validated) {
            // Serialize registrations with ticket allocation and administrative link changes.
            $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->public_registration_enabled && $locked->public_registration_token === $token, 404);
            if ($reason = $this->closedReason($locked)) {
                throw ValidationException::withMessages(['registration' => $reason]);
            }
            $duplicate = $locked->attendees()->where(function ($query) use ($validated) {
                $query->whereIn('phone', [$validated['phone'], '+'.$validated['phone']]);
                if ($validated['email']) {
                    $query->orWhereRaw('LOWER(email) = ?', [$validated['email']]);
                }
            })->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['registration' => 'A registration with these contact details already exists. Contact the organizer for help.']);
            }

            $attendee = $locked->attendees()->create($validated + ['registration_status' => 'unchecked_in']);

            // Public registrants cannot select privileged or double tickets.
            return Ticket::create([
                'event_id' => $locked->id, 'attendee_id' => $attendee->id,
                'type' => 'single', 'status' => 'active', 'token' => $attendee->token,
            ]);
        }, 3);

        $request->session()->put('public_registration_receipts.'.$token, $ticket->id);

        return to_route('public-registration.complete', $token);
    }

    public function complete(Request $request, string $token): View
    {
        // Receipts are visible only in the browser that submitted the registration.
        $event = Event::where('public_registration_token', $token)->firstOrFail();
        $ticket = $event->tickets()->with('attendee')
            ->whereKey($request->session()->get('public_registration_receipts.'.$token))->firstOrFail();

        return view('public-registration.complete', compact('event', 'ticket'));
    }
}
