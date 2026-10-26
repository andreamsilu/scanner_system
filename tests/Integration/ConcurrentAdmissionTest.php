<?php

namespace Tests\Integration;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentAdmissionTest extends TestCase
{
    private User $scanner;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql' || ! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            $this->markTestSkipped('Run against a disposable MySQL database ending in _test.');
        }
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $this->scanner = User::factory()->create();
        $role = Role::create(['slug' => 'scanner', 'name' => 'Scanner']);
        $this->scanner->roles()->attach($role);
        $this->event = Event::create([
            'name' => 'Concurrent event', 'venue' => 'Hall', 'location' => 'Town', 'capacity' => 100,
            'start_date' => now()->subHour(), 'end_date' => now()->addHour(), 'created_by' => $this->scanner->id,
        ]);
        $this->scanner->events()->attach($this->event, ['assigned_by' => $this->scanner->id, 'assigned_at' => now()]);
    }

    private function attendee(): Attendee
    {
        return Attendee::withoutEvents(fn () => Attendee::create([
            'event_id' => $this->event->id, 'name' => 'Guest', 'phone' => '', 'email' => null,
            'token' => Str::random(48), 'registration_status' => 'unchecked_in',
        ]));
    }

    private function together(string $mode, array $values): array
    {
        $start = (string) (microtime(true) + 2);
        $processes = [];
        foreach ($values as $value) {
            $process = new Process([
                PHP_BINARY, base_path('tests/Support/admission-worker.php'), $mode,
                (string) $this->scanner->id, (string) $this->event->id, (string) $value, $start,
            ], base_path(), ['APP_ENV' => 'testing', 'SCANNER_TEST_DB' => json_encode(DB::connection()->getConfig())]);
            $process->setTimeout(20);
            $process->start();
            $processes[] = $process;
        }

        return array_map(function (Process $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

            return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }, $processes);
    }

    public function test_simultaneous_double_ticket_confirmations_cannot_consume_two_single_entries(): void
    {
        $attendee = $this->attendee();
        $ticket = Ticket::create(['event_id' => $this->event->id, 'attendee_id' => $attendee->id, 'type' => 'double', 'status' => 'active']);
        $results = $this->together('admit', [$attendee->token, $attendee->token]);
        $this->assertCount(1, array_filter($results, fn ($result) => $result['success']));
        $this->assertSame(1, $ticket->fresh()->entries_used);
        $this->assertSame(1, DB::table('scan_logs')->count());
    }

    public function test_simultaneous_ticket_issuance_allocates_distinct_event_numbers(): void
    {
        $results = $this->together('issue', [$this->attendee()->id, $this->attendee()->id]);
        $numbers = array_column($results, 'ticket_number');
        sort($numbers);
        $this->assertSame(['1', '2'], $numbers);
    }
}
