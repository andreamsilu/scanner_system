<?php

namespace Tests\Feature;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Role;
use App\Models\ScanLog;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $scanner;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->scanner = User::factory()->create(['is_active' => true]);
        $role = Role::unguarded(fn () => Role::create(['name' => 'Scanner', 'slug' => 'scanner']));
        $this->scanner->roles()->attach($role);
        $this->event = $this->makeEvent();
        $this->scanner->events()->attach($this->event, ['assigned_at' => now(), 'assigned_by' => $this->scanner->id]);
    }

    private function makeEvent(): Event
    {
        return Event::create([
            'name' => 'Test event', 'venue' => 'Hall', 'location' => 'Dar es Salaam',
            'start_date' => now()->subHour(), 'end_date' => now()->addHour(),
            'capacity' => 100, 'status' => 'active', 'created_by' => $this->scanner->id,
        ]);
    }

    private function ticket(string $type = 'single', ?Event $event = null): Ticket
    {
        $event ??= $this->event;
        $attendee = Attendee::withoutEvents(fn () => Attendee::create([
            'event_id' => $event->id, 'name' => 'Guest', 'phone' => '255712345678',
            'email' => null, 'token' => Str::random(48), 'registration_status' => 'unchecked_in',
        ]));

        return Ticket::create(['event_id' => $event->id, 'attendee_id' => $attendee->id, 'type' => $type, 'status' => 'active']);
    }

    private function scan(Ticket $ticket, array $options = [])
    {
        return $this->actingAs($this->scanner)->withSession(['selected_event_id' => $ticket->event_id])
            ->postJson(route('scanner.scan.validate'), array_merge(['token' => $ticket->attendee->token], $options));
    }

    public function test_numbers_start_at_one_for_each_event_and_are_not_reused(): void
    {
        $first = $this->ticket();
        $second = $this->ticket();
        $this->assertSame('1', $first->ticket_number);
        $this->assertSame('2', $second->ticket_number);
        $second->delete();
        $this->assertSame('3', $this->ticket()->ticket_number);
        $this->assertSame('1', $this->ticket(event: $this->makeEvent())->ticket_number);
    }

    public function test_standard_ticket_admits_once_and_rejects_duplicates(): void
    {
        $ticket = $this->ticket();
        $this->scan($ticket)->assertOk()->assertJsonPath('ticket.remaining_entries', 0);
        $this->scan($ticket)->assertStatus(422)->assertJsonPath('code', 'TICKET_USED');
        $this->assertSame(1, ScanLog::count());
        $this->assertSame('checked_in', $ticket->attendee->fresh()->registration_status);
    }

    public function test_double_ticket_requires_a_choice_without_consuming_entries(): void
    {
        $ticket = $this->ticket('double');
        $this->scan($ticket)->assertOk()->assertJsonPath('code', 'ENTRY_CHOICE_REQUIRED')->assertJsonPath('ticket.remaining_entries', 2);
        $this->assertSame(0, $ticket->fresh()->entries_used);
        $this->assertSame(0, ScanLog::count());
    }

    public function test_double_ticket_can_admit_one_person_at_a_time(): void
    {
        $ticket = $this->ticket('double');
        $this->scan($ticket, ['entries' => 1, 'expected_entries_used' => 0])->assertOk()->assertJsonPath('ticket.remaining_entries', 1);
        $this->assertSame('active', $ticket->fresh()->status);
        $this->scan($ticket)->assertOk()->assertJsonPath('code', 'ENTRY_CHOICE_REQUIRED')->assertJsonPath('ticket.remaining_entries', 1);
        $this->scan($ticket, ['entries' => 1, 'expected_entries_used' => 1])->assertOk()->assertJsonPath('ticket.remaining_entries', 0);
        $this->scan($ticket)->assertStatus(422);
        $this->assertSame(2, ScanLog::sum('entries_admitted'));
    }

    public function test_double_entry_consumes_both_admissions(): void
    {
        $ticket = $this->ticket('double');
        $this->scan($ticket, ['entries' => 2, 'expected_entries_used' => 0])->assertOk()->assertJsonPath('entries_admitted', 2);
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertSame(1, ScanLog::count());
        $this->assertSame(2, ScanLog::first()->entries_admitted);
    }

    public function test_stale_confirmation_cannot_consume_the_remaining_entry(): void
    {
        $ticket = $this->ticket('double');
        $this->scan($ticket, ['entries' => 1, 'expected_entries_used' => 0])->assertOk();
        $this->scan($ticket, ['entries' => 1, 'expected_entries_used' => 0])->assertStatus(422)->assertJsonPath('code', 'TICKET_CHANGED');
        $this->assertSame(1, $ticket->fresh()->remaining_entries);
    }

    public function test_cannot_over_admit_or_scan_cancelled_tickets(): void
    {
        $ticket = $this->ticket();
        $this->scan($ticket, ['entries' => 2])->assertStatus(422)->assertJsonPath('code', 'ENTRY_LIMIT');
        $ticket->update(['status' => 'cancelled']);
        $this->scan($ticket)->assertStatus(422)->assertJsonPath('code', 'TICKET_CANCELLED');
        $this->assertSame(0, ScanLog::count());
    }

    public function test_revoked_event_assignment_and_disabled_accounts_are_rejected(): void
    {
        $ticket = $this->ticket();
        $this->scanner->events()->detach($this->event);
        $this->scan($ticket)->assertForbidden();
        $this->scanner->is_active = false;
        $this->scanner->saveQuietly();
        $this->scan($ticket)->assertForbidden();
        $this->assertSame(0, ScanLog::count());
    }

    public function test_public_ticket_links_never_record_admission(): void
    {
        $ticket = $this->ticket();
        $this->get(route('scanner.check-in', $ticket->attendee->token))->assertOk();
        $this->get(route('tickets.verify', $ticket->attendee->token))->assertOk();
        $this->assertSame(0, $ticket->fresh()->entries_used);
        $this->assertSame(0, ScanLog::count());
    }

    public function test_scanners_cannot_access_sms_management(): void
    {
        $this->actingAs($this->scanner)->getJson('/sms/management')->assertForbidden();
    }

    public function test_qr_verification_url_and_event_scoped_manual_number_work(): void
    {
        $ticket = $this->ticket();
        $result = app(CheckInService::class)->admit($this->scanner, $this->event->id, route('tickets.verify', $ticket->attendee->token));
        $this->assertTrue($result['success']);
        $other = $this->ticket();
        $result = app(CheckInService::class)->admit($this->scanner, $this->event->id, $other->ticket_number);
        $this->assertTrue($result['success']);
    }

    public function test_closed_events_and_cancelled_registrations_are_rejected(): void
    {
        $ticket = $this->ticket();
        $ticket->attendee->update(['registration_status' => 'cancelled']);
        $this->scan($ticket)->assertStatus(422)->assertJsonPath('code', 'TICKET_CANCELLED');
        $this->event->update(['end_date' => now()->subMinute()]);
        $this->scan($ticket)->assertStatus(422)->assertJsonPath('code', 'EVENT_CLOSED');
    }

    public function test_scanner_page_and_counts_include_partially_used_double_tickets(): void
    {
        $ticket = $this->ticket('double');
        $this->scan($ticket, ['entries' => 1, 'expected_entries_used' => 0])->assertOk();
        $this->get(route('scanner.scan.index'))->assertOk()->assertSee('Single entry')->assertSee('Double entry');
        $this->get(route('scanner.dashboard'))->assertOk()->assertViewHas('assigned_events', function ($events) {
            return $events->first()->scan_stats['scanned_count'] === 1;
        });
        $this->assertFalse($this->scanner->events()->first()->relationLoaded('attendees'));
    }

    public function test_invalid_scan_input_returns_validation_errors_without_admission(): void
    {
        $ticket = $this->ticket();
        $this->scan($ticket, ['token' => ['unexpected' => 'array']])->assertStatus(422);
        $this->scan($ticket, ['entries' => 3])->assertStatus(422);
        $this->assertSame(0, ScanLog::count());
    }
}
