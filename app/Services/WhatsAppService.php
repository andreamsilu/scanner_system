<?php

namespace App\Services;

use App\Jobs\SendWhatsAppInvitation;
use App\Models\Attendee;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WhatsAppService
{
    public function setupIssues(): array
    {
        $issues = [];
        if (! config('services.whatsapp.enabled')) {
            $issues[] = 'WhatsApp Business sending is not enabled.';
        }
        foreach (['access_token', 'phone_number_id', 'api_version', 'template_name', 'language', 'app_secret', 'verify_token'] as $key) {
            if (! config('services.whatsapp.'.$key)) {
                $issues[] = 'Missing WhatsApp '.str_replace('_', ' ', $key).'.';
            }
        }
        if (! preg_match('/^v[0-9]+\.[0-9]+$/', (string) config('services.whatsapp.api_version'))) {
            $issues[] = 'Select a supported Meta Graph API version.';
        }
        if (! ctype_digit((string) config('services.whatsapp.phone_number_id'))) {
            $issues[] = 'The WhatsApp phone number ID must contain digits only.';
        }
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $issues[] = 'Set the public site address to HTTPS before sending invitations.';
        }
        if (in_array(config('queue.default'), ['sync', 'null'], true)) {
            $issues[] = 'Configure a persistent background queue and start its worker.';
        }

        return $issues;
    }

    public function normalizePhone(string $value): string
    {
        if (! preg_match('/^\+?[0-9 ()-]+$/', trim($value))) {
            throw ValidationException::withMessages(['whatsappConsent' => 'Enter a valid WhatsApp phone number.']);
        }
        $number = preg_replace('/[^0-9]/', '', $value);
        $country = (string) config('services.whatsapp.default_country_code', '255');
        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        } elseif (str_starts_with($number, '0')) {
            $number = $country.substr($number, 1);
        } elseif (strlen($number) === 9 && ! str_starts_with(trim($value), '+')) {
            $number = $country.$number;
        }
        if (! preg_match('/^[1-9][0-9]{7,14}$/', $number)) {
            throw ValidationException::withMessages(['whatsappConsent' => 'Use a phone number with its international country code.']);
        }

        return $number;
    }

    public function setConsent(Attendee $guest, bool $consented, string $source): void
    {
        $number = $consented ? $this->normalizePhone($guest->phone ?? '') : $guest->whatsapp_phone;
        $unchanged = $consented && $guest->whatsapp_opted_in_at && $guest->whatsapp_phone === $number;
        $guest->forceFill([
            'whatsapp_phone' => $number,
            'whatsapp_opted_in_at' => $consented ? ($unchanged ? $guest->whatsapp_opted_in_at : now()) : null,
            'whatsapp_opt_in_source' => $unchanged ? $guest->whatsapp_opt_in_source : $source,
        ])->saveQuietly();
    }

    public function hasConsent(Attendee $guest): bool
    {
        if (! $guest->whatsapp_opted_in_at) {
            return false;
        }
        try {
            return $guest->whatsapp_phone === $this->normalizePhone($guest->phone ?? '');
        } catch (ValidationException) {
            return false;
        }
    }

    public function eligible(Ticket $ticket): bool
    {
        $guest = $ticket->attendee;
        if (! $guest || $guest->trashed() || ! $guest->whatsapp_opted_in_at || $ticket->status !== 'active'
            || $guest->registration_status === 'cancelled' || $ticket->event->status === 'completed' || $ticket->event->end_date->isPast()) {
            return false;
        }
        return $this->hasConsent($guest);
    }

    public function queue(Ticket $ticket, User $actor): bool
    {
        abort_unless($actor->is_active && $actor->isAdmin(), 403);
        if ($this->setupIssues()) {
            throw ValidationException::withMessages(['whatsapp' => 'Finish the WhatsApp Business setup before sending.']);
        }

        return DB::transaction(function () use ($ticket, $actor) {
            $locked = Ticket::with(['attendee', 'event'])->lockForUpdate()->findOrFail($ticket->id);
            if (! $this->eligible($locked)) {
                return false;
            }
            $invitation = WhatsAppInvitation::firstOrCreate(['ticket_id' => $locked->id], [
                'reference' => (string) Str::uuid(), 'event_id' => $locked->event_id,
                'attendee_id' => $locked->attendee_id, 'requested_by' => $actor->id,
                'recipient' => $locked->attendee->whatsapp_phone,
                'template_name' => config('services.whatsapp.template_name'),
                'language' => config('services.whatsapp.language'), 'status' => 'queued',
            ]);
            // A ticket is invited once. Only a confirmed failure can be retried.
            if (! $invitation->wasRecentlyCreated && $invitation->status !== 'failed') {
                return false;
            }
            $invitation->update([
                'reference' => $invitation->wasRecentlyCreated ? $invitation->reference : (string) Str::uuid(),
                'message_id' => null,
                'status' => 'queued', 'recipient' => $locked->attendee->whatsapp_phone,
                'requested_by' => $actor->id, 'error_code' => null, 'error_message' => null,
                'template_name' => config('services.whatsapp.template_name'), 'language' => config('services.whatsapp.language'),
            ]);
            SendWhatsAppInvitation::dispatch($invitation->id)->afterCommit();

            return true;
        }, 3);
    }

    public function send(int $id): void
    {
        $invitation = DB::transaction(function () use ($id) {
            $invitation = WhatsAppInvitation::with(['ticket.attendee', 'ticket.event', 'requester'])->lockForUpdate()->find($id);
            if (! $invitation || $invitation->status !== 'queued') {
                return null;
            }
            if ($this->setupIssues() || ! $invitation->requester?->is_active || ! $invitation->requester->isAdmin()
                || ! $invitation->ticket || ! $this->eligible($invitation->ticket)
                || $invitation->recipient !== $invitation->ticket->attendee->whatsapp_phone) {
                $invitation->update(['status' => 'failed', 'error_message' => 'Sending stopped: check setup, guest consent, account access and ticket status.']);

                return null;
            }
            $invitation->update(['status' => 'sending', 'attempted_at' => now()]);

            return $invitation;
        }, 3);
        if (! $invitation) {
            return;
        }

        $ticket = $invitation->ticket;
        $event = $ticket->event;
        $values = [$ticket->attendee->name, $event->name, $event->start_date->format('d M Y, H:i T'),
            trim($event->venue.' · '.$event->location, ' ·'), $ticket->ticket_number,
            rtrim(config('app.url'), '/').'/tickets/'.$ticket->attendee->token.'/verify'];
        try {
            // Never automatically retry an HTTP send: a timeout may already have delivered it.
            $response = Http::withToken(config('services.whatsapp.access_token'))->acceptJson()->connectTimeout(10)->timeout(30)
                ->post('https://graph.facebook.com/'.config('services.whatsapp.api_version').'/'.config('services.whatsapp.phone_number_id').'/messages', [
                    'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $invitation->recipient,
                    'type' => 'template', 'biz_opaque_callback_data' => $invitation->reference,
                    'template' => ['name' => $invitation->template_name, 'language' => ['code' => $invitation->language],
                        'components' => [['type' => 'body', 'parameters' => array_map(fn ($value) => [
                            'type' => 'text', 'text' => preg_replace('/\s+/', ' ', (string) $value),
                        ], $values)]]],
                ]);
            $messageId = $response->json('messages.0.id');
            if ($response->successful() && is_string($messageId) && $messageId !== '') {
                WhatsAppInvitation::whereKey($id)->whereIn('status', ['sending', 'unknown'])->update([
                    'status' => 'accepted', 'message_id' => $messageId, 'error_message' => null,
                ]);
            } else {
                WhatsAppInvitation::whereKey($id)->where('status', 'sending')->update([
                    'status' => $response->clientError() ? 'failed' : 'unknown',
                    'error_code' => substr((string) $response->json('error.code', $response->status()), 0, 40),
                    'error_message' => $response->clientError() ? 'Meta rejected this invitation. Check the error code, template and account settings.' : 'Send outcome unknown. Check Meta before trying again.',
                ]);
            }
        } catch (\Throwable) {
            WhatsAppInvitation::whereKey($id)->where('status', 'sending')->update([
                'status' => 'unknown', 'error_message' => 'Send outcome unknown. Waiting for a delivery update; do not resend.',
            ]);
        }
    }
}
