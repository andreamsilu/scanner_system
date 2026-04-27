<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The legacy scanner_operators table may not exist in current schema.
        // Seed only when the table is present to keep this seeder safe across versions.
        if (!Schema::hasTable('scanner_operators')) {
            $this->command->info('Skipped scanner operator seed (scanner_operators table not present).');
            return;
        }

        DB::table('scanner_operators')->updateOrInsert(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->command->info('Scanner operator admin is ready: admin@example.com / password');
    }
} 