<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSingleSms implements ShouldQueue
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
    public $backoff = 30;

    /**
     * The phone number to send SMS to
     *
     * @var string
     */
    protected string $phone;

    /**
     * The message content
     *
     * @var string
     */
    protected string $message;

    /**
     * The message type
     *
     * @var string
     */
    protected string $type;

    /**
     * Additional metadata
     *
     * @var array
     */
    protected array $metadata;

    /**
     * Create a new job instance.
     *
     * @param string $phone
     * @param string $message
     * @param string $type
     * @param array $metadata
     * @return void
     */
    public function __construct(string $phone, string $message, string $type, array $metadata = [])
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->type = $type;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     *
     * @param SmsService $smsService
     * @return void
     */
    public function handle(SmsService $smsService): void
    {
        $jobId = uniqid('single_sms_', true);
        $attemptNumber = $this->attempts();
        
        try {
            // Log job start with detailed information
            Log::info('Starting single SMS job execution', [
                'job_id' => $jobId,
                'attempt_number' => $attemptNumber,
                'recipient' => $this->phone,
                'type' => $this->type,
                'message_length' => strlen($this->message),
                'metadata_count' => count($this->metadata),
                'timestamp' => now()->toIso8601String(),
                'queue_connection' => config('queue.default'),
                'max_attempts' => $this->tries,
                'backoff_seconds' => $this->backoff
            ]);

            // Log metadata details
            Log::info('SMS job metadata details', [
                'job_id' => $jobId,
                'metadata' => $this->metadata,
                'attendee_id' => $this->metadata['attendee_id'] ?? null,
                'ticket_id' => $this->metadata['ticket_id'] ?? null,
                'event_id' => $this->metadata['event_id'] ?? null,
                'validation_type' => $this->metadata['validation_type'] ?? null,
                'entry_point' => $this->metadata['entry_point'] ?? null
            ]);

            // Log SMS service call attempt
            Log::info('Attempting to call SMS service', [
                'job_id' => $jobId,
                'recipient' => $this->phone,
                'type' => $this->type,
                'service_class' => get_class($smsService),
                'timestamp' => now()->toIso8601String()
            ]);

            $result = $smsService->send(
                $this->phone,
                $this->message,
                $this->type,
                $this->metadata
            );

            // Log SMS service response
            Log::info('SMS service response received', [
                'job_id' => $jobId,
                'recipient' => $this->phone,
                'success' => $result['success'] ?? false,
                'message_id' => $result['message_id'] ?? null,
                'reference' => $result['reference'] ?? null,
                'log_id' => $result['log_id'] ?? null,
                'error' => $result['error'] ?? null,
                'timestamp' => now()->toIso8601String()
            ]);

            if ($result['success']) {
                // Log successful SMS delivery
                Log::info('Single SMS sent successfully', [
                    'job_id' => $jobId,
                    'recipient' => $this->phone,
                    'message_id' => $result['message_id'] ?? null,
                    'reference' => $result['reference'] ?? null,
                    'log_id' => $result['log_id'] ?? null,
                    'attendee_id' => $this->metadata['attendee_id'] ?? null,
                    'ticket_id' => $this->metadata['ticket_id'] ?? null,
                    'event_id' => $this->metadata['event_id'] ?? null,
                    'validation_type' => $this->metadata['validation_type'] ?? null,
                    'entry_point' => $this->metadata['entry_point'] ?? null,
                    'timestamp' => now()->toIso8601String()
                ]);
            } else {
                // Log SMS failure
                Log::warning('Single SMS failed', [
                    'job_id' => $jobId,
                    'recipient' => $this->phone,
                    'error' => $result['error'] ?? 'Unknown error',
                    'attempt_number' => $attemptNumber,
                    'max_attempts' => $this->tries,
                    'attendee_id' => $this->metadata['attendee_id'] ?? null,
                    'ticket_id' => $this->metadata['ticket_id'] ?? null,
                    'event_id' => $this->metadata['event_id'] ?? null,
                    'validation_type' => $this->metadata['validation_type'] ?? null,
                    'entry_point' => $this->metadata['entry_point'] ?? null,
                    'timestamp' => now()->toIso8601String()
                ]);

                // If this is the last attempt, log as error
                if ($attemptNumber >= $this->tries) {
                    Log::error('Single SMS job failed permanently', [
                        'job_id' => $jobId,
                        'recipient' => $this->phone,
                        'type' => $this->type,
                        'error' => $result['error'] ?? 'Unknown error',
                        'attempts' => $attemptNumber,
                        'max_attempts' => $this->tries,
                        'attendee_id' => $this->metadata['attendee_id'] ?? null,
                        'ticket_id' => $this->metadata['ticket_id'] ?? null,
                        'event_id' => $this->metadata['event_id'] ?? null,
                        'validation_type' => $this->metadata['validation_type'] ?? null,
                        'entry_point' => $this->metadata['entry_point'] ?? null,
                        'timestamp' => now()->toIso8601String()
                    ]);
                } else {
                    // Log retry attempt
                    Log::info('SMS job will be retried', [
                        'job_id' => $jobId,
                        'recipient' => $this->phone,
                        'current_attempt' => $attemptNumber,
                        'max_attempts' => $this->tries,
                        'next_attempt_delay' => $this->backoff,
                        'timestamp' => now()->toIso8601String()
                    ]);
                    
                    // Retry the job
                    throw new \Exception($result['error'] ?? 'SMS sending failed');
                }
            }

        } catch (\Exception $e) {
            // Log job execution error
            Log::error('SMS job execution failed with exception', [
                'job_id' => $jobId,
                'recipient' => $this->phone,
                'type' => $this->type,
                'attempt_number' => $attemptNumber,
                'max_attempts' => $this->tries,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'attendee_id' => $this->metadata['attendee_id'] ?? null,
                'ticket_id' => $this->metadata['ticket_id'] ?? null,
                'event_id' => $this->metadata['event_id'] ?? null,
                'validation_type' => $this->metadata['validation_type'] ?? null,
                'entry_point' => $this->metadata['entry_point'] ?? null,
                'timestamp' => now()->toIso8601String()
            ]);

            // Re-throw the exception to trigger retry or mark as failed
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
        $jobId = uniqid('single_sms_', true);
        Log::error('Single SMS job failed permanently', [
            'job_id' => $jobId,
            'recipient' => $this->phone,
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);
    }
} 