<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendInvitationSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The event ID
     *
     * @var int
     */
    protected int $eventId;

    /**
     * The user ID who initiated the job
     *
     * @var int
     */
    protected int $userId;

    /**
     * Create a new job instance.
     *
     * @param int $eventId
     * @param int $userId
     * @return void
     */
    public function __construct(int $eventId, int $userId)
    {
        $this->eventId = $eventId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @param SmsService $smsService
     * @return void
     */
    public function handle(SmsService $smsService): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting invitation SMS job for event', [
                'job_id' => $this->job->getJobId(),
                'event_id' => $this->eventId,
                'user_id' => $this->userId,
                'timestamp' => now()->toIso8601String()
            ]);

            // Get the event with attendees and tickets
            $event = Event::with(['attendees.ticket', 'attendees.smsLogs'])->findOrFail($this->eventId);

            $totalAttendees = $event->attendees->count();
            $attendeesWithPhone = $event->attendees->whereNotNull('phone')->count();
            $alreadySentCount = 0;
            $sentCount = 0;
            $failedCount = 0;
            $skippedCount = 0;
            $errors = [];

            Log::info('Invitation SMS job statistics', [
                'job_id' => $this->job->getJobId(),
                'event_id' => $this->eventId,
                'event_name' => $event->name,
                'total_attendees' => $totalAttendees,
                'attendees_with_phone' => $attendeesWithPhone,
                'timestamp' => now()->toIso8601String()
            ]);

            foreach ($event->attendees as $attendee) {
                try {
                    // Skip if no phone number
                    if (!$attendee->phone) {
                        $skippedCount++;
                        Log::info('Skipping attendee - no phone number', [
                            'job_id' => $this->job->getJobId(),
                            'event_id' => $this->eventId,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'reason' => 'no_phone_number'
                        ]);
                        continue;
                    }

                    // Check if invitation SMS was already sent
                    $existingSms = $attendee->smsLogs->whereJsonContains('metadata->type', 'invitation')->first();
                    if ($existingSms) {
                        $alreadySentCount++;
                        Log::info('Skipping attendee - invitation SMS already sent', [
                            'job_id' => $this->job->getJobId(),
                            'event_id' => $this->eventId,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'existing_sms_id' => $existingSms->id,
                            'existing_sms_date' => $existingSms->created_at->toDateTimeString(),
                            'reason' => 'already_sent'
                        ]);
                        continue;
                    }

                    // Get the ticket for this attendee
                    $ticket = $attendee->ticket;
                    if (!$ticket) {
                        $skippedCount++;
                        Log::warning('Skipping attendee - no ticket found', [
                            'job_id' => $this->job->getJobId(),
                            'event_id' => $this->eventId,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'reason' => 'no_ticket'
                        ]);
                        continue;
                    }

                    // Send invitation SMS
                    $result = $smsService->sendInvitation($ticket);

                    if ($result['success']) {
                        $sentCount++;
                        Log::info('Invitation SMS sent successfully', [
                            'job_id' => $this->job->getJobId(),
                            'event_id' => $this->eventId,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'ticket_id' => $ticket->id,
                            'phone' => $attendee->phone,
                            'message_id' => $result['message_id'] ?? null,
                            'log_id' => $result['log_id'] ?? null
                        ]);
                    } else {
                        $failedCount++;
                        $errors[] = [
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'phone' => $attendee->phone,
                            'error' => $result['error'] ?? 'Unknown error'
                        ];
                        Log::error('Failed to send invitation SMS', [
                            'job_id' => $this->job->getJobId(),
                            'event_id' => $this->eventId,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'phone' => $attendee->phone,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                    }

                    // Add a small delay between SMS to avoid rate limiting
                    usleep(100000); // 0.1 second delay

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'phone' => $attendee->phone ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    Log::error('Exception while sending invitation SMS', [
                        'job_id' => $this->job->getJobId(),
                        'event_id' => $this->eventId,
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'phone' => $attendee->phone ?? 'unknown',
                        'exception_class' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Log job completion
            Log::info('Invitation SMS job completed', [
                'job_id' => $this->job->getJobId(),
                'event_id' => $this->eventId,
                'event_name' => $event->name,
                'total_attendees' => $totalAttendees,
                'attendees_with_phone' => $attendeesWithPhone,
                'already_sent_count' => $alreadySentCount,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'skipped_count' => $skippedCount,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'errors' => $errors,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Invitation SMS job failed', [
                'job_id' => $this->job->getJobId(),
                'event_id' => $this->eventId,
                'user_id' => $this->userId,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invitation SMS job failed permanently', [
            'job_id' => $this->job->getJobId(),
            'event_id' => $this->eventId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String()
        ]);
    }
} 