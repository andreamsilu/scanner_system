<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Traits\HasSmsLogging;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
     * Format a phone number to 255712345678 format for SMS sending
     *
     * @param string $number The phone number to format
     * @return string
     */
    protected function formatPhoneNumber(string $number): string
    {
        $originalNumber = $number;
        
        // Remove any non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        // Log the formatting process
        Log::info('Phone number formatting initiated', [
            'original_number' => $originalNumber,
            'cleaned_number' => $number,
            'number_length' => strlen($number),
            'provider' => $this->getProviderName(),
            'timestamp' => now()->toIso8601String()
        ]);

        // Handle different input formats
        if (strlen($number) === 0) {
            Log::error('Phone number formatting failed - empty number after cleaning', [
                'original_number' => $originalNumber,
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            throw new \InvalidArgumentException('Invalid phone number: empty after cleaning');
        }

        // If number starts with 255 and has 12 digits total (255 + 9 digits), it's already in correct format
        if (strlen($number) === 12 && str_starts_with($number, '255')) {
            Log::info('Phone number already in correct format', [
                'original_number' => $originalNumber,
                'formatted_number' => $number,
                'format' => '255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $number;
        }

        // If number starts with 255 but has more than 12 digits, truncate to 12
        if (str_starts_with($number, '255') && strlen($number) > 12) {
            $formattedNumber = substr($number, 0, 12);
            Log::info('Phone number truncated to 12 digits', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'truncated_from' => strlen($number),
                'truncated_to' => 12,
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // If number starts with 0, remove the 0 and add 255
        if (str_starts_with($number, '0')) {
            $formattedNumber = '255' . substr($number, 1);
            Log::info('Phone number formatted from 0XXXXXXXXX to 255XXXXXXXXX', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'format_change' => '0XXXXXXXXX -> 255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // If number has 9 digits (Tanzania mobile number without country code), add 255
        if (strlen($number) === 9) {
            $formattedNumber = '255' . $number;
            Log::info('Phone number formatted from 9 digits to 255XXXXXXXXX', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'format_change' => 'XXXXXXXXX -> 255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // If number has 10 digits and doesn't start with 0, assume it's already a local number and add 255
        if (strlen($number) === 10 && !str_starts_with($number, '0')) {
            $formattedNumber = '255' . $number;
            Log::info('Phone number formatted from 10 digits to 255XXXXXXXXX', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'format_change' => 'XXXXXXXXXX -> 255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // If number has 11 digits and starts with 255, it's already correct
        if (strlen($number) === 11 && str_starts_with($number, '255')) {
            Log::info('Phone number already in correct format (11 digits)', [
                'original_number' => $originalNumber,
                'formatted_number' => $number,
                'format' => '255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $number;
        }

        // If number has 11 digits but doesn't start with 255, assume it's a local number and add 255
        if (strlen($number) === 11 && !str_starts_with($number, '255')) {
            $formattedNumber = '255' . $number;
            Log::info('Phone number formatted from 11 digits to 255XXXXXXXXX', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'format_change' => 'XXXXXXXXXXX -> 255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // If number starts with 255 but has less than 12 digits, pad with zeros or handle accordingly
        if (str_starts_with($number, '255') && strlen($number) < 12) {
            $formattedNumber = str_pad($number, 12, '0', STR_PAD_RIGHT);
            Log::warning('Phone number padded with zeros to reach 12 digits', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'padded_from' => strlen($number),
                'padded_to' => 12,
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // Default case: try to add 255 if the number doesn't already have it
        if (!str_starts_with($number, '255')) {
            $formattedNumber = '255' . $number;
            Log::info('Phone number formatted with default 255 prefix', [
                'original_number' => $originalNumber,
                'cleaned_number' => $number,
                'formatted_number' => $formattedNumber,
                'format_change' => 'default -> 255XXXXXXXXX',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return $formattedNumber;
        }

        // If we get here, return the number as is
        Log::info('Phone number returned as is (no formatting needed)', [
            'original_number' => $originalNumber,
            'formatted_number' => $number,
            'provider' => $this->getProviderName(),
            'timestamp' => now()->toIso8601String()
        ]);
        return $number;
    }

    /**
     * Validate a phone number in 255712345678 format
     *
     * @param string $number The phone number to validate
     * @return bool
     */
    public function isValidPhoneNumber(string $number): bool
    {
        $originalNumber = $number;
        
        try {
            // First, format the number to see what we get
            $formattedNumber = $this->formatPhoneNumber($number);
            
            // Validate the formatted number
            $isValid = $this->validateFormattedNumber($formattedNumber);
            
            // Log validation result
            Log::info('Phone number validation completed', [
                'original_number' => $originalNumber,
                'formatted_number' => $formattedNumber,
                'is_valid' => $isValid,
                'validation_rule' => '255XXXXXXXXX format',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            
            return $isValid;
            
        } catch (\Exception $e) {
            // Log validation error
            Log::error('Phone number validation failed with exception', [
                'original_number' => $originalNumber,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            
            return false;
        }
    }

    /**
     * Validate a formatted phone number (255712345678 format)
     *
     * @param string $number The formatted phone number to validate
     * @return bool
     */
    protected function validateFormattedNumber(string $number): bool
    {
        // Must be exactly 12 digits
        if (strlen($number) !== 12) {
            Log::warning('Phone number validation failed - wrong length', [
                'number' => $number,
                'length' => strlen($number),
                'expected_length' => 12,
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return false;
        }

        // Must start with 255
        if (!str_starts_with($number, '255')) {
            Log::warning('Phone number validation failed - does not start with 255', [
                'number' => $number,
                'prefix' => substr($number, 0, 3),
                'expected_prefix' => '255',
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return false;
        }

        // Must contain only digits
        if (!ctype_digit($number)) {
            Log::warning('Phone number validation failed - contains non-digits', [
                'number' => $number,
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return false;
        }

        // Validate Tanzania mobile number patterns (255 + 9 digits starting with 6, 7, or 8)
        $mobilePrefix = substr($number, 3, 1);
        if (!in_array($mobilePrefix, ['6', '7', '8'])) {
            Log::warning('Phone number validation failed - invalid mobile prefix', [
                'number' => $number,
                'mobile_prefix' => $mobilePrefix,
                'valid_prefixes' => ['6', '7', '8'],
                'provider' => $this->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);
            return false;
        }

        // Log successful validation
        Log::info('Phone number validation passed', [
            'number' => $number,
            'mobile_prefix' => $mobilePrefix,
            'format' => '255XXXXXXXXX',
            'provider' => $this->getProviderName(),
            'timestamp' => now()->toIso8601String()
        ]);

        return true;
    }
} 