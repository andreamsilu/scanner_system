<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_is_created_with_private_password_and_required_roles(): void
    {
        $this->artisan('app:create-admin', ['email' => 'owner@example.com'])
            ->expectsQuestion('Password (at least 12 characters)', 'a-long-test-password')
            ->expectsQuestion('Confirm password', 'a-long-test-password')
            ->expectsOutput('Administrator created.')
            ->assertExitCode(0);
        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($owner->hasRole('admin'));
        $this->assertTrue(Hash::check('a-long-test-password', $owner->password));
        $this->assertTrue(Role::where('slug', 'scanner')->exists());
    }

    public function test_weak_password_does_not_create_an_account(): void
    {
        $this->artisan('app:create-admin', ['email' => 'owner@example.com'])
            ->expectsQuestion('Password (at least 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertExitCode(1);
        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }
}
