<?php

namespace App\Services;

use Twilio\Rest\Client;
use App\Models\SmsTemplate;
use App\Models\SmsLog;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SmsService
{
    protected $client;
    protected $fromNumber;

    /**
     * Initialize SMS service with Twilio client
     */
    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->fromNumber = config('services.twilio.from_number');
    }

    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string $type Type of message
     * @param array $metadata Additional metadata to store
     * @return bool
     */
    public function send(string $to, string $message, string $type = 'notification', array $metadata = []): bool
    {
        try {
            $response = $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            // Log the SMS
            SmsLog::create([
                'to' => $to,
                'message' => $message,
                'type' => $type,
                'status' => $response->status,
                'sid' => $response->sid,
                'metadata' => $metadata
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage(), [
                'to' => $to,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send an attendee invitation SMS
     *
     * @param Event $event
     * @param array $attendeeData
     * @return bool
     */
    public function sendAttendeeInvitation(Event $event, array $attendeeData): bool
    {
        $template = SmsTemplate::where('type', 'attendee_invitation')->first();
        
        if (!$template) {
            Log::error('Attendee invitation template not found');
            return false;
        }

        $data = array_merge([
            'event_name' => $event->name,
            'date' => $event->date->format('F j, Y g:i A'),
            'location' => $event->location,
            'contact_number' => $event->contact_number ?? config('app.support_phone'),
            'event_organizer' => $event->organizer_name ?? config('app.name')
        ], $attendeeData);

        $message = $this->replaceTemplateVariables($template->content, $data);
        
        return $this->send(
            $attendeeData['phone'],
            $message,
            'attendee_invitation',
            [
                'event_id' => $event->id,
                'attendee_id' => $attendeeData['attendee_id'] ?? null,
                'ticket_id' => $attendeeData['ticket_id'] ?? null
            ]
        );
    }

    /**
     * Send a ticket usage notification
     *
     * @param Ticket $ticket
     * @param string $entryPoint
     * @return bool
     */
    public function sendTicketUsageNotification(Ticket $ticket, string $entryPoint): bool
    {
        $template = SmsTemplate::where('type', 'ticket_used')->first();
        
        if (!$template) {
            Log::error('Ticket usage notification template not found');
            return false;
        }

        $data = [
            'user_name' => $ticket->attendee->name,
            'event_name' => $ticket->event->name,
            'ticket_number' => $ticket->ticket_number,
            'used_time' => Carbon::now()->format('F j, Y g:i A'),
            'entry_point' => $entryPoint,
            'support_number' => config('app.support_phone')
        ];

        $message = $this->replaceTemplateVariables($template->content, $data);
        
        return $this->send(
            $ticket->attendee->phone,
            $message,
            'ticket_used',
            [
                'ticket_id' => $ticket->id,
                'event_id' => $ticket->event_id,
                'entry_point' => $entryPoint
            ]
        );
    }

    /**
     * Send an event reminder
     *
     * @param Ticket $ticket
     * @return bool
     */
    public function sendEventReminder(Ticket $ticket): bool
    {
        $template = SmsTemplate::where('type', 'ticket_reminder')->first();
        
        if (!$template) {
            Log::error('Event reminder template not found');
            return false;
        }

        $data = [
            'user_name' => $ticket->attendee->name,
            'event_name' => $ticket->event->name,
            'date' => $ticket->event->date->format('F j, Y g:i A'),
            'location' => $ticket->event->location,
            'ticket_number' => $ticket->ticket_number,
            'event_organizer' => $ticket->event->organizer_name ?? config('app.name')
        ];

        $message = $this->replaceTemplateVariables($template->content, $data);
        
        return $this->send(
            $ticket->attendee->phone,
            $message,
            'ticket_reminder',
            [
                'ticket_id' => $ticket->id,
                'event_id' => $ticket->event_id
            ]
        );
    }

    /**
     * Replace template variables with actual data
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function replaceTemplateVariables(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }
} 