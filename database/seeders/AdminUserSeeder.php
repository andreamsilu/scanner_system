<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
        // Create or update the default app admin user.
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Attach admin role through the pivot table when role tables exist.
        if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $adminRole = Role::firstOrCreate(
                ['slug' => 'admin'],
                ['name' => 'Administrator', 'description' => 'System Administrator with full access']
            );

            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $this->command->info('Admin user is ready: admin@gmail.com / password');
    }
} 