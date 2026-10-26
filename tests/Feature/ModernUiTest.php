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
        $this->get(route('events.tickets.index', $this->event))->assertOk()->assertSee('Find a ticket')->assertSee('No tickets yet');
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
            ->set('search', 'missing')->assertSee('No matching tickets');
    }

    public function test_partial_admissions_are_visible_and_filterable(): void
    {
        $ticket = $this->ticket('One guest remaining', 'double');
        $ticket->entries_used = 1;
        $ticket->save();
        $this->ticket('Not arrived');

        Livewire::actingAs($this->admin)->test(TicketBrowser::class, ['event' => $this->event])
            ->set('status', 'partial')->assertSee('One guest remaining')->assertSee('Partially used')
            ->assertSee('1 of 2 admitted')->assertDontSee('Not arrived');
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
