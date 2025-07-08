<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StartSmsQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:queue:start {--daemon : Run in daemon mode} {--sleep=3 : Number of seconds to wait when no job is available} {--tries=3 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the SMS queue worker with proper configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting SMS queue worker...');

        try {
            // Check if queue is already running
            if ($this->isQueueRunning()) {
                $this->warn('Queue worker is already running.');
                return 0;
            }

            // Get command options
            $daemon = $this->option('daemon');
            $sleep = $this->option('sleep');
            $tries = $this->option('tries');

            // Build the command
            $command = 'php ' . base_path('artisan') . ' queue:work';
            
            if ($daemon) {
                $command .= ' --daemon';
            }
            
            $command .= " --sleep={$sleep} --tries={$tries}";
            
            if ($daemon) {
                $command .= ' > /dev/null 2>&1 & echo $!';
            }

            // Execute the command
            if ($daemon) {
                $pid = exec($command);
                
                if ($pid && is_numeric($pid)) {
                    // Store the PID for later management
                    Cache::put('sms_queue_pid', $pid, 3600);
                    
                    Log::info('SMS queue worker started in daemon mode', [
                        'pid' => $pid,
                        'sleep' => $sleep,
                        'tries' => $tries,
                        'timestamp' => now()->toIso8601String()
                    ]);

                    $this->info("SMS queue worker started successfully with PID: {$pid}");
                    return 0;
                } else {
                    $this->error('Failed to start queue worker. Could not get PID.');
                    return 1;
                }
            } else {
                // Run in foreground
                $this->info('Running queue worker in foreground. Press Ctrl+C to stop.');
                passthru($command);
                return 0;
            }

        } catch (\Exception $e) {
            Log::error('Failed to start SMS queue worker', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            $this->error('Failed to start queue worker: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if queue worker is running
     *
     * @return bool
     */
    private function isQueueRunning(): bool
    {
        $pid = Cache::get('sms_queue_pid');
        
        if (!$pid) {
            return false;
        }

        // Check if process is still running
        return file_exists("/proc/{$pid}");
    }
} 