<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\EventCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventCardSeeder extends Seeder
{
    public function run(): void
    {
        $attendees = Attendee::all();

        foreach ($attendees as $attendee) {
            EventCard::create([
                'event_id' => $attendee->event_id,
                'attendee_id' => $attendee->id,
                'token' => Str::random(32),
                'is_active' => true,
                'is_used' => false,
            ]);
        }
    }
} 