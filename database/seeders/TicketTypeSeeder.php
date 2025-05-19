<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        TicketType::create([
            'name' => 'Regular',
            'description' => 'Standard event access',
            'price' => 50.00,
            'is_active' => true,
        ]);

        TicketType::create([
            'name' => 'VIP',
            'description' => 'Premium access with additional benefits',
            'price' => 100.00,
            'is_active' => true,
        ]);
    }
} 