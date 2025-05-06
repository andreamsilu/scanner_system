<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Tech Conference 2024',
                'description' => 'Annual technology conference featuring the latest innovations and industry leaders.',
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(32),
                'location' => 'Convention Center, New York',
                'capacity' => 500,
                'status' => 'active',
            ],
            [
                'title' => 'Business Networking Event',
                'description' => 'Networking event for business professionals and entrepreneurs.',
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(15),
                'location' => 'Grand Hotel, Chicago',
                'capacity' => 200,
                'status' => 'active',
            ],
            [
                'title' => 'Workshop: Digital Marketing',
                'description' => 'Hands-on workshop covering the latest digital marketing strategies.',
                'start_date' => now()->addDays(45),
                'end_date' => now()->addDays(46),
                'location' => 'Business Center, San Francisco',
                'capacity' => 100,
                'status' => 'active',
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
} 