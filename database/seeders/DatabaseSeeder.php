<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call(RoleSeeder::class);
        // Then seed users and assign roles
        $this->call(ScannerSeeder::class);

        $this->call([
            UserSeeder::class,
            EventSeeder::class,
            TicketTypeSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
