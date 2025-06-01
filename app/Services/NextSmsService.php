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

            // Log the request for debugging
            Log::info('Sending SMS request', [
                'url' => $this->baseUrl . '/text/single',
                'to' => $to,
                'message' => $message,
                'sender_id' => $this->config['sender_id']
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/text/single', [
                'recipient' => $this->formatPhoneNumber($to),
                'sender_id' => $this->config['sender_id'],
                'message' => $message,
                'type' => 'text'
            ]);

            // Log the response for debugging
            Log::info('SMS API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            $responseData = [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'to' => $to,
                'status' => $result['status'] ?? 'sent',
                'cost' => $result['cost'] ?? null,
                'units' => $result['units'] ?? null
            ];

            $this->logActivity('send', $responseData);

            return $responseData;
        } catch (\Exception $e) {
            $this->logActivity('send_error', [
                'error' => $e->getMessage(),
                'to' => $to,
            ], 'error');

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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/balance');

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            $balanceData = [
                'success' => true,
                'balance' => $result['balance'] ?? 0,
                'currency' => $result['currency'] ?? 'TZS',
            ];

            $this->logActivity('balance_check', $balanceData);

            return $balanceData;
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
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
     * Get the provider's name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'nextsms';
    }
} 