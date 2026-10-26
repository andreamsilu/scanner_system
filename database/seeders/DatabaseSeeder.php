<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo seeders are disabled in production. Use app:create-admin instead.');
        }
        // Ensure both admin identities exist:
        // 1) users-table admin for normal app auth
        // 2) scanner_operators admin for scanner operator auth
        $this->call([
            AdminUserSeeder::class,
            AdminSeeder::class,
        ]);

        // Create roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'description' => 'System Administrator with full access']
        );

        $scannerRole = Role::firstOrCreate(
            ['slug' => 'scanner'],
            ['name' => 'Scanner', 'description' => 'Event Scanner with limited access']
        );

        // Reuse the admin user created by AdminUserSeeder.
        $admin = User::where('email', 'admin@gmail.com')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        // Create scanner users
        $scanner1 = User::firstOrCreate(
            ['email' => 'scanner1@example.com'],
            [
                'name' => 'Scanner One',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $scanner1->roles()->syncWithoutDetaching([$scannerRole->id]);

        $scanner2 = User::firstOrCreate(
            ['email' => 'scanner2@example.com'],
            [
                'name' => 'Scanner Two',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $scanner2->roles()->syncWithoutDetaching([$scannerRole->id]);

        // Intentionally do not seed demo events/attendees/tickets.
    }
}
