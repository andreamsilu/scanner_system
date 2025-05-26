<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignRoles extends Command
{
    protected $signature = 'users:assign-roles';
    protected $description = 'Assign roles to existing users';

    public function handle()
    {
        // Assign admin role to the first user
        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole('admin');
            $this->info('Admin role assigned to: ' . $admin->email);
        }

        // Assign scanner role to other users
        $scanner = User::where('email', 'scanner@gmail.com')->first();
        if ($scanner) {
            $scanner->assignRole('scanner');
            $this->info('Scanner role assigned to: ' . $scanner->email);
        }

        $this->info('Roles assigned successfully!');
    }
} 