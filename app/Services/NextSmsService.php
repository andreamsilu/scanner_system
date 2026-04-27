<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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
     * Send an SMS message
     *
     * @param string $to The recipient's phone number
     * @param string $message The message content
     * @param array $options Additional options for the SMS provider
     * @return array Response from the SMS provider
     * @throws \Exception When sending fails
     */
    public function send(string $to, string $message, array $options = []): array
    {
        try {
            // Validate credentials at action time so UI pages can still load
            // when SMS settings are not yet configured.
            $this->validateConfig();

            if (!$this->isValidPhoneNumber($to)) {
                throw new \InvalidArgumentException('Invalid phone number format');
            }

            $reference = $options['reference'] ?? 'ref_' . Str::uuid()->toString();
            $payload = [
                'from' => $this->getSenderId(),
                'to' => $this->formatPhoneNumber($to),
                'text' => $message,
                'reference' => $reference
            ];

            $this->logSmsActivity('send_attempt', [
                'recipient' => $to,
                'message' => $message,
                'payload' => $payload
            ]);

            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withHeaders($this->getDefaultHeaders())
                ->withOptions([
                    'verify' => true,
                    'connect_timeout' => 10,
                ])
                ->post($this->baseUrl . '/text/single', $payload);

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            $responseData = [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'recipient' => $to,
                'status' => $result['status'] ?? 'sent',
                'reference' => $reference,
                'cost' => $result['cost'] ?? null,
                'units' => $result['units'] ?? null,
                'provider_status' => $result['status'] ?? null,
                'provider_message' => $result['message'] ?? null
            ];

            $this->logSmsActivity('send_success', $responseData);

            return $responseData;
        } catch (\Exception $e) {
            return $this->handleSmsError($e, [
                'recipient' => $to,
                'message' => $message,
                'reference' => $reference ?? null,
                'payload' => $payload ?? null
            ]);
        }
    }

    /**
     * Get the remaining SMS credits/units
     *
     * @return array Balance information containing remaining SMS credits
     * @throws \Exception When balance check fails
     */
    public function getBalance(): array
    {
        try {
            $this->validateConfig();

            $response = Http::withHeaders($this->getDefaultHeaders())
                ->get($this->baseUrl . '/balance');

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            $balance = (int)($result['sms_balance'] ?? 0);
            
            $balanceData = [
                'success' => true,
                'credits' => $balance,
                'units' => $balance,
                'type' => 'sms_credits',
                'provider' => $this->getProviderName(),
                'raw_response' => $result
            ];

            $this->logSmsActivity('balance_check', $balanceData);

            return $balanceData;
        } catch (\Exception $e) {
            return $this->handleSmsError($e, ['action' => 'balance_check']);
        }
    }

    /**
     * Get the delivery status of a message
     *
     * @param string $messageId The message ID to check
     * @return array Delivery status information
     * @throws \Exception When status check fails
     */
    public function getStatus(string $messageId): array
    {
        try {
            $this->validateConfig();

            $response = Http::withHeaders($this->getDefaultHeaders())
                ->get($this->baseUrl . '/status/' . $messageId);

            if (!$response->successful()) {
                throw new \Exception('NextSMS API error: ' . $response->body());
            }

            $result = $response->json();
            
            $statusData = [
                'success' => true,
                'message_id' => $messageId,
                'status' => $this->mapDeliveryStatus($result['status'] ?? 'unknown'),
                'provider_status' => $result['status'] ?? 'unknown',
                'provider_message' => $result['message'] ?? null,
                'raw_response' => $result
            ];

            $this->logSmsActivity('status_check', $statusData);

            return $statusData;
        } catch (\Exception $e) {
            return $this->handleSmsError($e, [
                'message_id' => $messageId,
                'action' => 'status_check'
            ]);
        }
    }

    /**
     * Map provider status to standardized status
     *
     * @param string $providerStatus The status from the provider
     * @return string Standardized status
     */
    protected function mapDeliveryStatus(string $providerStatus): string
    {
        return match (strtolower($providerStatus)) {
            'delivered' => 'delivered',
            'failed', 'rejected' => 'failed',
            'pending', 'sent' => 'sent',
            default => 'unknown'
        };
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
