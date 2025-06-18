<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkSms implements ShouldQueue
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
     * The SMS data array
     *
     * @var array
     */
    protected array $smsData;

    /**
     * Create a new job instance.
     *
     * @param array $smsData
     * @return void
     */
    public function __construct(array $smsData)
    {
        $this->smsData = $smsData;
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
            Log::info('Starting bulk SMS job', [
                'job_id' => $this->job->getJobId(),
                'sms_count' => count($this->smsData),
                'timestamp' => now()->toIso8601String()
            ]);

            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($this->smsData as $index => $smsItem) {
                try {
                    $result = $smsService->send(
                        $smsItem['phone'],
                        $smsItem['message'],
                        $smsItem['type'],
                        $smsItem['metadata'] ?? []
                    );

                    if ($result['success']) {
                        $successCount++;
                        Log::info('Bulk SMS sent successfully', [
                            'job_id' => $this->job->getJobId(),
                            'index' => $index,
                            'recipient' => $smsItem['phone'],
                            'message_id' => $result['message_id'] ?? null
                        ]);
                    } else {
                        $failureCount++;
                        $errors[] = [
                            'index' => $index,
                            'recipient' => $smsItem['phone'],
                            'error' => $result['error'] ?? 'Unknown error'
                        ];
                        Log::warning('Bulk SMS failed', [
                            'job_id' => $this->job->getJobId(),
                            'index' => $index,
                            'recipient' => $smsItem['phone'],
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                    }

                    // Add a small delay between SMS to avoid rate limiting
                    usleep(100000); // 0.1 second delay

                } catch (\Exception $e) {
                    $failureCount++;
                    $errors[] = [
                        'index' => $index,
                        'recipient' => $smsItem['phone'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    Log::error('Bulk SMS exception', [
                        'job_id' => $this->job->getJobId(),
                        'index' => $index,
                        'recipient' => $smsItem['phone'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('Bulk SMS job completed', [
                'job_id' => $this->job->getJobId(),
                'total_count' => count($this->smsData),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk SMS job failed', [
                'job_id' => $this->job->getJobId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        Log::error('Bulk SMS job failed permanently', [
            'job_id' => $this->job->getJobId(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'sms_data_count' => count($this->smsData)
        ]);
    }
} 