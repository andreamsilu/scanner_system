<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Scanner',
            'email' => 'scanner@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get scanner role ID
        $scannerRoleId = DB::table('roles')
            ->where('name', 'scanner')
            ->value('id');

        if ($scannerRoleId) {
            // Assign scanner role to user
            DB::table('role_user')->insert([
                'role_id' => $scannerRoleId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
} 