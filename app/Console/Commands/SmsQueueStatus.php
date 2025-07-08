<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SmsQueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:queue:status {--json : Output in JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of SMS queue worker and active jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $status = $this->getQueueStatus();
            
            if ($this->option('json')) {
                $this->output->write(json_encode($status, JSON_PRETTY_PRINT));
                return 0;
            }

            $this->displayStatus($status);
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to get queue status: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get queue status information
     *
     * @return array
     */
    private function getQueueStatus(): array
    {
        $pid = Cache::get('sms_queue_pid');
        $isRunning = $pid && file_exists("/proc/{$pid}");
        
        $activeJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        // Get job progress for all events
        $jobProgress = [];
        $cacheKeys = Cache::get('active_sms_jobs', []);
        
        foreach ($cacheKeys as $jobKey) {
            $jobData = Cache::get($jobKey);
            if ($jobData) {
                $jobProgress[] = $jobData;
            }
        }

        return [
            'queue_status' => [
                'is_running' => $isRunning,
                'pid' => $pid,
                'active_jobs' => $activeJobs,
                'failed_jobs' => $failedJobs
            ],
            'job_progress' => $jobProgress
        ];
    }

    /**
     * Display status information
     *
     * @param array $status
     * @return void
     */
    private function displayStatus(array $status): void
    {
        $queueStatus = $status['queue_status'];
        $jobProgress = $status['job_progress'];

        $this->info('=== SMS Queue Status ===');
        
        // Queue Worker Status
        $this->line('');
        $this->line('Queue Worker:');
        $this->line('  Status: ' . ($queueStatus['is_running'] ? 'Running' : 'Stopped'));
        
        if ($queueStatus['pid']) {
            $this->line('  PID: ' . $queueStatus['pid']);
        }
        
        $this->line('  Active Jobs: ' . $queueStatus['active_jobs']);
        $this->line('  Failed Jobs: ' . $queueStatus['failed_jobs']);

        // Job Progress
        if (!empty($jobProgress)) {
            $this->line('');
            $this->info('Active SMS Jobs:');
            
            foreach ($jobProgress as $job) {
                $this->line('');
                $this->line('  Event: ' . $job['event_name']);
                $this->line('  Job ID: ' . $job['job_id']);
                $this->line('  Status: ' . ucfirst($job['status']));
                $this->line('  Progress: ' . $job['progress'] . '%');
                $this->line('  Started: ' . $job['started_at']);
                
                if ($job['completed_at']) {
                    $this->line('  Completed: ' . $job['completed_at']);
                }
                
                $this->line('  Statistics:');
                $this->line('    Total Attendees: ' . $job['total_attendees']);
                $this->line('    Sent: ' . $job['sent_count']);
                $this->line('    Failed: ' . $job['failed_count']);
                $this->line('    Skipped: ' . $job['skipped_count']);
                
                if ($job['current_attendee']) {
                    $this->line('    Currently Processing: ' . $job['current_attendee']);
                }
                
                if (!empty($job['errors'])) {
                    $this->line('    Errors: ' . count($job['errors']));
                }
            }
        } else {
            $this->line('');
            $this->line('No active SMS jobs found.');
        }

        $this->line('');
        $this->info('=== End Status ===');
    }
} 