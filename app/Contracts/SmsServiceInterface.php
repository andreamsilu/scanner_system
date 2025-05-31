<?php

namespace App\Contracts;

interface SmsServiceInterface
{
    /**
     * Send an SMS message
     *
     * @param string $to The recipient's phone number
     * @param string $message The message content
     * @param array $options Additional options for the SMS provider
     * @return array Response from the SMS provider
     */
    public function send(string $to, string $message, array $options = []): array;

    /**
     * Get the remaining balance/credits
     *
     * @return array Balance information
     */
    public function getBalance(): array;

    /**
     * Get the delivery status of a message
     *
     * @param string $messageId The message ID to check
     * @return array Delivery status information
     */
    public function getStatus(string $messageId): array;

    /**
     * Get the provider's name
     *
     * @return string
     */
    public function getProviderName(): string;
} 