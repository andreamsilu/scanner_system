<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
     * Send an SMS message (Simulated)
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

            // Log the simulated request
            Log::info('Simulated NextSMS request', [
                'to' => $to,
                'message' => $message,
                'sender_id' => $this->config['sender_id'],
                'reference' => $reference,
                'simulated' => true
            ]);

            // Simulate API delay
            sleep(1);

            // Generate mock response
            $responseData = [
                'success' => true,
                'message_id' => 'MOCK_' . Str::random(10),
                'to' => $to,
                'status' => [
                    'name' => 'DELIVERED',
                    'groupName' => 'DELIVERED',
                    'description' => 'Message delivered successfully (Simulated)'
                ],
                'reference' => $reference,
                'cost' => 1,
                'units' => 1
            ];

            $this->logActivity('send', $responseData);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('Simulated SMS sending failed', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message,
                'simulated' => true
            ]);
            throw $e;
        }
    }

    /**
     * Get the remaining SMS credits/units (Simulated)
     *
     * @return array Balance information containing remaining SMS credits
     */
    public function getBalance(): array
    {
        try {
            // Simulate API delay
            sleep(1);
            
            // Return mock balance
            $balanceData = [
                'success' => true,
                'credits' => 1000,
                'units' => 1000,
                'type' => 'sms_credits',
                'provider' => 'nextsms',
                'simulated' => true
            ];

            $this->logActivity('balance_check', $balanceData);

            return $balanceData;
        } catch (\Exception $e) {
            Log::error('Simulated SMS balance check error', [
                'error' => $e->getMessage(),
                'simulated' => true
            ]);
            throw $e;
        }
    }

    /**
     * Get the delivery status of a message (Simulated)
     *
     * @param string $messageId The message ID to check
     * @return array Delivery status information
     */
    public function getStatus(string $messageId): array
    {
        try {
            // Simulate API delay
            sleep(1);
            
            // Return mock status
            $statusData = [
                'success' => true,
                'message_id' => $messageId,
                'status' => [
                    'name' => 'DELIVERED',
                    'groupName' => 'DELIVERED',
                    'description' => 'Message delivered successfully (Simulated)'
                ],
                'delivered_at' => now()->toIso8601String(),
                'simulated' => true
            ];

            $this->logActivity('status_check', $statusData);

            return $statusData;
        } catch (\Exception $e) {
            Log::error('Simulated SMS status check error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'simulated' => true
            ]);
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
