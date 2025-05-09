<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ScannerUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Scanner',
            'email' => 'scanner@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'scanner',
        ]);

        $this->command->info('Scanner user created successfully');
    }
} 