<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrationSafetyTest extends TestCase
{
    public function test_upgrade_preserves_credentials_and_used_double_tickets_and_assigns_event_numbers(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.migration_probe' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('migration_probe');
        try {
            $upgrade = database_path('migrations/2026_10_25_000001_add_event_ticket_numbers_and_admissions.php');
            $historical = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($path) => $path !== $upgrade));
            $this->artisan('migrate', ['--database' => 'migration_probe', '--path' => $historical, '--realpath' => true, '--force' => true])->assertExitCode(0);
            $user = DB::table('users')->insertGetId(['name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'test-hash']);
            $event = DB::table('events')->insertGetId([
                'name' => 'Existing event', 'venue' => 'Hall', 'location' => 'Town', 'capacity' => 100,
                'start_date' => now()->subHour(), 'end_date' => now()->addHour(), 'created_by' => $user,
            ]);
            $attendee = DB::table('attendees')->insertGetId([
                'event_id' => $event, 'name' => 'Existing guest', 'phone' => '0712345678', 'token' => '123456',
            ]);
            $ticketId = DB::table('tickets')->insertGetId([
                'event_id' => $event, 'attendee_id' => $attendee, 'ticket_number' => 'TICKET-ORIGINAL',
                'token' => '123456', 'type' => 'double', 'status' => 'used', 'used_at' => now(),
            ]);
            DB::table('scan_logs')->insert([
                'ticket_id' => $ticketId, 'scanner_id' => $user, 'scanned_at' => now(), 'status' => 'success',
            ]);
            (require $upgrade)->up();
            $ticket = Ticket::findOrFail($ticketId);
            $this->assertSame('1', $ticket->ticket_number);
            $this->assertSame('TICKET-ORIGINAL', $ticket->legacy_ticket_number);
            $this->assertSame('123456', $ticket->token);
            $this->assertSame('123456', $ticket->attendee->token);
            $this->assertSame(2, $ticket->entries_used);
            $this->assertSame(0, $ticket->remaining_entries);
            $this->assertSame('used', $ticket->status);
            $this->assertSame(2, (int) DB::table('events')->where('id', $event)->value('next_ticket_number'));
            $this->assertSame(1, DB::table('scan_logs')->count());

            // Cascading deletes can no longer erase the admission history.
            $this->expectException(QueryException::class);
            DB::table('tickets')->where('id', $ticketId)->delete();
        } finally {
            DB::disconnect('migration_probe');
            DB::setDefaultConnection($original);
        }
    }
}
