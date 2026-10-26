<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_password_confirmation_screen_is_closed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertNotFound();
    }

    public function test_unused_confirmation_endpoint_cannot_confirm_a_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertNotFound();
        $response->assertSessionMissing('auth.password_confirmed_at');
    }

    public function test_invalid_password_cannot_set_confirmation_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertNotFound();
        $response->assertSessionMissing('auth.password_confirmed_at');
    }
}
