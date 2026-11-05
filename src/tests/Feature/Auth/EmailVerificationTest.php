<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\User;
use App\Shared\Enums\UserRole;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notice_can_be_rendered(): void
    {
        $this->withoutVite();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('Cek email Anda');
    }

    public function test_email_can_be_verified_with_signed_url(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer, ['email_verified_at' => null]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_cannot_be_verified_with_invalid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->getKey(), 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_notification_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_is_redirected_away_from_notice(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertRedirect(route('dashboard'));
    }
}
