<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Models\Event;
use App\Models\Ticket;
use App\Traits\HasSmsLogging;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        $reference = Str::uuid()->toString();
        $startTime = microtime(true);
        
        try {
            // Log SMS send attempt initiation
            Log::info('SMS service send attempt initiated', [
                'recipient' => $to,
                'type' => $type,
                'message_length' => strlen($message),
                'reference' => $reference,
                'metadata_count' => count($metadata),
                'provider' => $this->smsProvider->getProviderName(),
                'timestamp' => now()->toIso8601String()
            ]);

            // Prepare payload
            $payload = [
                'from' => $this->smsProvider->getSenderId(),
                'to' => $to,
                'text' => $message,
                'reference' => $reference
            ];

            // Log payload preparation
            Log::info('SMS payload prepared', [
                'recipient' => $to,
                'sender_id' => $payload['from'],
                'reference' => $reference,
                'message_preview' => substr($message, 0, 50) . '...',
                'payload_keys' => array_keys($payload),
                'timestamp' => now()->toIso8601String()
            ]);

            // Log SMS activity attempt
            $this->logSmsActivity('send_attempt', [
                'recipient' => $to,
                'message' => $message,
                'type' => $type,
                'payload' => $payload,
                'metadata' => $metadata
            ]);

            // Log provider call attempt
            Log::info('Attempting to call SMS provider', [
                'recipient' => $to,
                'provider' => $this->smsProvider->getProviderName(),
                'provider_class' => get_class($this->smsProvider),
                'reference' => $reference,
                'timestamp' => now()->toIso8601String()
            ]);

            // Call SMS provider
            $response = $this->smsProvider->send($to, $message, [
                'reference' => $reference,
                'type' => $type
            ]);

            // Log provider response
            Log::info('SMS provider response received', [
                'recipient' => $to,
                'provider' => $this->smsProvider->getProviderName(),
                'success' => $response['success'] ?? false,
                'message_id' => $response['message_id'] ?? null,
                'status' => $response['status'] ?? null,
                'reference' => $reference,
                'response_keys' => array_keys($response),
                'timestamp' => now()->toIso8601String()
            ]);

            // Prepare log data
            $logData = array_merge($response, [
                'type' => $type,
                'metadata' => array_merge($metadata, [
                    'payload' => $payload,
                    'response' => $response,
                    'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ])
            ]);

            // Log SMS activity success
            $this->logSmsActivity('send_success', $logData);

            // Log final success
            Log::info('SMS service send completed successfully', [
                'recipient' => $to,
                'type' => $type,
                'success' => $response['success'] ?? false,
                'message_id' => $response['message_id'] ?? null,
                'reference' => $reference,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'attendee_id' => $metadata['attendee_id'] ?? null,
                'ticket_id' => $metadata['ticket_id'] ?? null,
                'event_id' => $metadata['event_id'] ?? null,
                'validation_type' => $metadata['validation_type'] ?? null,
                'entry_point' => $metadata['entry_point'] ?? null,
                'timestamp' => now()->toIso8601String()
            ]);

            return [
                'success' => $response['success'] ?? false,
                'log_id' => $response['log_id'] ?? null,
                'message_id' => $response['message_id'] ?? null,
                'reference' => $reference
            ];
        } catch (\Exception $e) {
            // Log error details
            Log::error('SMS service send failed with exception', [
                'recipient' => $to,
                'type' => $type,
                'reference' => $reference,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'attendee_id' => $metadata['attendee_id'] ?? null,
                'ticket_id' => $metadata['ticket_id'] ?? null,
                'event_id' => $metadata['event_id'] ?? null,
                'validation_type' => $metadata['validation_type'] ?? null,
                'entry_point' => $metadata['entry_point'] ?? null,
                'timestamp' => now()->toIso8601String()
            ]);

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

    /**
     * Send a ticket usage notification SMS
     *
     * @param Ticket $ticket The ticket that was used
     * @param string $entryPoint The entry point where the ticket was scanned
     * @return array Response data including success status
     * @throws \Exception When required data is missing
     */
    public function sendTicketUsageNotification(Ticket $ticket, string $entryPoint = 'Main Gate'): array
    {
        $attendee = $ticket->attendee;
        
        if (!$attendee || !$attendee->phone) {
            throw new \Exception('Attendee or phone number not found');
        }

        $event = $ticket->event;
        $message = $this->formatTicketUsageMessage($event, $ticket, $entryPoint);

        $metadata = [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'ticket_id' => $ticket->id,
            'attendee_name' => $attendee->name,
            'attendee_phone' => $attendee->phone,
            'ticket_number' => $ticket->ticket_number,
            'ticket_type' => $ticket->type,
            'event_name' => $event->name,
            'entry_point' => $entryPoint,
            'scanned_at' => now()->toDateTimeString()
        ];

        return $this->send($attendee->phone, $message, 'ticket_used', $metadata);
    }

    /**
     * Send an event reminder SMS
     *
     * @param Ticket $ticket The ticket to send reminder for
     * @return array Response data including success status
     * @throws \Exception When required data is missing
     */
    public function sendEventReminder(Ticket $ticket): array
    {
        return $this->sendReminder($ticket);
    }

    /**
     * Send an attendee invitation SMS
     *
     * @param Event $event The event
     * @param array $attendeeData The attendee data
     * @return array Response data including success status
     * @throws \Exception When required data is missing
     */
    public function sendAttendeeInvitation(Event $event, array $attendeeData): array
    {
        if (empty($attendeeData['phone'])) {
            throw new \Exception('Phone number is required');
        }

        $message = $this->formatAttendeeInvitationMessage($event, $attendeeData);

        $metadata = [
            'event_id' => $event->id,
            'attendee_id' => $attendeeData['attendee_id'] ?? null,
            'ticket_id' => $attendeeData['ticket_id'] ?? null,
            'attendee_name' => $attendeeData['user_name'] ?? 'Guest',
            'attendee_phone' => $attendeeData['phone'],
            'ticket_type' => $attendeeData['ticket_type'] ?? 'regular',
            'event_name' => $event->name,
            'event_date' => $event->date ? Carbon::parse($event->date)->toDateTimeString() : null,
            'location' => $event->location,
            'venue' => $event->venue
        ];

        return $this->send($attendeeData['phone'], $message, 'attendee_invitation', $metadata);
    }

    /**
     * Format a ticket usage notification message
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @param string $entryPoint The entry point
     * @return string Formatted message
     */
    protected function formatTicketUsageMessage(Event $event, Ticket $ticket, string $entryPoint): string
    {
        $attendee = $ticket->attendee;
        $scannedTime = now()->format('g:i A');

        return sprintf(
            "Ndugu %s, tiketi yako ya %s imethibitishwa na kuingia kupitia %s saa %s. Asante kwa kuhudhuria %s. Karibu tena!",
            $attendee->name,
            $event->name,
            $entryPoint,
            $scannedTime,
            $event->name
        );
    }

    /**
     * Format an attendee invitation message
     *
     * @param Event $event The event
     * @param array $attendeeData The attendee data
     * @return string Formatted message
     */
    protected function formatAttendeeInvitationMessage(Event $event, array $attendeeData): string
    {
        $attendeeName = $attendeeData['user_name'] ?? 'Guest';
        $ticketType = $attendeeData['ticket_type'] ?? 'regular';
        $eventDate = $event->date ? Carbon::parse($event->date) : null;
        
        if ($eventDate) {
            $dayName = $eventDate->locale('sw')->isoFormat('dddd');
            $formattedTime = $eventDate->format('g:i');
            $period = $eventDate->format('A');
            $dateString = $dayName . ' ' . $eventDate->toLocaleDateString('en-GB') . ' saa ' . $formattedTime . ' ' . $period;
        } else {
            $dateString = 'tarehe itakayotangazwa';
        }

        return sprintf(
            "Ndugu %s, unakaribishwa kwenye %s itakayofanyika %s. Mahali: %s. Ukumbi: %s. Aina ya tiketi: %s. Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824",
            $attendeeName,
            $event->name,
            $dateString,
            $event->location,
            $event->venue,
            ucfirst($ticketType)
        );
    }

    /**
     * Get message status from provider
     *
     * @param string $messageId The message ID to check
     * @return array Status information
     */
    public function getMessageStatus(string $messageId): array
    {
        return $this->getStatus($messageId);
    }
} 