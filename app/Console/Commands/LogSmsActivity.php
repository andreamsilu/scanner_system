<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogSmsActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:log {--follow : Follow the log file in real-time} {--lines=50 : Number of lines to show} {--filter= : Filter logs by keyword}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor SMS activity logs in real-time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logFile = storage_path('logs/laravel.log');
        $lines = $this->option('lines');
        $filter = $this->option('filter');
        $follow = $this->option('follow');

        if (!File::exists($logFile)) {
            $this->error('Log file not found: ' . $logFile);
            return 1;
        }

        $this->info('📱 SMS Activity Monitor');
        $this->info('Log file: ' . $logFile);
        $this->info('Filter: ' . ($filter ?: 'All SMS logs'));
        $this->info('Lines: ' . $lines);
        $this->info('Follow: ' . ($follow ? 'Yes' : 'No'));
        $this->line('');

        if ($follow) {
            $this->followLogs($logFile, $filter);
        } else {
            $this->showRecentLogs($logFile, $lines, $filter);
        }

        return 0;
    }

    /**
     * Show recent SMS logs
     */
    private function showRecentLogs(string $logFile, int $lines, ?string $filter): void
    {
        $command = "tail -n {$lines} " . escapeshellarg($logFile);
        
        if ($filter) {
            $command .= " | grep -i " . escapeshellarg($filter);
        }

        $output = shell_exec($command);
        
        if ($output) {
            $this->displayLogs($output);
        } else {
            $this->warn('No logs found matching the criteria.');
        }
    }

    /**
     * Follow logs in real-time
     */
    private function followLogs(string $logFile, ?string $filter): void
    {
        $this->info('🔄 Following logs in real-time... (Press Ctrl+C to stop)');
        $this->line('');

        $command = "tail -f " . escapeshellarg($logFile);
        
        if ($filter) {
            $command .= " | grep -i " . escapeshellarg($filter);
        }

        $process = popen($command, 'r');
        
        if ($process === false) {
            $this->error('Failed to open log file for following.');
            return;
        }

        while (($line = fgets($process)) !== false) {
            $this->displayLogLine($line);
        }

        pclose($process);
    }

    /**
     * Display formatted logs
     */
    private function displayLogs(string $logs): void
    {
        $lines = explode("\n", trim($logs));
        
        foreach ($lines as $line) {
            if (trim($line)) {
                $this->displayLogLine($line);
            }
        }
    }

    /**
     * Display a single log line with formatting
     */
    private function displayLogLine(string $line): void
    {
        // Parse log line
        if (preg_match('/^\[(.*?)\] (\w+)\.(\w+): (.*)$/', $line, $matches)) {
            $timestamp = $matches[1];
            $level = strtoupper($matches[2]);
            $channel = $matches[3];
            $message = $matches[4];

            // Format timestamp
            $time = Carbon::parse($timestamp)->format('H:i:s');

            // Determine color based on log level
            $color = match($level) {
                'ERROR' => 'red',
                'WARNING' => 'yellow',
                'INFO' => 'green',
                'DEBUG' => 'blue',
                default => 'white'
            };

            // Determine icon based on message content
            $icon = $this->getMessageIcon($message);

            // Display formatted line
            $this->line(sprintf(
                '<fg=%s>%s</> [<fg=cyan>%s</>] <fg=magenta>%s</> %s %s',
                $color,
                $time,
                $level,
                $channel,
                $icon,
                $message
            ));
        } else {
            // Fallback for non-standard log lines
            $this->line($line);
        }
    }

    /**
     * Get appropriate icon for message type
     */
    private function getMessageIcon(string $message): string
    {
        if (stripos($message, 'SMS job dispatched') !== false) {
            return '📤';
        }
        if (stripos($message, 'SMS sent successfully') !== false) {
            return '✅';
        }
        if (stripos($message, 'SMS failed') !== false) {
            return '❌';
        }
        if (stripos($message, 'SMS notification initiated') !== false) {
            return '📱';
        }
        if (stripos($message, 'SMS notification skipped') !== false) {
            return '⏭️';
        }
        if (stripos($message, 'SMS service') !== false) {
            return '🔧';
        }
        if (stripos($message, 'provider') !== false) {
            return '🌐';
        }
        if (stripos($message, 'error') !== false) {
            return '⚠️';
        }
        
        return '📝';
    }
} 