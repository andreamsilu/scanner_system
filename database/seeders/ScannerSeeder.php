<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ScannerSeeder extends Seeder
{
    public function run(): void
    {
        // Insert (or upsert) scanner and admin users (using insertOrIgnore so that if the users already exist (e.g. if the users table already exists) then no error is thrown.)
        DB::table('users')->insertOrIgnore([
            [
                'name' => 'Scanner User',
                'email' => 'scanner@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // (Re-)assign (or upsert) the "admin" role (from the roles table) to the user (admin@gmail.com) (using the pivot (user_role) table) (so that (for example) if the roles (or the pivot) are not assigned (or are deleted) then the seeder (re-)assigns them.)
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $adminUserId = DB::table('users')->where('email', 'admin@gmail.com')->value('id');
        if ($adminRoleId && $adminUserId) {
            DB::table('user_role')->insertOrIgnore([
                ['user_id' => $adminUserId, 'role_id' => $adminRoleId, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // (Re-)assign (or upsert) the "scanner" role (from the roles table) to the user (scanner@gmail.com) (using the pivot (user_role) table) (so that (for example) if the roles (or the pivot) are not assigned (or are deleted) then the seeder (re-)assigns them.)
        $scannerRoleId = DB::table('roles')->where('name', 'scanner')->value('id');
        $scannerUserId = DB::table('users')->where('email', 'scanner@gmail.com')->value('id');
        if ($scannerRoleId && $scannerUserId) {
            DB::table('user_role')->insertOrIgnore([
                ['user_id' => $scannerUserId, 'role_id' => $scannerRoleId, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
} 