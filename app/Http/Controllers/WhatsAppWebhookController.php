<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\WhatsAppInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $expected = (string) config('services.whatsapp.verify_token');
        $token = (string) $request->query('hub.verify_token', $request->query('hub_verify_token', ''));
        abort_unless($expected !== '' && hash_equals($expected, $token)
            && $request->query('hub.mode', $request->query('hub_mode')) === 'subscribe', 403);

        return response((string) $request->query('hub.challenge', $request->query('hub_challenge', '')), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request)
    {
        $secret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        abort_unless($secret !== '' && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $signature), 403);
        $payload = $request->json()->all();
        abort_unless(($payload['object'] ?? '') === 'whatsapp_business_account', 400);
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (($value['metadata']['phone_number_id'] ?? '') !== (string) config('services.whatsapp.phone_number_id')) {
                    continue;
                }
                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    $this->updateStatus($status);
                }
                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $text = strtoupper(trim((string) ($message['text']['body'] ?? $message['button']['text'] ?? '')));
                    if (in_array($text, ['STOP', 'UNSUBSCRIBE', 'SITISHA', 'ACHA'], true) && isset($message['from'])) {
                        Attendee::where('whatsapp_phone', $message['from'])->update([
                            'whatsapp_opted_in_at' => null, 'whatsapp_opt_in_source' => 'whatsapp_stop',
                        ]);
                    }
                }
            }
        }

        return response()->json(['received' => true]);
    }

    private function updateStatus(array $status): void
    {
        $next = $status['status'] ?? '';
        if (! in_array($next, ['sent', 'delivered', 'read', 'failed'], true) || empty($status['id'])) {
            return;
        }
        DB::transaction(function () use ($status, $next) {
            $invitation = WhatsAppInvitation::where(function ($query) use ($status) {
                $query->where('message_id', $status['id']);
                if (is_string($status['biz_opaque_callback_data'] ?? null)) {
                    $query->orWhere('reference', $status['biz_opaque_callback_data']);
                }
            })->lockForUpdate()->first();
            if (! $invitation || ($status['recipient_id'] ?? '') !== $invitation->recipient) {
                return;
            }
            $ranks = ['queued' => 0, 'sending' => 1, 'unknown' => 1, 'failed' => 1, 'accepted' => 2, 'sent' => 3, 'delivered' => 4, 'read' => 5];
            $current = $ranks[$invitation->status] ?? 0;
            if (($next === 'failed' && $current >= 4) || ($next !== 'failed' && $ranks[$next] < $current)) {
                return;
            }
            $invitation->update([
                'message_id' => $status['id'], 'status' => $next,
                'error_code' => $next === 'failed' ? substr((string) ($status['errors'][0]['code'] ?? ''), 0, 40) : null,
                'error_message' => $next === 'failed' ? 'WhatsApp reported delivery failure. Check the error code before retrying.' : null,
            ]);
        }, 3);
    }
}
