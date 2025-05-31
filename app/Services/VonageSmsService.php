<?php

namespace App\Services;

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class VonageSmsService extends BaseSmsService
{
    /**
     * The Vonage client instance
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Create a new Vonage SMS service instance
     */
    public function __construct()
    {
        parent::__construct();

        $this->client = new Client(
            new Basic(
                $this->config['key'],
                $this->config['secret']
            )
        );
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

            $response = $this->client->sms()->send(
                new SMS(
                    $to,
                    $this->config['from'],
                    $message
                )
            );

            $result = [
                'success' => true,
                'message_id' => $response->current()->getMessageId(),
                'to' => $to,
                'remaining_balance' => $response->current()->getRemainingBalance(),
            ];

            $this->logActivity('send', $result);

            return $result;
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
            $response = $this->client->account()->getBalance();
            
            $result = [
                'success' => true,
                'balance' => $response->getBalance(),
                'currency' => $response->getCurrency(),
            ];

            $this->logActivity('balance_check', $result);

            return $result;
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
            $response = $this->client->sms()->search($messageId);
            
            $result = [
                'success' => true,
                'message_id' => $messageId,
                'status' => $response->getStatus(),
                'price' => $response->getPrice(),
                'network' => $response->getNetwork(),
            ];

            $this->logActivity('status_check', $result);

            return $result;
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
        return 'vonage';
    }
} 