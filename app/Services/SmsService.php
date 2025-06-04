<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
     * @param string $type Message type (invitation, reminder, notification, etc.)
     * @param array $metadata Additional metadata to store with the log
     * @return bool Whether the message was sent successfully
     */
    public function send(string $to, string $message, string $type, array $metadata = []): bool
    {
        try {
            // Prepare the payload
            $payload = [
                'from' => config('services.nextsms.sender_id'),
                'to' => $to,
                'text' => $message,
                'reference' => Str::uuid()->toString()
            ];

            // Log the outgoing payload
            Log::info('Sending SMS', [
                'type' => $type,
                'payload' => $payload,
                'metadata' => $metadata
            ]);

            // Send the message
            $response = $this->smsProvider->send($payload);

            // Log the response
            Log::info('SMS Response', [
                'type' => $type,
                'reference' => $payload['reference'],
                'response' => $response,
                'metadata' => $metadata
            ]);

            // Create log entry
            $log = SmsLog::create([
                'type' => $type,
                'recipient' => $to,
                'message' => $message,
                'status' => $response['status'] ?? 'unknown',
                'provider' => $this->getProviderName(),
                'provider_reference' => $response['message_id'] ?? null,
                'metadata' => array_merge($metadata, [
                    'payload' => $payload,
                    'response' => $response
                ])
            ]);

            return ($response['status'] ?? '') === 'success';
        } catch (\Exception $e) {
            // Log the error with full context
            Log::error('SMS Send Error', [
                'type' => $type,
                'to' => $to,
                'message' => $message,
                'metadata' => $metadata,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Create error log entry
            SmsLog::create([
                'type' => $type,
                'recipient' => $to,
                'message' => $message,
                'status' => 'failed',
                'provider' => $this->getProviderName(),
                'metadata' => array_merge($metadata, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ])
            ]);

            throw $e;
        }
    }

    /**
     * Send an invitation SMS
     *
     * @param Ticket $ticket The ticket to send invitation for
     * @return bool Whether the message was sent successfully
     */
    public function sendInvitation(Ticket $ticket): bool
    {
        $attendee = $ticket->attendee;
        
        if (!$attendee || !$attendee->phone) {
            Log::warning('Cannot send invitation SMS - Missing attendee or phone number', [
                'ticket_id' => $ticket->id,
                'attendee_id' => $attendee?->id,
                'has_phone' => (bool)$attendee?->phone
            ]);
            throw new \Exception('Attendee or phone number not found');
        }

        $event = $ticket->event;
        $eventDate = Carbon::parse($event->date);
        $dayName = $eventDate->locale('sw')->isoFormat('dddd');
        $formattedTime = $eventDate->format('g:i');
        $period = $eventDate->format('A');
        $token = $ticket->ticket_number;
        $ticketType = $ticket->type;
        $attendeeName = $attendee->name;

        // Log the invitation details before sending
        Log::info('Preparing invitation SMS', [
            'ticket_id' => $ticket->id,
            'attendee_id' => $attendee->id,
            'event_id' => $event->id,
            'event_name' => $event->name,
            'event_date' => $eventDate->toDateTimeString(),
            'location' => $event->location,
            'venue' => $event->venue,
            'ticket_number' => $token,
            'ticket_type' => $ticketType
        ]);

        $message = "Ndugu {$attendeeName}, unakaribishwa kwenye {$event->name} itakayofanyika {$dayName} {$eventDate->toLocaleDateString('en-GB')} saa {$formattedTime} {$period}. Mahali: {$event->location}. Ukumbi: {$event->venue}. Tiketi: {$token} ({$ticketType}). Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824";

        return $this->send(
            $attendee->phone,
            $message,
            'invitation',
            [
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'ticket_id' => $ticket->id,
                'attendee_name' => $attendeeName,
                'attendee_phone' => $attendee->phone,
                'ticket_number' => $token,
                'ticket_type' => $ticketType,
                'event_name' => $event->name,
                'event_date' => $eventDate->toDateTimeString(),
                'location' => $event->location,
                'venue' => $event->venue
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
        // Format the date to include day name in Swahili
        $date = Carbon::parse($event->start_date);
        $dayNames = [
            'Monday' => 'Jumatatu',
            'Tuesday' => 'Jumanne',
            'Wednesday' => 'Jumatano',
            'Thursday' => 'Alhamisi',
            'Friday' => 'Ijumaa',
            'Saturday' => 'Jumamosi',
            'Sunday' => 'Jumapili'
        ];
        $dayName = $dayNames[$date->format('l')];

        // Format the time with AM/PM in Swahili
        $time = Carbon::parse($event->start_time);
        $timeStr = $time->format('h:i');
        $period = $time->format('A') === 'AM' ? 'asubuhi' : 'jioni';

        return sprintf(
            "Ndugu %s, unakaribishwa kwenye %s itakayofanyika %s %s saa %s %s katika %s. Tiketi: %s (%s). Tafadhali wasilisha tiketi hii mlangoni. Maelezo: %s",
            $ticket->attendee->name,
            $event->name,
            $dayName,
            $date->format('d/m/Y'),
            $timeStr,
            $period,
            $event->location,
            $ticket->token,
            strtoupper($ticket->type),
            $ticket->attendee->phone
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