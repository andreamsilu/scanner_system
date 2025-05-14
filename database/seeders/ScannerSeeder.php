<?php

namespace Database\Seeders;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScannerSeeder extends Seeder
{
    public function run(): void
    {
        $scannerUser = User::where('role', 'scanner')->first();

        Scanner::create([
            'user_id' => $scannerUser->id,
            'name' => 'Scanner User',
            'email' => 'scanner@example.com',
            'phone' => '1234567890',
            'is_active' => true,
        ]);
    }
} 