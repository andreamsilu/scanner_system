<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_password_recovery_screen_is_closed(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_closed_recovery_endpoint_does_not_send_notifications(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
        Notification::assertNothingSent();
    }

    public function test_unused_reset_token_route_is_closed(): void
    {
        $this->get('/reset-password/arbitrary-token')->assertNotFound();
    }

    public function test_closed_reset_endpoint_cannot_change_passwords(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;
        $this->post('/reset-password', [
            'email' => $user->email, 'token' => 'arbitrary-token',
            'password' => 'replacement-password', 'password_confirmation' => 'replacement-password',
        ])->assertNotFound();
        $this->assertSame($hash, $user->fresh()->password);
    }
}
