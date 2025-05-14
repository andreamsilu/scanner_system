<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendeeSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::first();
        $user = User::where('role', 'user')->first();

        Attendee::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'ticket_number' => 'TICKET001',
            'status' => 'registered',
            'ticket_type' => 'regular',
            'position' => 'Developer',
            'qr_token' => 'qr_token_001',
        ]);

        Attendee::create([
            'user_id' => null,
            'event_id' => $event->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '9876543211',
            'ticket_number' => 'TICKET002',
            'status' => 'registered',
            'ticket_type' => 'vip',
            'position' => 'Designer',
            'qr_token' => 'qr_token_002',
        ]);
    }
} 