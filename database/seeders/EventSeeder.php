<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        Event::create([
            'name' => 'Tech Conference 2024',
            'description' => 'Annual technology conference featuring the latest innovations.',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(32),
            'venue' => 'Convention Center',
            'capacity' => 500,
            'location' => '123 Tech Street, Silicon Valley',
            'status' => 'published',
            'is_public' => true,
            'created_by' => $admin->id,
        ]);

        Event::create([
            'name' => 'Startup Meetup',
            'description' => 'Networking event for startups and investors.',
            'start_date' => now()->addDays(15),
            'end_date' => now()->addDays(15),
            'venue' => 'Innovation Hub',
            'capacity' => 200,
            'location' => '456 Startup Avenue, Tech City',
            'status' => 'published',
            'is_public' => true,
            'created_by' => $admin->id,
        ]);
    }
} 