<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create scanner users
        User::create([
            'name' => 'Scanner User 1',
            'email' => 'scanner1@example.com',
            'password' => Hash::make('password'),
            'role' => 'scanner',
        ]);

        User::create([
            'name' => 'Scanner User 2',
            'email' => 'scanner2@example.com',
            'password' => Hash::make('password'),
            'role' => 'scanner',
        ]);
    }
} 