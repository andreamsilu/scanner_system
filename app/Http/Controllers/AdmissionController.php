<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use App\Services\CheckInService;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function store(Request $request, CheckInService $service)
    {
        $input = $request->validate([
            'token' => ['required', 'string', 'max:2048'],
            'entries' => ['nullable', 'integer', 'between:1,2'],
            'expected_entries_used' => ['nullable', 'integer', 'between:0,2'],
        ]);
        $eventId = (int) session('selected_event_id');
        if (! $eventId) {
            return response()->json(['success' => false, 'code' => 'NO_EVENT_SELECTED', 'message' => 'Please select an event first.'], 422);
        }
        $result = $service->admit(
            $request->user(), $eventId, $input['token'],
            isset($input['entries']) ? (int) $input['entries'] : null,
            isset($input['expected_entries_used']) ? (int) $input['expected_entries_used'] : null
        );

        return response()->json($result, $result['success'] || ($result['code'] ?? null) === 'ENTRY_CHOICE_REQUIRED' ? 200 : 422);
    }

    public function prepare(Event $event, Attendee $attendee)
    {
        abort_unless((int) $attendee->event_id === (int) $event->id, 404);
        session(['selected_event_id' => $event->id]);

        return redirect()->route('scanner.scan.index', ['ticket' => $attendee->ticket?->ticket_number]);
    }

    public function prepareToken(string $token)
    {
        $attendee = Attendee::where('token', $token)->firstOrFail();
        abort_unless(auth()->user()->hasRole('admin') || auth()->user()->events()->where('events.id', $attendee->event_id)->exists(), 403);
        session(['selected_event_id' => $attendee->event_id]);

        return redirect()->route('scanner.scan.index', ['ticket' => $attendee->ticket?->ticket_number]);
    }
}
