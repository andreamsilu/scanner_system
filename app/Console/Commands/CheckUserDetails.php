<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CheckUserDetails extends Command
{
    protected $signature = 'user:check-details {email}';
    protected $description = 'Check user details and verify password';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return;
        }

        $this->info('User found:');
        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Created At'],
            [[$user->id, $user->name, $user->email, $user->role, $user->created_at]]
        );

        // Test password verification
        $testPassword = 'password';
        $passwordMatches = Hash::check($testPassword, $user->password);
        
        $this->info("\nPassword verification:");
        $this->info("Test password: {$testPassword}");
        $this->info("Stored hash: {$user->password}");
        $this->info("Password matches: " . ($passwordMatches ? 'YES' : 'NO'));

        // Create a new hash for comparison
        $newHash = Hash::make($testPassword);
        $this->info("\nNew hash for comparison: {$newHash}");
        $this->info("New hash matches test password: " . (Hash::check($testPassword, $newHash) ? 'YES' : 'NO'));
    }
} 