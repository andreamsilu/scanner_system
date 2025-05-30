<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'type' => 'attendee_invitation',
                'name' => 'Attendee Event Invitation',
                'content' => "Dear {user_name},\n\nYou're invited to {event_name}!\n\n📅 Date: {date}\n📍 Venue: {location}\n\n🎫 Ticket Type: {ticket_type}\n💰 Price: {ticket_price}\n\nPlease arrive 30 minutes before the event.\n\nFor any queries, contact: {contact_number}\n\nWe look forward to seeing you!\n\nBest regards,\n{event_organizer}",
                'is_active' => true
            ],
            [
                'type' => 'ticket_used',
                'name' => 'Ticket Usage Notification',
                'content' => "Dear {user_name},\n\nYour ticket for {event_name} has been used.\n\n🎫 Ticket #: {ticket_number}\n⏰ Used at: {used_time}\n📍 Entry Point: {entry_point}\n\nThank you for attending!\n\nFor support, contact: {support_number}",
                'is_active' => true
            ],
            [
                'type' => 'ticket_reminder',
                'name' => 'Event Reminder',
                'content' => "Reminder: {event_name} is tomorrow!\n\n📅 Date: {date}\n📍 Venue: {location}\n🎫 Ticket #: {ticket_number}\n\nPlease bring your ticket and ID.\n\nSee you there!\n\n{event_organizer}",
                'is_active' => true
            ]
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                ['type' => $template['type'], 'name' => $template['name']],
                $template
            );
        }
    }
} 