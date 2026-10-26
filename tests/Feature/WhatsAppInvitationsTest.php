<?php

namespace Tests\Feature;

use App\Jobs\QueueWhatsAppInvitations;
use App\Jobs\SendWhatsAppInvitation;
use App\Livewire\TicketBrowser;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppInvitation;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppInvitationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Event $event;
    private WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
        Http::preventStrayRequests();
        config(['app.url' => 'https://events.example.com', 'queue.default' => 'database', 'services.whatsapp' => [
            'enabled' => true, 'api_version' => 'v99.0', 'access_token' => 'test-token',
            'phone_number_id' => '12345678901', 'app_secret' => 'test-secret', 'verify_token' => 'test-verify',
            'template_name' => 'event_invitation', 'language' => 'en_US', 'default_country_code' => '255',
        ]]);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::create(['name' => 'Administrator', 'slug' => 'admin']));
        $this->event = Event::create([
            'name' => 'Conference', 'venue' => 'Main hall', 'location' => 'Dar es Salaam',
            'start_date' => now()->addDay(), 'end_date' => now()->addDays(2), 'capacity' => 100, 'created_by' => $this->admin->id,
        ]);
        $this->service = app(WhatsAppService::class);
    }

    private function ticket(bool $consent = true, ?Event $event = null): Ticket
    {
        $event ??= $this->event;
        $guest = $event->attendees()->create(['name' => 'Guest', 'phone' => '0712345678', 'registration_status' => 'unchecked_in']);
        $this->service->setConsent($guest, $consent, 'test');

        return Ticket::create(['event_id' => $event->id, 'attendee_id' => $guest->id, 'status' => 'active', 'type' => 'single', 'token' => $guest->token]);
    }

    private function queue(Ticket $ticket): WhatsAppInvitation
    {
        $this->assertTrue($this->service->queue($ticket, $this->admin));

        return WhatsAppInvitation::where('ticket_id', $ticket->id)->sole();
    }

    private function webhook(array $value, bool $signed = true)
    {
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [[
            'value' => ['metadata' => ['phone_number_id' => '12345678901']] + $value,
        ]]]]]);

        return $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signed ? 'sha256='.hash_hmac('sha256', $body, 'test-secret') : 'sha256=invalid',
        ], $body);
    }

    public function test_consent_and_a_real_background_queue_are_required(): void
    {
        $ticket = $this->ticket(false);
        $this->assertFalse($this->service->queue($ticket, $this->admin));
        Queue::assertNothingPushed();
        $this->assertSame(0, WhatsAppInvitation::count());
        config(['queue.default' => 'sync']);
        $this->assertContains('Configure a persistent background queue and start its worker.', $this->service->setupIssues());
        config(['services.whatsapp.access_token' => null]);
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('sendBulkWhatsApp')->assertHasErrors('whatsapp');
        Http::assertNothingSent();
    }

    public function test_phone_numbers_are_normalized_and_invalid_numbers_are_rejected(): void
    {
        foreach (['0712 345 678', '+255 (712) 345-678', '00255712345678', '712345678'] as $number) {
            $this->assertSame('255712345678', $this->service->normalizePhone($number));
        }
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->normalizePhone('not-a-phone');
    }

    public function test_approved_template_is_sent_once_with_event_details_and_ticket_link(): void
    {
        $ticket = $this->ticket();
        $record = $this->queue($ticket);
        $this->assertFalse($this->service->queue($ticket, $this->admin));
        Queue::assertPushed(SendWhatsAppInvitation::class, 1);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);
        $this->service->send($record->id);
        $this->service->send($record->id);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '255712345678' && $request['type'] === 'template'
            && $request['template']['name'] === 'event_invitation'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Guest'
            && $request['template']['components'][0]['parameters'][1]['text'] === 'Conference'
            && $request['template']['components'][0]['parameters'][4]['text'] === '1'
            && $request['template']['components'][0]['parameters'][5]['text'] === 'https://events.example.com/tickets/'.$ticket->attendee->token.'/verify');
        $this->assertSame('accepted', $record->fresh()->status);
        $this->assertSame('wamid.test', $record->fresh()->message_id);
    }

    public function test_revoked_consent_changed_phone_and_cancelled_tickets_are_not_sent(): void
    {
        $ticket = $this->ticket();
        $record = $this->queue($ticket);
        $this->service->setConsent($ticket->attendee, false, 'withdrawn');
        $this->service->send($record->id);
        $this->assertSame('failed', $record->fresh()->status);
        $other = $this->ticket();
        $otherRecord = $this->queue($other);
        $other->attendee->update(['phone' => '0713999999']);
        $this->service->send($otherRecord->id);
        $cancelled = $this->ticket();
        $cancelledRecord = $this->queue($cancelled);
        $cancelled->update(['status' => 'cancelled']);
        $this->service->send($cancelledRecord->id);
        $this->assertSame('failed', $otherRecord->fresh()->status);
        $this->assertSame('failed', $cancelledRecord->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_timeouts_have_an_unknown_outcome_and_are_not_retried(): void
    {
        $ticket = $this->ticket();
        $record = $this->queue($ticket);
        Http::fake(fn () => throw new ConnectionException('Timeout with sensitive details'));
        $this->service->send($record->id);
        $this->assertSame('unknown', $record->fresh()->status);
        $this->assertFalse($this->service->queue($ticket, $this->admin));
        $this->assertStringNotContainsString('sensitive', $record->fresh()->error_message);
    }

    public function test_confirmed_rejection_can_be_retried_with_a_new_reference(): void
    {
        $ticket = $this->ticket();
        $record = $this->queue($ticket);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 132001, 'message' => 'secret should not be stored']], 400)]);
        $this->service->send($record->id);
        $this->assertSame('failed', $record->fresh()->status);
        $this->assertSame('132001', $record->fresh()->error_code);
        $this->assertStringNotContainsString('secret', $record->fresh()->error_message);
        $this->assertTrue($this->service->queue($ticket, $this->admin));
        $this->assertNotSame($record->reference, $record->fresh()->reference);
        $this->assertSame(1, WhatsAppInvitation::count());
    }

    public function test_bulk_invitations_are_scoped_and_skip_unconsented_and_already_invited_guests(): void
    {
        $eligible = $this->ticket();
        $this->ticket(false);
        $invited = $this->ticket();
        $this->queue($invited);
        $otherEvent = $this->event->replicate();
        $otherEvent->save();
        $outside = $this->ticket(event: $otherEvent);
        (new QueueWhatsAppInvitations($this->event->id, $this->admin->id))->handle($this->service);
        $this->assertDatabaseHas('whatsapp_invitations', ['ticket_id' => $eligible->id, 'status' => 'queued']);
        $this->assertDatabaseMissing('whatsapp_invitations', ['ticket_id' => $outside->id]);
        $this->assertSame(2, WhatsAppInvitation::count());
        (new QueueWhatsAppInvitations($this->event->id, $this->admin->id))->handle($this->service);
        Queue::assertPushed(SendWhatsAppInvitation::class, 2);
    }

    public function test_admin_controls_queue_individual_and_bulk_invitations(): void
    {
        $ticket = $this->ticket();
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('editGuest', $ticket->attendee_id)->assertSee('Send WhatsApp invitation')
            ->call('sendWhatsApp', $ticket->attendee_id)->assertSee('WhatsApp invitation queued.')
            ->call('sendBulkWhatsApp')->assertSee('Bulk WhatsApp invitations scheduled');
        Queue::assertPushed(SendWhatsAppInvitation::class);
        Queue::assertPushed(QueueWhatsAppInvitations::class, fn ($job) => $job->eventId === $this->event->id);
    }

    public function test_disabled_admins_cannot_send_and_revocation_stops_queued_messages(): void
    {
        $ticket = $this->ticket();
        $record = $this->queue($ticket);
        $this->admin->roles()->detach();
        $this->service->send($record->id);
        $this->assertSame('failed', $record->fresh()->status);
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_webhook_verification_checks_the_configured_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify&hub.challenge=12345')->assertOk()->assertContent('12345');
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345')->assertForbidden();
        $this->webhook([], false)->assertForbidden();
    }

    public function test_signed_status_updates_are_idempotent_and_do_not_downgrade_delivery(): void
    {
        $record = $this->queue($this->ticket());
        $status = ['id' => 'wamid.delivery', 'recipient_id' => $record->recipient,
            'biz_opaque_callback_data' => $record->reference, 'status' => 'read'];
        $this->webhook(['statuses' => [$status]])->assertOk();
        $this->webhook(['statuses' => [$status]])->assertOk();
        $this->webhook(['statuses' => [array_merge($status, ['status' => 'sent'])]])->assertOk();
        $this->webhook(['statuses' => [array_merge($status, ['status' => 'failed'])]])->assertOk();
        $this->assertSame('read', $record->fresh()->status);
        $this->assertSame('wamid.delivery', $record->fresh()->message_id);
    }

    public function test_stop_messages_withdraw_consent_and_prevent_queued_delivery(): void
    {
        $ticket = $this->ticket();
        $record = $this->queue($ticket);
        $this->webhook(['messages' => [['from' => $record->recipient, 'type' => 'text', 'text' => ['body' => 'STOP']]]])->assertOk();
        $this->assertNull($ticket->attendee->fresh()->whatsapp_opted_in_at);
        $this->service->send($record->id);
        Http::assertNothingSent();
    }

    public function test_public_registration_can_collect_optional_whatsapp_consent(): void
    {
        $this->event->forceFill(['public_registration_enabled' => true, 'public_registration_token' => (string) Str::uuid()])->saveQuietly();
        $url = route('public-registration.show', $this->event->public_registration_token);
        $this->get($url)->assertOk()->assertSee('reply STOP');
        $this->post($url, ['name' => 'Public guest', 'phone' => '+255712345678', 'whatsapp_consent' => '1'])->assertRedirect();
        $guest = Attendee::sole();
        $this->assertNotNull($guest->whatsapp_opted_in_at);
        $this->assertSame('public_registration', $guest->whatsapp_opt_in_source);
        $this->assertSame('255712345678', $guest->whatsapp_phone);
        Queue::assertNothingPushed();
    }

    public function test_changing_a_phone_requires_consent_to_be_confirmed_again(): void
    {
        $ticket = $this->ticket();
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('editGuest', $ticket->attendee_id)->assertSet('whatsappConsent', true)
            ->set('phone', '0713999999')->assertSet('whatsappConsent', false)->call('saveGuest')->assertHasNoErrors();
        $this->assertNull($ticket->attendee->fresh()->whatsapp_opted_in_at);
    }

    public function test_early_delivery_callback_is_not_overwritten_by_api_acceptance(): void
    {
        $record = $this->queue($this->ticket());
        Http::fake(function () use ($record) {
            $this->webhook(['statuses' => [[
                'id' => 'wamid.early', 'recipient_id' => $record->recipient,
                'biz_opaque_callback_data' => $record->reference, 'status' => 'delivered',
            ]]])->assertOk();

            return Http::response(['messages' => [['id' => 'wamid.early']]], 200);
        });
        $this->service->send($record->id);
        $this->assertSame('delivered', $record->fresh()->status);
    }

    public function test_replayed_old_stop_does_not_withdraw_later_consent(): void
    {
        $this->freezeTime();
        $ticket = $this->ticket();
        $oldTimestamp = (string) now()->subMinute()->timestamp;
        $this->webhook(['messages' => [[
            'from' => $ticket->attendee->whatsapp_phone, 'timestamp' => $oldTimestamp,
            'type' => 'text', 'text' => ['body' => 'STOP'],
        ]]])->assertOk();
        $this->assertNotNull($ticket->attendee->fresh()->whatsapp_opted_in_at);
    }

    public function test_wrong_business_number_cannot_update_delivery_or_consent(): void
    {
        $record = $this->queue($this->ticket());
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [[
            'value' => ['metadata' => ['phone_number_id' => '999999'], 'statuses' => [[
                'id' => 'wamid.wrong', 'recipient_id' => $record->recipient,
                'biz_opaque_callback_data' => $record->reference, 'status' => 'delivered',
            ]]],
        ]]]]]);
        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test-secret'),
        ], $body)->assertOk();
        $this->assertSame('queued', $record->fresh()->status);
    }

    public function test_legacy_phone_changes_do_not_silently_renew_permission(): void
    {
        $ticket = $this->ticket();
        $ticket->attendee->update(['phone' => '0713999999']);
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('editGuest', $ticket->attendee_id)->assertSet('whatsappConsent', false)
            ->call('saveGuest')->assertHasNoErrors();
        $this->assertNull($ticket->attendee->fresh()->whatsapp_opted_in_at);
    }

    public function test_only_the_signed_webhook_is_exempt_from_csrf(): void
    {
        $this->app->instance('env', 'production');
        try {
            $this->webhook([])->assertOk();
            $this->post('/login', ['email' => 'any@example.com', 'password' => 'invalid'])->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }
}
