<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StopSmsQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:queue:stop {--force : Force kill the process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the SMS queue worker safely';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Stopping SMS queue worker...');

        try {
            $pid = Cache::get('sms_queue_pid');
            
            if (!$pid) {
                $this->warn('No queue worker PID found. Queue worker may not be running.');
                return 0;
            }

            $force = $this->option('force');
            $signal = $force ? 'KILL' : 'TERM';

            // Kill the process
            $result = exec("kill -{$signal} {$pid} 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                // Clear the PID from cache
                Cache::forget('sms_queue_pid');
                
                Log::info('SMS queue worker stopped', [
                    'pid' => $pid,
                    'signal' => $signal,
                    'timestamp' => now()->toIso8601String()
                ]);

                $this->info("SMS queue worker stopped successfully (PID: {$pid})");
                return 0;
            } else {
                // Check if process is still running
                if (file_exists("/proc/{$pid}")) {
                    if ($force) {
                        $this->error("Failed to force kill queue worker (PID: {$pid})");
                        return 1;
                    } else {
                        $this->warn("Process is still running. Use --force to kill it forcefully.");
                        return 1;
                    }
                } else {
                    // Process is already dead, clear cache
                    Cache::forget('sms_queue_pid');
                    $this->info("Queue worker process was already stopped (PID: {$pid})");
                    return 0;
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to stop SMS queue worker', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            $this->error('Failed to stop queue worker: ' . $e->getMessage());
            return 1;
        }
    }
} 