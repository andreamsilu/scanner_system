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
        $this->baseUrl = $this->config['api_url'] ?? 'https://messaging-service.co.tz/api/sms/v1';
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

            // Generate a unique reference if not provided
            $reference = $options['reference'] ?? 'ref_' . uniqid();

            // Log the request for debugging
            Log::info('Sending SMS request', [
                'url' => $this->baseUrl . '/text/single',
                'to' => $to,
                'message' => $message,
                'sender_id' => $this->config['sender_id'],
                'reference' => $reference
            ]);

            // Format the API key for authorization
            $apiKey = base64_encode($this->config['api_key']);

            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'Basic ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->withOptions([
                    'verify' => true,
                    'connect_timeout' => 10,
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
                'headers' => $response->headers()
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
                    'auth_header' => 'Basic ' . substr($apiKey, 0, 10) . '...'
                ]);
                throw new \Exception($errorMessage);
            }

            $result = $response->json();
            
            $responseData = [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'to' => $to,
                'status' => $result['status'] ?? 'sent',
                'reference' => $reference,
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
                'url' => $this->baseUrl . '/text/single'
            ]);
            throw new \Exception('Failed to connect to SMS service. Please try again later.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('SMS request error', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message,
                'response' => $e->response ? [
                    'status' => $e->response->status(),
                    'body' => $e->response->body()
                ] : null
            ]);
            throw new \Exception('SMS service request failed. Please try again later.');
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message
            ]);
            throw $e;
        }
    }

    
    /**
     * Get the remaining SMS credits/units
     *
     * @return array Balance information containing remaining SMS credits
     */
    public function getBalance(): array
    {
        try {
            $apiKey = base64_encode($this->config['api_key']);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/balance');

            if (!$response->successful()) {
                Log::error('NextSMS balance check failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            $balanceData = [
                'success' => true,
                'credits' => (int)($result['balance'] ?? 0), // Number of SMS credits remaining
                'units' => (int)($result['units'] ?? 0),     // Number of SMS units (if different from credits)
                'type' => 'sms_credits',                     // Indicates this is SMS credits, not monetary balance
                'provider' => 'nextsms'
            ];

            $this->logActivity('balance_check', $balanceData);

            return $balanceData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SMS balance check connection error', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/balance'
            ]);
            throw new \Exception('Failed to connect to SMS service. Please try again later.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('SMS balance check request error', [
                'error' => $e->getMessage(),
                'response' => $e->response ? [
                    'status' => $e->response->status(),
                    'body' => $e->response->body()
                ] : null
            ]);
            throw new \Exception('SMS service request failed. Please try again later.');
        } catch (\Exception $e) {
            $this->logActivity('balance_check_error', [
                'error' => $e->getMessage(),
            ], 'error');
            throw $e;
        }
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
            $apiKey = base64_encode($this->config['api_key']);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/messages/' . $messageId);

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            $statusData = [
                'success' => true,
                'message_id' => $messageId,
                'status' => $result['status'] ?? 'unknown',
                'delivered_at' => $result['delivered_at'] ?? null,
                'error' => $result['error'] ?? null,
            ];

            $this->logActivity('status_check', $statusData);

            return $statusData;
        } catch (\Exception $e) {
            $this->logActivity('status_check_error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ], 'error');

            throw $e;
        }
    }

    /**
     * Map the provider's delivery status to our standardized status
     *
     * @param string $providerStatus The status from the provider
     * @return string Our standardized status
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
     * Get the sender ID configured for this SMS service
     *
     * @return string
     */
    public function getSenderId(): string
    {
        return $this->config['sender_id'] ?? '';
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
