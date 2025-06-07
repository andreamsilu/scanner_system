<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Models\Event;
use App\Models\Ticket;
use App\Traits\HasSmsLogging;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SmsService
{
    use HasSmsLogging;

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
     * @return array Response data including success status and log ID
     */
    public function send(string $to, string $message, string $type, array $metadata = []): array
    {
        try {
            $reference = Str::uuid()->toString();
            $payload = [
                'from' => $this->smsProvider->getSenderId(),
                'to' => $to,
                'text' => $message,
                'reference' => $reference
            ];

            $this->logSmsActivity('send_attempt', [
                'recipient' => $to,
                'message' => $message,
                'type' => $type,
                'payload' => $payload,
                'metadata' => $metadata
            ]);

            $response = $this->smsProvider->send($to, $message, [
                'reference' => $reference,
                'type' => $type
            ]);

            $logData = array_merge($response, [
                'type' => $type,
                'metadata' => array_merge($metadata, [
                    'payload' => $payload,
                    'response' => $response
                ])
            ]);

            $this->logSmsActivity('send_success', $logData);

            return [
                'success' => $response['success'] ?? false,
                'log_id' => $response['log_id'] ?? null,
                'message_id' => $response['message_id'] ?? null,
                'reference' => $reference
            ];
        } catch (\Exception $e) {
            return $this->handleSmsError($e, [
                'recipient' => $to,
                'message' => $message,
                'type' => $type,
                'metadata' => $metadata
            ]);
        }
    }

    /**
     * Send an invitation SMS
     *
     * @param Ticket $ticket The ticket to send invitation for
     * @return array Response data including success status
     * @throws \Exception When required data is missing
     */
    public function sendInvitation(Ticket $ticket): array
    {
        $attendee = $ticket->attendee;
        
        if (!$attendee || !$attendee->phone) {
            throw new \Exception('Attendee or phone number not found');
        }

        $event = $ticket->event;
        $eventDate = Carbon::parse($event->date);
        $message = $this->formatInvitationMessage($event, $ticket);

        $metadata = [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'ticket_id' => $ticket->id,
            'attendee_name' => $attendee->name,
            'attendee_phone' => $attendee->phone,
            'ticket_number' => $ticket->ticket_number,
            'ticket_type' => $ticket->type->name,
            'event_name' => $event->name,
            'event_date' => $eventDate->toDateTimeString(),
            'location' => $event->location,
            'venue' => $event->venue
        ];

        return $this->send($attendee->phone, $message, 'invitation', $metadata);
    }

    /**
     * Send a reminder SMS
     *
     * @param Ticket $ticket The ticket to send reminder for
     * @return array Response data including success status
     * @throws \Exception When required data is missing
     */
    public function sendReminder(Ticket $ticket): array
    {
        $attendee = $ticket->attendee;
        
        if (!$attendee || !$attendee->phone) {
            throw new \Exception('Attendee or phone number not found');
        }

        $event = $ticket->event;
        $eventDate = Carbon::parse($event->date);
        $message = $this->formatReminderMessage($event, $ticket);

        $metadata = [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'ticket_id' => $ticket->id,
            'attendee_name' => $attendee->name,
            'attendee_phone' => $attendee->phone,
            'ticket_number' => $ticket->ticket_number,
            'ticket_type' => $ticket->type->name,
            'event_name' => $event->name,
            'event_date' => $eventDate->toDateTimeString(),
            'location' => $event->location,
            'venue' => $event->venue
        ];

        return $this->send($attendee->phone, $message, 'reminder', $metadata);
    }

    /**
     * Format an invitation message
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @return string Formatted message
     */
    protected function formatInvitationMessage(Event $event, Ticket $ticket): string
    {
        $attendee = $ticket->attendee;
        $eventDate = Carbon::parse($event->date);
        $dayName = $eventDate->locale('sw')->isoFormat('dddd');
        $formattedTime = $eventDate->format('g:i');
        $period = $eventDate->format('A');

        return sprintf(
            "Ndugu %s, unakaribishwa kwenye %s itakayofanyika %s %s saa %s %s. Mahali: %s. Ukumbi: %s. Tiketi: %s (%s). Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824",
            $attendee->name,
            $event->name,
            $dayName,
            $eventDate->toLocaleDateString('en-GB'),
            $formattedTime,
            $period,
            $event->location,
            $event->venue,
            $ticket->ticket_number,
            $ticket->type->name
        );
    }

    /**
     * Format a reminder message
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @return string Formatted message
     */
    protected function formatReminderMessage(Event $event, Ticket $ticket): string
    {
        $attendee = $ticket->attendee;
        $eventDate = Carbon::parse($event->date);
        $dayName = $eventDate->locale('sw')->isoFormat('dddd');
        $formattedTime = $eventDate->format('g:i');
        $period = $eventDate->format('A');

        return sprintf(
            "Kumbusho: Ndugu %s, tukio la %s litafanyika %s %s saa %s %s. Mahali: %s. Ukumbi: %s. Tiketi: %s. Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824",
            $attendee->name,
            $event->name,
            $dayName,
            $eventDate->toLocaleDateString('en-GB'),
            $formattedTime,
            $period,
            $event->location,
            $event->venue,
            $ticket->ticket_number
        );
    }

    /**
     * Get the remaining SMS credits/units
     *
     * @return array Balance information
     */
    public function getBalance(): array
    {
        return $this->smsProvider->getBalance();
    }

    /**
     * Get the delivery status of a message
     *
     * @param string $messageId The message ID to check
     * @return array Delivery status information
     */
    public function getStatus(string $messageId): array
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