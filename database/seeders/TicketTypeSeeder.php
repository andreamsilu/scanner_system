<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $ticketTypes = [
            [
                'name' => 'Regular Admission',
                'description' => 'Standard event admission ticket',
                'price' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'VIP Pass',
                'description' => 'Premium access with additional benefits',
                'price' => 50.00,
                'is_active' => true,
            ],
            [
                'name' => 'Early Bird',
                'description' => 'Discounted ticket for early registration',
                'price' => 25.00,
                'is_active' => true,
            ],
        ];

        foreach ($ticketTypes as $ticketType) {
            TicketType::create($ticketType);
        }
    }
} 