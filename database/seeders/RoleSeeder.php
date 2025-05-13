<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'System administrator with full access'
            ],
            [
                'name' => 'Scanner',
                'slug' => 'scanner',
                'description' => 'User who can scan event cards'
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Regular user with limited access'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
} 