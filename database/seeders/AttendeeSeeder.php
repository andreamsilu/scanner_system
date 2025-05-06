<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Database\Seeder;

class AttendeeSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        foreach ($events as $event) {
            // Create 10 attendees for each event
            for ($i = 0; $i < 10; $i++) {
                Attendee::create([
                    'event_id' => $event->id,
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'phone' => fake()->phoneNumber(),
                    'company' => fake()->company(),
                    'position' => fake()->jobTitle(),
                    'registration_date' => now(),
                    'status' => 'registered',
                ]);
            }
        }
    }
} 