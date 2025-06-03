<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SmsService
{
    /**
     * The SMS provider instance
     *
     * @var SmsServiceInterface
     */
    protected SmsServiceInterface $smsProvider;

    /**
     * Create a new SMS service instance
     */
    public function __construct(SmsServiceInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string $type Type of message (invitation, notification, reminder)
     * @param array $metadata Additional metadata to store
     * @return array
     */
    public function send(string $to, string $message, string $type = 'notification', array $metadata = []): array
    {
        try {
            $result = $this->smsProvider->send($to, $message);

            // Create SMS log entry
            $log = SmsLog::create([
                'recipient' => $to,
                'message' => $message,
                'status' => $result['status'] ?? 'sent',
                'message_id' => $result['message_id'] ?? null,
                'metadata' => $metadata,
                'sender_id' => $this->smsProvider->getSenderId(),
                'reference' => $result['reference'] ?? null,
                'provider_status' => $result['provider_status'] ?? null,
                'provider_message' => $result['provider_message'] ?? null,
            ]);

            // Log to Laravel's logging system as well
            Log::channel('sms')->info('SMS sent', [
                'log_id' => $log->id,
                'recipient' => $to,
                'message_id' => $result['message_id'] ?? null,
                'metadata' => $metadata,
                'provider' => $this->smsProvider->getProviderName(),
            ]);

            return array_merge($result, ['log_id' => $log->id]);
        } catch (\Exception $e) {
            // Create error log entry
            $log = SmsLog::create([
                'recipient' => $to,
                'message' => $message,
                'status' => 'failed',
                'metadata' => $metadata,
                'sender_id' => $this->smsProvider->getSenderId(),
                'error' => $e->getMessage(),
            ]);

            // Log to Laravel's logging system
            Log::channel('sms')->error('SMS sending failed', [
                'log_id' => $log->id,
                'recipient' => $to,
                'error' => $e->getMessage(),
                'metadata' => $metadata,
                'provider' => $this->smsProvider->getProviderName(),
            ]);

            throw $e;
        }
    }

    /**
     * Send an invitation SMS
     *
     * @param Ticket $ticket The ticket to send invitation for
     * @return array
     */
    public function sendInvitation(Ticket $ticket): array
    {
        $event = $ticket->event;
        $message = $this->formatInvitationMessage($event, $ticket);

        return $this->send(
            $ticket->phone,
            $message,
            'invitation',
            [
                'ticket_id' => $ticket->id,
                'event_id' => $event->id,
            ]
        );
    }

    /**
     * Send a notification SMS
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param array $metadata Additional metadata
     * @return array
     */
    public function sendNotification(string $to, string $message, array $metadata = []): array
    {
        return $this->send($to, $message, 'notification', $metadata);
    }

    /**
     * Send a reminder SMS
     *
     * @param Ticket $ticket The ticket to send reminder for
     * @return array
     */
    public function sendReminder(Ticket $ticket): array
    {
        $event = $ticket->event;
        $message = $this->formatReminderMessage($event, $ticket);

        return $this->send(
            $ticket->phone,
            $message,
            'reminder',
            [
                'ticket_id' => $ticket->id,
                'event_id' => $event->id,
            ]
        );
    }

    /**
     * Format an invitation message
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @return string
     */
    protected function formatInvitationMessage(Event $event, Ticket $ticket): string
    {
        return strtr(
            "MWALIKO: {attendee_name}\n" .
            "SENDOFF ya ALICE PIENZI CHAMBILA\n" .
            "Tarehe: {event_date}\n" .
            "Saa: {event_time}\n" .
            "Mahali: {event_location}\n" .
            "Token: {ticket_token}",
            [
                '{attendee_name}' => strtoupper($ticket->attendee->name),
                '{event_date}' => Carbon::parse($event->date)->format('d/m/Y'),
                '{event_time}' => Carbon::parse($event->date)->format('h:i A'),
                '{event_location}' => $event->location,
                '{ticket_token}' => $ticket->token,
            ]
        );
    }

    /**
     * Format a reminder message
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @return string
     */
    protected function formatReminderMessage(Event $event, Ticket $ticket): string
    {
        return strtr(
            "Reminder: {event_name} is tomorrow! Date: {event_date}. Location: {event_location}. " .
            "Your ticket code: {ticket_code}. Visit {ticket_url} to view your ticket.",
            [
                '{event_name}' => $event->name,
                '{event_date}' => Carbon::parse($event->date)->format('F j, Y g:i A'),
                '{event_location}' => $event->location,
                '{ticket_code}' => $ticket->code,
                '{ticket_url}' => route('tickets.show', $ticket->code),
            ]
        );
    }

    /**
     * Get the SMS provider's balance
     *
     * @return array
     */
    public function getBalance(): array
    {
        return $this->smsProvider->getBalance();
    }

    /**
     * Get the status of a message
     *
     * @param string $messageId The message ID
     * @return array
     */
    public function getMessageStatus(string $messageId): array
    {
        return $this->smsProvider->getStatus($messageId);
    }

    /**
     * Get the provider's name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->smsProvider->getProviderName();
    }
} 