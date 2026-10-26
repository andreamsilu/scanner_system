<?php

namespace App\Services;

use App\Jobs\SendSingleSms;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\ScanLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckInService
{
    public function admit(User $scanner, int $eventId, string $credential, ?int $entries = null, ?int $expectedEntriesUsed = null): array
    {
        abort_unless($scanner->is_active, 403, 'This account is disabled.');
        abort_unless($scanner->hasRole('admin') || $scanner->events()->where('events.id', $eventId)->exists(), 403, 'You are not assigned to this event.');

        $event = Event::findOrFail($eventId);
        if ($event->status !== 'active' || now()->lt($event->start_date) || now()->gt($event->end_date)) {
            return $this->failure('EVENT_CLOSED', 'This event is not open for admission.');
        }
        $token = TicketToken::extract($credential);
        if ($token === '') {
            return $this->failure('INVALID_TOKEN', 'Enter a valid ticket number or scan a QR code.');
        }

        return DB::transaction(function () use ($scanner, $event, $token, $entries, $expectedEntriesUsed) {
            // Always look up QR credentials first; short legacy numeric tokens may also look like ticket numbers.
            $ticket = Ticket::where('event_id', $event->id)
                ->whereHas('attendee', fn ($query) => $query->where('token', $token))
                ->lockForUpdate()->first();
            $ticket ??= Ticket::where('event_id', $event->id)
                ->where('ticket_number', $token)->lockForUpdate()->first();
            if (! $ticket) {
                return $this->failure('INVALID_TOKEN', 'Ticket not found for the selected event.');
            }
            $attendee = Attendee::whereKey($ticket->attendee_id)->lockForUpdate()->first();
            if (! $attendee || (int) $attendee->event_id !== (int) $event->id) {
                return $this->failure('INVALID_TOKEN', 'Ticket not found for the selected event.');
            }
            if ($ticket->status === 'cancelled' || $attendee->registration_status === 'cancelled') {
                return $this->failure('TICKET_CANCELLED', 'This ticket or registration has been cancelled.');
            }
            if ($ticket->status !== 'active' || $ticket->remaining_entries < 1) {
                return $this->failure('TICKET_USED', 'This ticket has no admissions remaining.');
            }
            $details = [
                'ticket_number' => $ticket->ticket_number,
                'type' => $ticket->type,
                'entries_used' => $ticket->entries_used,
                'remaining_entries' => $ticket->remaining_entries,
            ];
            if ($ticket->type === 'double' && ($entries === null || $expectedEntriesUsed === null)) {
                return [
                    'success' => false,
                    'code' => 'ENTRY_CHOICE_REQUIRED',
                    'message' => 'Single entry or double entry?',
                    'ticket' => $details,
                    'attendee' => ['name' => $attendee->name],
                ];
            }
            if ($expectedEntriesUsed !== null && $expectedEntriesUsed !== $ticket->entries_used) {
                return $this->failure('TICKET_CHANGED', 'This ticket was just scanned elsewhere. Scan it again to see the remaining entries.');
            }
            $entries ??= 1;
            if ($entries < 1 || $entries > $ticket->remaining_entries) {
                return $this->failure('ENTRY_LIMIT', 'The requested admission exceeds the entries remaining on this ticket.');
            }
            $ticket->entries_used += $entries;
            $ticket->status = $ticket->remaining_entries === 0 ? 'used' : 'active';
            $ticket->used_at = $ticket->status === 'used' ? now() : null;
            $ticket->save();
            $attendee->update([
                'registration_status' => 'checked_in',
                'checked_in_at' => $attendee->checked_in_at ?? now(),
            ]);
            ScanLog::create([
                'ticket_id' => $ticket->id,
                'scanner_id' => $scanner->id,
                'status' => 'success',
                'entries_admitted' => $entries,
                'notes' => $entries === 2 ? 'Double entry' : 'Single entry',
            ]);
            if ($attendee->phone) {
                DB::afterCommit(function () use ($attendee, $ticket, $entries) {
                    try {
                        SendSingleSms::dispatch(
                            $attendee->phone,
                            "Ticket #{$ticket->ticket_number}: {$entries} admitted. {$ticket->remaining_entries} entries remaining.",
                            'ticket_used',
                            ['attendee_id' => $attendee->id, 'event_id' => $ticket->event_id, 'ticket_id' => $ticket->id]
                        );
                    } catch (\Throwable $exception) {
                        Log::error('Unable to queue admission notification', ['ticket_id' => $ticket->id]);
                    }
                });
            }

            return [
                'success' => true,
                'message' => "{$entries} admitted. {$ticket->remaining_entries} entries remaining.",
                'entries_admitted' => $entries,
                'ticket' => array_merge($details, ['entries_used' => $ticket->entries_used, 'remaining_entries' => $ticket->remaining_entries]),
                'attendee' => ['name' => $attendee->name, 'checked_in_at' => $attendee->checked_in_at->toIso8601String()],
            ];
        }, 3);
    }

    private function failure(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
