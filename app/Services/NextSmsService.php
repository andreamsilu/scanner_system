<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NextSmsService extends BaseSmsService
{
    /**
     * The NextSMS API base URL
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Create a new NextSMS service instance
     */
    public function __construct()
    {
        parent::__construct();
        
        // Log the full config for debugging (excluding sensitive data)
        Log::info('NextSMS Service Configuration', [
            'config_keys' => array_keys($this->config),
            'api_url_set' => !empty($this->config['api_url']),
            'username_set' => !empty($this->config['username']),
            'password_set' => !empty($this->config['password']),
            'sender_id_set' => !empty($this->config['sender_id']),
            'api_url' => $this->config['api_url'] ?? 'not_set',
            'sender_id' => $this->config['sender_id'] ?? 'not_set'
        ]);

        $this->baseUrl = $this->config['api_url'] ?? 'https://messaging-service.co.tz/api/sms/v1';

        // Validate required credentials with more detailed error
        if (empty($this->config['username'])) {
            Log::error('NextSMS username not configured', [
                'config_keys' => array_keys($this->config),
                'env_username' => env('NEXTSMS_USERNAME'),
                'config_username' => $this->config['username'] ?? 'not_set'
            ]);
            throw new \RuntimeException('NextSMS username is not properly configured. Please check your .env file and services config.');
        }

        if (empty($this->config['password'])) {
            Log::error('NextSMS password not configured', [
                'config_keys' => array_keys($this->config),
                'env_password_set' => !empty(env('NEXTSMS_PASSWORD')),
                'config_password_set' => !empty($this->config['password'])
            ]);
            throw new \RuntimeException('NextSMS password is not properly configured. Please check your .env file and services config.');
        }

        // Log the URLs being used (without sensitive data)
        Log::info('NextSMS Service initialized', [
            'base_url' => $this->baseUrl,
            'sender_id' => $this->config['sender_id'] ?? 'not_set',
            'default_country_code' => $this->config['default_country_code'] ?? '255',
            'username' => $this->config['username'] ?? 'not_set'
        ]);
    }

    /**
     * Format the API credentials for Basic authentication using RFC2045-MIME Base64
     * 
     * @return string
     * @throws \RuntimeException if credentials are not properly configured
     */
    protected function getBasicAuthHeader(): string
    {
        // Log credential status before attempting to use them
        Log::debug('Preparing Basic Auth Header', [
            'username_set' => !empty($this->config['username']),
            'password_set' => !empty($this->config['password']),
            'username_length' => strlen($this->config['username'] ?? ''),
            'password_length' => strlen($this->config['password'] ?? '')
        ]);

        if (empty($this->config['username']) || empty($this->config['password'])) {
            Log::error('NextSMS credentials missing in getBasicAuthHeader', [
                'username_set' => !empty($this->config['username']),
                'password_set' => !empty($this->config['password']),
                'config_keys' => array_keys($this->config)
            ]);
            throw new \RuntimeException('NextSMS credentials (username and password) are not properly configured');
        }

        // Format: username:password
        $credentials = $this->config['username'] . ':' . $this->config['password'];
        
        // RFC2045-MIME variant of Base64 encoding
        // - Uses + and / for 62 and 63
        // - Pads with = if needed
        // - No line breaks
        $encoded = base64_encode($credentials);
        
        // Remove any line breaks that might have been added
        $encoded = str_replace(["\r", "\n"], '', $encoded);
        
        return 'Basic ' . $encoded;
    }

    /**
     * Send an SMS message
     *
     * @param string $to The recipient's phone number
     * @param string $message The message content
     * @param array $options Additional options for the SMS provider
     * @return array Response from the SMS provider
     */
    public function send(string $to, string $message, array $options = []): array
    {
        try {
            if (!$this->isValidPhoneNumber($to)) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }

            $authHeader = $this->getBasicAuthHeader();
            
            // Generate a unique reference for the message
            $reference = $options['reference'] ?? 'msg_' . uniqid() . '_' . time();
            
            // Log the request for debugging (without sensitive data)
            Log::info('Sending SMS request', [
                'url' => $this->baseUrl . '/text/single',
                'to' => $to,
                'message' => $message,
                'sender_id' => $this->config['sender_id'],
                'username' => $this->config['username'] ?? 'not_set',
                'reference' => $reference,
                'auth_header_format' => 'Basic RFC2045-MIME'
            ]);

            $response = Http::timeout(30) // Increase timeout to 30 seconds
                ->retry(3, 100) // Retry 3 times with 100ms delay between attempts
                ->withHeaders([
                    'Authorization' => $authHeader,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->withOptions([
                    'verify' => true, // Verify SSL certificate
                    'connect_timeout' => 10, // Connection timeout in seconds
                ])
                ->post($this->baseUrl . '/text/single', [
                    'from' => $this->config['sender_id'],
                    'to' => $this->formatPhoneNumber($to),
                    'text' => $message,
                    'reference' => $reference
                ]);

            // Log the response for debugging
            Log::info('SMS API response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
                'reference' => $reference
            ]);

            if (!$response->successful()) {
                $errorMessage = 'NextSMS API error: ' . $response->body();
                Log::error('SMS API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                    'to' => $to,
                    'message' => $message,
                    'reference' => $reference,
                    'auth_type' => 'Basic RFC2045-MIME',
                    'username' => $this->config['username'] ?? 'not_set'
                ]);
                throw new \Exception($errorMessage);
            }

            $result = $response->json();
            
            $responseData = [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'reference' => $reference,
                'to' => $to,
                'status' => $result['status'] ?? 'sent',
                'cost' => $result['cost'] ?? null,
                'units' => $result['units'] ?? null
            ];

            $this->logActivity('send', $responseData);

            return $responseData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SMS connection error', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message,
                'reference' => $reference ?? null,
                'url' => $this->baseUrl . '/text/single',
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ]);
            throw new \Exception('Failed to connect to SMS service. Please try again later.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('SMS request error', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message,
                'reference' => $reference ?? null,
                'response' => $e->response ? [
                    'status' => $e->response->status(),
                    'body' => $e->response->body()
                ] : null,
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ]);
            throw new \Exception('SMS service request failed. Please try again later.');
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message,
                'reference' => $reference ?? null,
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ]);
            throw $e;
        }
    }

    /**
     * Get the remaining balance/credits
     *
     * @return array Balance information
     */
    public function getBalance(): array
    {
        try {
            $authHeader = $this->getBasicAuthHeader();
            
            // Log the balance check request (without sensitive data)
            Log::info('Checking SMS balance', [
                'url' => $this->baseUrl . '/balance',
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ]);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/balance');

            // Log the response for debugging
            Log::info('SMS balance response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            // Handle the simple response format with sms_balance
            $units = (int)($result['sms_balance'] ?? 0);
            
            $balanceData = [
                'success' => true,
                'units' => $units,
                'last_updated' => now()->toIso8601String(),
                'status' => $this->determineBalanceStatus($units),
                'provider' => $this->getProviderName()
            ];

            $this->logActivity('balance_check', $balanceData);

            return $balanceData;
        } catch (\Exception $e) {
            $this->logActivity('balance_check_error', [
                'error' => $e->getMessage(),
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ], 'error');

            throw $e;
        }
    }

    /**
     * Determine the status of the SMS balance based on available units
     * 
     * @param int $units Number of SMS units available
     * @return string Status of the balance (critical, low, normal)
     */
    protected function determineBalanceStatus(int $units): string
    {
        if ($units <= 0) {
            return 'critical';
        } elseif ($units <= 10) {
            return 'low';
        }
        return 'normal';
    }

    /**
     * Get the delivery status of a message
     *
     * @param string $messageId The message ID to check
     * @return array Delivery status information
     */
    public function getStatus(string $messageId): array
    {
        try {
            $authHeader = $this->getBasicAuthHeader();
            
            // Log the status check request
            Log::info('Checking SMS delivery status', [
                'url' => $this->baseUrl . '/messages/' . $messageId,
                'message_id' => $messageId,
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ]);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/messages/' . $messageId);

            // Log the response for debugging
            Log::info('SMS status response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            // Map API status to our internal status
            $status = $this->mapDeliveryStatus($result['status'] ?? 'unknown');
            
            $statusData = [
                'success' => true,
                'message_id' => $messageId,
                'status' => $status,
                'delivered_at' => $result['delivered_at'] ?? null,
                'error' => $result['error'] ?? null,
                'provider_status' => $result['status'] ?? 'unknown',
                'provider_message' => $result['message'] ?? null,
                'last_updated' => now()->toIso8601String()
            ];

            $this->logActivity('status_check', $statusData);

            return $statusData;
        } catch (\Exception $e) {
            $this->logActivity('status_check_error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'auth_type' => 'Basic RFC2045-MIME',
                'username' => $this->config['username'] ?? 'not_set'
            ], 'error');

            throw $e;
        }
    }

    /**
     * Map provider status to internal status
     *
     * @param string $providerStatus The status from the provider
     * @return string Mapped internal status
     */
    protected function mapDeliveryStatus(string $providerStatus): string
    {
        $statusMap = [
            'delivered' => 'delivered',
            'sent' => 'sent',
            'failed' => 'failed',
            'pending' => 'sent',
            'queued' => 'sent',
            'rejected' => 'failed',
            'undelivered' => 'failed',
            'expired' => 'failed',
            'unknown' => 'unknown'
        ];

        return $statusMap[strtolower($providerStatus)] ?? 'unknown';
    }

    /**
     * Get the provider's name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'nextsms';
    }
} 