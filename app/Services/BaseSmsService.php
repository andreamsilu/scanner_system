<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Traits\HasSmsLogging;
use Illuminate\Support\Facades\Config;

abstract class BaseSmsService implements SmsServiceInterface
{
    use HasSmsLogging;

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
     * Get the provider's configuration
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the sender ID from configuration
     *
     * @return string
     */
    public function getSenderId(): string
    {
        return $this->config['sender_id'] ?? config('services.sms.default_sender_id', 'EVENT');
    }

    /**
     * Validate the service configuration
     *
     * @throws \RuntimeException If configuration is invalid
     */
    protected function validateConfig(): void
    {
        $required = ['username', 'password', 'sender_id'];
        $missing = array_filter($required, fn($key) => empty($this->config[$key]));

        if (!empty($missing)) {
            throw new \RuntimeException(
                sprintf(
                    'Invalid SMS configuration for %s. Missing: %s',
                    $this->getProviderName(),
                    implode(', ', $missing)
                )
            );
        }
    }

    /**
     * Get the API credentials for Basic Auth
     *
     * @return string
     */
    protected function getBasicAuthCredentials(): string
    {
        return base64_encode($this->config['username'] . ':' . $this->config['password']);
    }

    /**
     * Get the default headers for API requests
     *
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . $this->getBasicAuthCredentials(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
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
    public function isValidPhoneNumber(string $number): bool
    {
        $number = $this->formatPhoneNumber($number);
        return preg_match('/^\+[1-9]\d{1,14}$/', $number) === 1;
    }
} 