<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\User;
use App\Shared\Enums\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post('/forgot-password', ['email' => 'reset@example.com'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        [$user] = $this->createTenantUser(UserRole::Customer, ['email' => 'reset@example.com']);

        $this->post('/forgot-password', ['email' => 'reset@example.com']);

        $token = $this->resetTokenFromNotification($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(password_verify('NewPassword123', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $this->createTenantUser(UserRole::Customer, ['email' => 'reset@example.com']);

        $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => 'reset@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_reset_revokes_api_tokens(): void
    {
        Notification::fake();

        [$user] = $this->createTenantUser(UserRole::Customer, ['email' => 'reset@example.com']);
        $apiToken = $user->createToken('api')->plainTextToken;

        $this->post('/forgot-password', ['email' => 'reset@example.com']);

        $resetToken = $this->resetTokenFromNotification($user);

        $this->post('/reset-password', [
            'token' => $resetToken,
            'email' => 'reset@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertNotEmpty($apiToken);
    }

    /**
     * The database stores only a hash of the reset token, so read the plain
     * token from the queued ResetPassword notification instead.
     */
    protected function resetTokenFromNotification(User $user): string
    {
        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $this->assertIsString($token);

        return $token;
    }
}
