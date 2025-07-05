<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\Attendee;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSmsInvitation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test-invitation {event_id?} {--phone=+255123456789}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMS invitation functionality with a sample ticket';

    /**
     * The SMS service instance
     *
     * @var SmsService
     */
    protected SmsService $smsService;

    /**
     * Create a new command instance.
     */
    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing SMS Invitation Functionality...');
        
        try {
            // Get event ID from argument or find first available event
            $eventId = $this->argument('event_id');
            $phone = $this->option('phone');
            
            if (!$eventId) {
                $event = Event::first();
                if (!$event) {
                    $this->error('❌ No events found in the database. Please create an event first.');
                    return 1;
                }
                $eventId = $event->id;
                $this->warn("⚠️  No event ID provided, using first available event (ID: {$eventId})");
            } else {
                $event = Event::find($eventId);
                if (!$event) {
                    $this->error("❌ Event with ID {$eventId} not found.");
                    return 1;
                }
            }
            
            $this->info("📅 Using event: {$event->name} (ID: {$event->id})");
            $this->info("📱 Test phone number: {$phone}");
            
            // Create a test attendee if none exists
            $attendee = $event->attendees()->first();
            if (!$attendee) {
                $this->warn("⚠️  No attendees found for this event. Creating a test attendee...");
                $attendee = Attendee::create([
                    'event_id' => $event->id,
                    'name' => 'Test Attendee',
                    'email' => 'test@example.com',
                    'phone' => $phone,
                    'registration_status' => 'registered',
                    'token' => 'TEST-' . time()
                ]);
                $this->info("✅ Created test attendee: {$attendee->name}");
            } else {
                $this->info("👤 Using existing attendee: {$attendee->name}");
            }
            
            // Create a test ticket if none exists
            $ticket = $attendee->tickets()->first();
            if (!$ticket) {
                $this->warn("⚠️  No tickets found for this attendee. Creating a test ticket...");
                $ticket = Ticket::create([
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'type' => 'regular',
                    'status' => 'active',
                    'ticket_number' => 'TEST-' . time(),
                    'token' => 'TICKET-' . time()
                ]);
                $this->info("✅ Created test ticket: {$ticket->ticket_number}");
            } else {
                $this->info("🎫 Using existing ticket: {$ticket->ticket_number}");
            }
            
            // Test SMS sending
            $this->info("\n📤 Sending test SMS invitation...");
            
            $result = $this->smsService->send(
                $phone,
                "Test SMS invitation for {$event->name}. Ticket: {$ticket->token}. Event date: " . $event->start_date->format('M d, Y'),
                'test_invitation',
                [
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'ticket_id' => $ticket->id,
                    'test' => true,
                    'timestamp' => now()->toIso8601String()
                ]
            );
            
            if ($result['success']) {
                $this->info("✅ SMS sent successfully!");
                $this->info("📊 Message ID: " . ($result['message_id'] ?? 'N/A'));
                $this->info("📝 Log ID: " . ($result['log_id'] ?? 'N/A'));
                
                // Show SMS log details
                if (isset($result['log_id'])) {
                    $this->info("\n📋 SMS Log Details:");
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Log ID', $result['log_id']],
                            ['Recipient', $phone],
                            ['Status', $result['status'] ?? 'sent'],
                            ['Message Type', 'test_invitation'],
                            ['Event', $event->name],
                            ['Attendee', $attendee->name],
                            ['Ticket', $ticket->ticket_number]
                        ]
                    );
                }
            } else {
                $this->error("❌ SMS sending failed!");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
                return 1;
            }
            
            $this->info("\n🎉 SMS invitation test completed successfully!");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed with exception: " . $e->getMessage());
            Log::error('SMS invitation test failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 