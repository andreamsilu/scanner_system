<?php

namespace App\Contracts;

interface SmsServiceInterface
{
    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param array $options Additional options for the SMS provider
     * @return array Response data including success status and message ID
     * @throws \Exception When sending fails
     */
    public function send(string $to, string $message, array $options = []): array;

    /**
     * Get the remaining SMS credits/units
     *
     * @return array Balance information containing remaining SMS credits
     * @throws \Exception When balance check fails
     */
    public function getBalance(): array;

    /**
     * Get the delivery status of a message
     *
     * @param string $messageId The message ID to check
     * @return array Delivery status information
     * @throws \Exception When status check fails
     */
    public function getStatus(string $messageId): array;

    /**
     * Get the provider's name
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Get the sender ID
     *
     * @return string
     */
    public function getSenderId(): string;

    /**
     * Validate a phone number
     *
     * @param string $number The phone number to validate
     * @return bool
     */
    public function isValidPhoneNumber(string $number): bool;
} 