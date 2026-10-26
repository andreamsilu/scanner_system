<?php

namespace Tests\Feature;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create(['is_active' => true]);
        $role = Role::unguarded(fn () => Role::create(['name' => 'Admin', 'slug' => 'admin']));
        $this->admin->roles()->attach($role);
        $this->event = Event::create([
            'name' => 'Community gathering', 'venue' => 'Town Hall', 'location' => 'Dar es Salaam',
            'start_date' => now()->addDay(), 'end_date' => now()->addDays(2),
            'capacity' => 10, 'created_by' => $this->admin->id,
        ]);
    }

    private function enable(): string
    {
        $this->event->forceFill([
            'public_registration_enabled' => true, 'public_registration_token' => (string) Str::uuid(),
        ])->saveQuietly();

        return route('public-registration.show', $this->event->public_registration_token);
    }

    private function guest(array $overrides = []): array
    {
        return array_merge(['name' => 'Asha Guest', 'phone' => '+255 712 345 678', 'email' => 'asha@example.com'], $overrides);
    }

    public function test_registration_is_private_until_an_admin_generates_a_link(): void
    {
        $this->assertFalse($this->event->fresh()->public_registration_enabled);
        $this->get(route('public-registration.show', (string) Str::uuid()))->assertNotFound();
        $this->actingAs($this->admin)->post(route('events.public-registration', $this->event), ['action' => 'generate'])
            ->assertRedirect(route('events.show', $this->event));
        $event = $this->event->fresh();
        $this->assertTrue($event->public_registration_enabled);
        $this->assertTrue(Str::isUuid($event->public_registration_token));
        $this->get(route('events.show', $event))->assertOk()->assertSee('Copy link')->assertSee('Open form');
    }

    public function test_guests_and_scanners_cannot_enable_public_registration(): void
    {
        $url = route('events.public-registration', $this->event);
        $this->postJson($url, ['action' => 'generate'])->assertUnauthorized();
        $scanner = User::factory()->create();
        $role = Role::unguarded(fn () => Role::create(['name' => 'Scanner', 'slug' => 'scanner']));
        $scanner->roles()->attach($role);
        $this->actingAs($scanner)->postJson($url, ['action' => 'generate'])->assertForbidden();
        $this->assertFalse($this->event->fresh()->public_registration_enabled);
    }

    public function test_public_form_registers_a_guest_with_a_sequential_single_ticket_and_qr(): void
    {
        $url = $this->enable();
        $this->get($url)->assertOk()->assertSee('Community gathering')->assertSee('Reserve your place');
        $this->assertSame(0, Attendee::count());
        $response = $this->post($url, $this->guest(['ticket_type' => 'vip', 'type' => 'double', 'ticket_number' => '999']));
        $response->assertRedirect(route('public-registration.complete', $this->event->public_registration_token));
        $ticket = Ticket::sole();
        $this->assertSame('1', $ticket->ticket_number);
        $this->assertSame('single', $ticket->type);
        $this->assertSame(1, $ticket->remaining_entries);
        $this->assertSame('255712345678', $ticket->attendee->phone);
        $this->assertSame($ticket->token, $ticket->attendee->token);
        Storage::disk('public')->assertExists($ticket->attendee->qr_code);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee("You're registered!", false)->assertSee('#1');
        $this->post($url, $this->guest(['phone' => '+255 713 345 678', 'email' => null]))->assertRedirect();
        $this->assertSame(['1', '2'], Ticket::orderBy('id')->pluck('ticket_number')->all());
    }

    public function test_duplicate_contact_details_do_not_create_more_tickets(): void
    {
        $url = $this->enable();
        $this->post($url, $this->guest())->assertRedirect();
        $this->post($url, $this->guest(['phone' => '255712345678', 'email' => 'other@example.com']))
            ->assertSessionHasErrors('registration');
        $this->post($url, $this->guest(['phone' => '+255 713 345 678', 'email' => 'ASHA@example.com']))
            ->assertSessionHasErrors('registration');
        $this->assertSame(1, Ticket::count());
        $this->assertSame(2, $this->event->fresh()->next_ticket_number);
    }

    public function test_full_events_reject_registration_on_the_server(): void
    {
        $url = $this->enable();
        $this->event->forceFill(['capacity' => 1])->saveQuietly();
        $this->post($url, $this->guest())->assertRedirect();
        $this->get($url)->assertOk()->assertSee('fully booked')->assertDontSee('Register for event');
        $this->post($url, $this->guest(['phone' => '255713345678', 'email' => null]))->assertSessionHasErrors('registration');
        $this->assertSame(1, Ticket::count());
    }

    public function test_ended_events_reject_registration(): void
    {
        $url = $this->enable();
        $this->event->forceFill(['end_date' => now()->subMinute()])->saveQuietly();
        $this->get($url)->assertOk()->assertSee('Registration closed');
        $this->post($url, $this->guest())->assertSessionHasErrors('registration');
        $this->assertSame(0, Ticket::count());
    }

    public function test_admin_can_disable_reenable_and_replace_the_link(): void
    {
        $url = $this->enable();
        $manage = route('events.public-registration', $this->event);
        $this->actingAs($this->admin)->post($manage, ['action' => 'disable'])->assertRedirect();
        $this->get($url)->assertNotFound();
        $this->post($url, $this->guest())->assertNotFound();
        $this->post($manage, ['action' => 'enable'])->assertRedirect();
        $this->get($url)->assertOk();
        $this->post($manage, ['action' => 'regenerate'])->assertRedirect();
        $this->get($url)->assertNotFound();
        $this->post($url, $this->guest())->assertNotFound();
        $this->get(route('public-registration.show', $this->event->fresh()->public_registration_token))->assertOk();
    }

    public function test_confirmation_is_not_publicly_accessible(): void
    {
        $url = $this->enable();
        $complete = route('public-registration.complete', $this->event->public_registration_token);
        $this->get($complete)->assertNotFound();
        $this->post($url, $this->guest())->assertRedirect($complete);
        $this->get($complete)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->flushSession();
        $this->get($complete)->assertNotFound();
    }

    public function test_invalid_input_and_repeated_submissions_are_rejected(): void
    {
        $url = $this->enable();
        $this->post($url, $this->guest(['phone' => '--- --- ---', 'email' => 'invalid']))
            ->assertSessionHasErrors(['phone', 'email']);
        $this->post($url, [])->assertSessionHasErrors(['name', 'phone']);
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->postJson($url, [])->assertUnprocessable();
        }
        $this->postJson($url, $this->guest())->assertStatus(429);
        $this->assertSame(0, Ticket::count());
    }
}
