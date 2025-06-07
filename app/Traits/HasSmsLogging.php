<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use App\Models\SmsLog;

trait HasSmsLogging
{
    /**
     * Log SMS activity with consistent formatting
     *
     * @param string $action The action being performed
     * @param array $data The data to log
     * @param string $level The log level (info, error, etc.)
     * @return void
     */
    protected function logSmsActivity(string $action, array $data, string $level = 'info'): void
    {
        $logData = array_merge([
            'provider' => $this->getProviderName(),
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
            'sender_id' => $this->getSenderId()
        ], $data);

        // Log to Laravel's logging system
        Log::channel('sms')->$level(json_encode($logData));

        // Log to database if it's a send action
        if ($action === 'send' && isset($data['recipient'])) {
            $this->createSmsLogEntry($logData);
        }
    }

    /**
     * Handle SMS errors consistently
     *
     * @param \Exception $e The exception that occurred
     * @param array $context Additional context for the error
     * @return array Error response data
     */
    protected function handleSmsError(\Exception $e, array $context = []): array
    {
        $logData = array_merge([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'provider' => $this->getProviderName(),
            'timestamp' => now()->toIso8601String()
        ], $context);

        // Log the error
        Log::error('SMS Error', $logData);
        $this->logSmsActivity('error', $logData, 'error');

        // Create error log entry if we have recipient information
        if (isset($context['recipient'])) {
            $this->createSmsLogEntry(array_merge($logData, [
                'status' => 'failed',
                'error' => $e->getMessage()
            ]));
        }

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'provider' => $this->getProviderName(),
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Create an SMS log entry in the database
     *
     * @param array $data The log data
     * @return SmsLog
     */
    protected function createSmsLogEntry(array $data): SmsLog
    {
        return SmsLog::create([
            'recipient' => $data['recipient'] ?? null,
            'sender_id' => $data['sender_id'] ?? $this->getSenderId(),
            'message' => $data['message'] ?? null,
            'status' => $data['status'] ?? 'unknown',
            'provider_status' => $data['provider_status'] ?? null,
            'provider_message' => $data['provider_message'] ?? null,
            'message_id' => $data['message_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'error' => $data['error'] ?? null,
            'metadata' => array_merge(
                $data['metadata'] ?? [],
                [
                    'provider' => $this->getProviderName(),
                    'action' => $data['action'] ?? 'unknown',
                    'timestamp' => $data['timestamp'] ?? now()->toIso8601String()
                ]
            )
        ]);
    }

    /**
     * Format a phone number to E.164 format
     *
     * @param string $number The phone number to format
     * @return string
     */
    protected function formatPhoneNumber(string $number): string
    {
        // Remove any non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        // If number doesn't start with +, add the default country code
        if (!str_starts_with($number, '+')) {
            $number = '+' . config('services.sms.default_country_code', '255') . $number;
        }

        return $number;
    }

    /**
     * Validate a phone number
     *
     * @param string $number The phone number to validate
     * @return bool
     */
    protected function isValidPhoneNumber(string $number): bool
    {
        $number = $this->formatPhoneNumber($number);
        return preg_match('/^\+[1-9]\d{1,14}$/', $number) === 1;
    }
} 