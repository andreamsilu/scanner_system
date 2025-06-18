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
        try {
            Log::info('Starting single SMS job', [
                'job_id' => $this->job->getJobId(),
                'recipient' => $this->phone,
                'type' => $this->type,
                'timestamp' => now()->toIso8601String()
            ]);

            $result = $smsService->send(
                $this->phone,
                $this->message,
                $this->type,
                $this->metadata
            );

            if ($result['success']) {
                Log::info('Single SMS sent successfully', [
                    'job_id' => $this->job->getJobId(),
                    'recipient' => $this->phone,
                    'message_id' => $result['message_id'] ?? null,
                    'reference' => $result['reference'] ?? null
                ]);
            } else {
                Log::warning('Single SMS failed', [
                    'job_id' => $this->job->getJobId(),
                    'recipient' => $this->phone,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                // If this is the last attempt, log as error
                if ($this->attempts() >= $this->tries) {
                    Log::error('Single SMS job failed permanently', [
                        'job_id' => $this->job->getJobId(),
                        'recipient' => $this->phone,
                        'type' => $this->type,
                        'error' => $result['error'] ?? 'Unknown error',
                        'attempts' => $this->attempts()
                    ]);
                } else {
                    // Retry the job
                    throw new \Exception($result['error'] ?? 'SMS sending failed');
                }
            }

        } catch (\Exception $e) {
            Log::error('Single SMS job exception', [
                'job_id' => $this->job->getJobId(),
                'recipient' => $this->phone,
                'type' => $this->type,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);

            // If this is the last attempt, don't throw again
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
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
        Log::error('Single SMS job failed permanently', [
            'job_id' => $this->job->getJobId(),
            'recipient' => $this->phone,
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);
    }
} 