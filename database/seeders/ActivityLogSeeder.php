<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        // Get admin user through the user_role table
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $adminUserId = DB::table('user_role')
            ->where('role_id', $adminRoleId)
            ->value('user_id');
        $admin = User::find($adminUserId);

        if ($admin) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'login',
                'description' => 'Admin user logged in',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);

            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'create_event',
                'description' => 'Created new event: Tech Conference 2024',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
        }
    }
} 