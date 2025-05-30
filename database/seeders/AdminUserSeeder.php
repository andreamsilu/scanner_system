<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates a default admin user with the following credentials:
     * Email: admin@gmail.com
     * Password: password
     * Role: admin
     */
    public function run(): void
    {
        // Check if admin user already exists
        if (!User::where('email', 'admin@gmail.com')->exists()) {
            User::create([
                'name' => 'System Administrator',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(), // Mark email as verified
            ]);

            $this->command->info('Admin user created successfully.');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
} 