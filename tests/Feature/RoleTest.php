<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_exist(): void
    {
        // Run the seeders
        $this->seed();

        // Check if roles exist
        $this->assertTrue(Role::where('slug', 'admin')->exists());
        $this->assertTrue(Role::where('slug', 'scanner')->exists());
    }

    public function test_admin_user_has_correct_role(): void
    {
        // Run the seeders
        $this->seed();

        // Get admin user
        $admin = User::where('email', 'admin@gmail.com')->first();
        
        // Check if user exists and has admin role
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isScanner());
    }

    public function test_scanner_user_has_correct_role(): void
    {
        // Run the seeders
        $this->seed();

        // Get scanner user
        $scanner = User::where('email', 'scanner1@example.com')->first();
        
        // Check if user exists and has scanner role
        $this->assertNotNull($scanner);
        $this->assertTrue($scanner->isScanner());
        $this->assertFalse($scanner->isAdmin());
    }

    public function test_role_assignment(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        // Create a new user
        $user = User::factory()->create();
        
        // Initially should have no roles
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isScanner());
        
        // Assign admin role
        $user->assignRole('admin');
        $this->assertTrue($user->isAdmin());
        
        // Assign scanner role
        $user->assignRole('scanner');
        $this->assertTrue($user->isScanner());
    }
}
