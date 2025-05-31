<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

abstract class BaseSmsService implements SmsServiceInterface
{
    /**
     * The SMS provider configuration
     *
     * @var array
     */
    protected array $config;

    /**
     * Create a new SMS service instance
     */
    public function __construct()
    {
        $this->config = Config::get('services.sms.' . $this->getProviderName(), []);
    }

    /**
     * Log SMS activity
     *
     * @param string $action The action being performed
     * @param array $data The data to log
     * @param string $level The log level (info, error, etc.)
     * @return void
     */
    protected function logActivity(string $action, array $data, string $level = 'info'): void
    {
        $logData = array_merge([
            'provider' => $this->getProviderName(),
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ], $data);

        Log::channel('sms')->$level(json_encode($logData));
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
            $number = '+' . $this->config['default_country_code'] . $number;
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

    /**
     * Get the provider's configuration
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return $this->config;
    }
} 