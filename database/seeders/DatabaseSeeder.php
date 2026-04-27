<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use App\Models\Attendee;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'System Administrator with full access'
        ]);

        $scannerRole = Role::create([
            'name' => 'Scanner',
            'slug' => 'scanner',
            'description' => 'Event Scanner with limited access'
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole);

        // Create scanner users
        $scanner1 = User::create([
            'name' => 'Scanner One',
            'email' => 'scanner1@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $scanner1->roles()->attach($scannerRole);

        $scanner2 = User::create([
            'name' => 'Scanner Two',
            'email' => 'scanner2@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $scanner2->roles()->attach($scannerRole);

        // Create sample events
        $event1 = Event::create([
            'name' => 'Tech Conference 2024',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(32),
            'venue' => 'Convention Center',
            'status' => 'active',
            'location' => '123 Tech Street, Silicon Valley',
            'capacity' => 500,
            'created_by' => $admin->id
        ]);

        $event2 = Event::create([
            'name' => 'Business Summit 2024',
            'start_date' => now()->addDays(60),
            'end_date' => now()->addDays(62),
            'venue' => 'Grand Hotel',
            'status' => 'active',
            'location' => '456 Business Avenue, Downtown',
            'capacity' => 300,
            'created_by' => $admin->id
        ]);

        // Assign scanners to events
        $event1->scanners()->attach([$scanner1->id, $scanner2->id]);
        $event2->scanners()->attach($scanner1->id);

        // Create sample attendees with tickets for event 1
        for ($i = 1; $i <= 5; $i++) {
            $token = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $attendee = Attendee::create([
                'event_id' => $event1->id,
                'name' => "Attendee {$i}",
                'email' => "attendee{$i}@example.com",
                'phone' => "+1234567890{$i}",
                'position' => "Position {$i}",
                'token' => $token,
                'qr_code' => "qr_codes/{$token}.png"
            ]);

            Ticket::create([
                'event_id' => $event1->id,
                'attendee_id' => $attendee->id,
                'type' => $i % 2 == 0 ? 'vip' : 'regular',
                'status' => 'active',
                'token' => $token
            ]);
        }

        // Create sample attendees with tickets for event 2
        for ($i = 6; $i <= 10; $i++) {
            $token = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $attendee = Attendee::create([
                'event_id' => $event2->id,
                'name' => "Attendee {$i}",
                'email' => "attendee{$i}@example.com",
                'phone' => "+1234567890{$i}",
                'position' => "Position {$i}",
                'token' => $token,
                'qr_code' => "qr_codes/{$token}.png"
            ]);

            Ticket::create([
                'event_id' => $event2->id,
                'attendee_id' => $attendee->id,
                'type' => $i % 2 == 0 ? 'vip' : 'regular',
                'status' => 'active',
                'token' => $token
            ]);
        }
    }
}
