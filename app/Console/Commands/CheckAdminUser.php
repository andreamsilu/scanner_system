<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CheckAdminUser extends Command
{
    protected $signature = 'user:check-admin';
    protected $description = 'Check if admin user exists and verify credentials';

    public function handle()
    {
        $user = User::where('email', 'admin@example.com')->first();

        if (!$user) {
            $this->error('Admin user does not exist in the database!');
            return;
        }

        $this->info('Admin user found:');
        $this->table(
            ['ID', 'Name', 'Email', 'Role'],
            [[$user->id, $user->name, $user->email, $user->role]]
        );

        // Test password
        $testPassword = 'password';
        $passwordMatches = Hash::check($testPassword, $user->password);
        
        $this->info('Password check with "' . $testPassword . '": ' . ($passwordMatches ? 'MATCHES' : 'DOES NOT MATCH'));
    }
} 