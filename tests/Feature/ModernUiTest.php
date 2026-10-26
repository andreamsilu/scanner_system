<?php

namespace Tests\Feature;

use App\Livewire\TicketBrowser;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ModernUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::create(['name' => 'Administrator', 'slug' => 'admin']));
        $this->event = $this->event('Welcome conference');
    }

    private function event(string $name): Event
    {
        return Event::create([
            'name' => $name, 'venue' => 'Main hall', 'location' => 'Dar es Salaam',
            'start_date' => now()->subHour(), 'end_date' => now()->addDay(),
            'capacity' => 100, 'status' => 'active', 'created_by' => $this->admin->id,
        ]);
    }

    private function ticket(string $name, string $type = 'single', ?Event $event = null): Ticket
    {
        $event ??= $this->event;
        $attendee = Attendee::withoutEvents(fn () => Attendee::create([
            'name' => $name, 'event_id' => $event->id, 'phone' => '255712345678',
            'token' => Str::random(48), 'registration_status' => 'unchecked_in',
        ]));

        return Ticket::create(['event_id' => $event->id, 'attendee_id' => $attendee->id, 'type' => $type, 'status' => 'active']);
    }

    public function test_shared_screens_render_with_the_new_layout(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Welcome back')->assertSee('autocomplete="current-password"', false);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Overview')->assertDontSee('<<<<<<<');
        $this->get(route('events.guests.index', $this->event))->assertOk()->assertSee('Find a guest')->assertSee('No guests yet');
        $this->get(route('events.tickets.index', $this->event))->assertRedirect(route('events.guests.index', $this->event));
        $this->get(route('events.attendees.index', $this->event))->assertRedirect(route('events.guests.index', $this->event));
        $this->withSession(['selected_event_id' => $this->event->id])->get(route('scanner.scan.index'))
            ->assertOk()->assertSee('Start camera')->assertSee('How many people are entering?')->assertSee('singleEntryButton');
        $this->get(route('settings.index'))->assertOk()->assertDontSee('<<<<<<<');
    }

    public function test_search_and_filters_remain_scoped_to_the_event(): void
    {
        $this->ticket('Alice Taylor');
        $this->ticket('Brian Mushi', 'double');
        $this->ticket('Outside guest', event: $this->event('Other event'));

        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->assertSee('Alice Taylor')->assertSee('Brian Mushi')->assertDontSee('Outside guest')->assertDontSee('@js(', false)
            ->set('search', 'Alice')->assertSee('Alice Taylor')->assertDontSee('Brian Mushi')
            ->call('resetFilters')->set('type', 'double')->assertSee('Brian Mushi')->assertDontSee('Alice Taylor')
            ->set('search', 'missing')->assertSee('No matching guests');
    }

    public function test_partial_admissions_are_visible_and_filterable(): void
    {
        $ticket = $this->ticket('One guest remaining', 'double');
        $ticket->entries_used = 1;
        $ticket->save();
        $this->ticket('Not arrived');

        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->set('status', 'partial')->assertSee('One guest remaining')->assertSee('Partially used')
            ->assertSee('1 of 2 entries used')->assertDontSee('Not arrived');
    }

    public function test_sorting_uses_numeric_ticket_numbers_and_filters_reset_pagination(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->ticket('Guest '.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->assertSeeInOrder(['Guest 02', 'Guest 10'])
            ->call('setPage', 2)->assertSee('Guest 17')->assertDontSee('Guest 01')
            ->set('search', 'Guest 01')->assertSee('Guest 01')->assertDontSee('Guest 17')
            ->call('resetFilters')->set('sort', 'desc')->assertSeeInOrder(['Guest 17', 'Guest 10', 'Guest 03']);
    }

    public function test_ticket_browser_rejects_scanner_and_disabled_accounts(): void
    {
        $scanner = User::factory()->create(['is_active' => true]);
        $scanner->roles()->attach(Role::create(['name' => 'Scanner', 'slug' => 'scanner']));
        Livewire::actingAs($scanner)->test(TicketBrowser::class, ['event' => $this->event])->assertForbidden();

        $this->admin->update(['is_active' => false]);
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])->assertForbidden();
    }

    public function test_revoking_admin_access_blocks_subsequent_livewire_updates(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event]);
        $this->admin->roles()->detach();
        $component->set('search', 'Guest')->assertForbidden();
    }

    public function test_guests_without_tickets_are_visible_and_filterable(): void
    {
        Attendee::withoutEvents(fn () => Attendee::create([
            'name' => 'Awaiting ticket', 'event_id' => $this->event->id,
            'phone' => '255713345678', 'email' => 'waiting@example.com', 'token' => Str::random(48),
        ]));
        $this->ticket('Ticket holder');
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->assertSee('Awaiting ticket')->assertSee('Ticket holder')->assertSee('No ticket')
            ->set('status', 'no_ticket')->assertSee('Awaiting ticket')->assertDontSee('Ticket holder')
            ->call('resetFilters')->set('search', 'waiting@example.com')->assertSee('Awaiting ticket')->assertDontSee('Ticket holder');
    }

    public function test_add_guest_shortcut_opens_the_editor(): void
    {
        Livewire::actingAs($this->admin)->withQueryParams(['add' => '1'])
            ->test(TicketBrowser::class, ['event' => $this->event])
            ->assertDispatched('modal-show', name: 'guest-details');
    }

    public function test_guests_can_be_added_and_edited_without_leaving_the_list(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('newGuest')->set('name', 'New guest')->set('phone', '255712345678')->call('saveGuest')->assertHasNoErrors();
        $guest = Attendee::sole();
        $ticket = Ticket::sole();
        $this->assertSame('1', $ticket->ticket_number);
        $this->assertNull($guest->email);
        $this->assertSame('single', $ticket->type);
        Storage::disk('public')->assertExists($guest->qr_code);
        $component->call('editGuest', $guest->id)->set('name', 'Updated guest')->set('email', 'guest@example.com')
            ->call('saveGuest')->assertHasNoErrors()->assertSee('Updated guest');
        $this->assertSame('guest@example.com', $guest->fresh()->email);
        $this->assertSame(1, Ticket::count());
        $this->assertSame($ticket->token, $ticket->fresh()->token);
    }

    public function test_an_existing_ticketless_guest_can_receive_one_double_ticket(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('newGuest')->set('name', 'Waiting guest')->set('phone', '255712345678')->set('createTicket', false)
            ->call('saveGuest')->assertHasNoErrors();
        $guest = Attendee::sole();
        $this->assertSame(0, Ticket::count());
        $component->call('editGuest', $guest->id, true)->set('ticketType', 'double')->call('saveGuest')
            ->assertHasNoErrors()->assertSee('0 of 2 entries used');
        $component->call('saveGuest')->assertHasNoErrors();
        $this->assertSame(1, Ticket::count());
        $this->assertSame('double', $guest->fresh()->ticket->type);
    }

    public function test_other_events_guests_cannot_be_edited_or_cancelled(): void
    {
        $outside = $this->ticket('Outside guest', event: $this->event('Other event'))->attendee;
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('editGuest', $outside->id)->assertNotFound();
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('cancelGuest', $outside->id)->assertNotFound();
        $this->assertSame('unchecked_in', $outside->fresh()->registration_status);
    }

    public function test_guest_cancellation_preserves_ticket_numbers_and_admission_history(): void
    {
        $ticket = $this->ticket('Arrived guest', 'double');
        $ticket->entries_used = 1;
        $ticket->save();
        $log = $ticket->scanLogs()->create(['scanner_id' => $this->admin->id, 'status' => 'success', 'entries_admitted' => 1]);
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('editGuest', $ticket->attendee_id)->assertSee('Download PDF')->assertSee('Send invitation SMS')
            ->call('cancelGuest', $ticket->attendee_id)->assertSee('Cancelled');
        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame('cancelled', $ticket->attendee->fresh()->registration_status);
        $this->assertSame(1, $ticket->fresh()->entries_used);
        $this->assertSame('1', $ticket->fresh()->ticket_number);
        $this->assertNotNull($log->fresh());
    }

    public function test_guest_validation_and_capacity_are_enforced(): void
    {
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('newGuest')->set('name', '   ')->set('email', 'invalid')->set('ticketType', 'invalid')
            ->call('saveGuest')->assertHasErrors(['name', 'email', 'phone', 'ticketType']);
        $this->event->capacity = 1;
        $this->event->saveQuietly();
        $this->ticket('Last place');
        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->call('newGuest')->set('name', 'Extra guest')->set('phone', '255712345678')
            ->call('saveGuest')->assertHasErrors('name');
        $this->assertSame(1, Attendee::count());
    }

    public function test_registration_link_settings_return_to_the_combined_page(): void
    {
        $this->actingAs($this->admin)->post(route('events.public-registration', $this->event), ['action' => 'generate', 'return_to' => 'guests'])
            ->assertRedirect(route('events.guests.index', $this->event));
        $this->get(route('events.guests.index', $this->event))->assertOk()->assertSee('Copy link');
    }

    public function test_event_selector_shows_all_events_to_admins_and_only_assignments_to_scanners(): void
    {
        $this->event('Private event');
        $this->actingAs($this->admin)->get(route('scanner.events.index'))->assertOk()
            ->assertSee('Welcome conference')->assertSee('Private event');

        $scanner = User::factory()->create(['is_active' => true]);
        $scanner->roles()->attach(Role::create(['name' => 'Scanner', 'slug' => 'scanner']));
        $scanner->events()->attach($this->event, ['assigned_at' => now(), 'assigned_by' => $this->admin->id]);
        $this->actingAs($scanner)->get(route('scanner.events.index'))->assertOk()
            ->assertSee('Welcome conference')->assertDontSee('Private event');
    }
}
