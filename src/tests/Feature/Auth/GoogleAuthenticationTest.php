<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\SocialAccount;
use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_google_redirect_fails_gracefully_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_google_redirect_sends_user_to_provider(): void
    {
        Socialite::shouldReceive('driver->redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_new_google_user_is_created_and_sent_to_role_selection(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->googleUser([
                'id' => 'google-100',
                'name' => 'Google User',
                'email' => 'new-google@example.com',
            ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('onboarding.create'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'new-google@example.com')->firstOrFail();

        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->getKey(),
            'provider' => 'google',
            'provider_user_id' => 'google-100',
        ]);

        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_existing_verified_user_gets_google_account_linked_without_duplicate(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Agent, [
            'email' => 'existing@example.com',
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->googleUser([
                'id' => 'google-200',
                'name' => 'Existing User',
                'email' => 'existing@example.com',
            ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'existing@example.com')->count());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->getKey(),
            'provider_user_id' => 'google-200',
        ]);
        $this->assertSame(1, Membership::query()->where('organization_id', $organization->getKey())->count());
    }

    public function test_google_callback_rejects_unverified_google_email_claim(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->googleUser([
                'id' => 'google-300',
                'name' => 'Unverified Google',
                'email' => 'unverified-google@example.com',
                'email_verified' => false,
            ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'unverified-google@example.com']);
    }

    public function test_google_callback_does_not_link_existing_unverified_user(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'pending@example.com']);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->googleUser([
                'id' => 'google-400',
                'name' => 'Pending User',
                'email' => 'pending@example.com',
            ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->getKey()]);
    }

    public function test_google_callback_handles_denied_consent(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_google_callback_handles_invalid_state(): void
    {
        Socialite::shouldReceive('driver->user')
            ->once()
            ->andThrow(new InvalidStateException('Invalid state'));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_inactive_google_linked_user_is_rejected(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'inactive-google@example.com']);

        SocialAccount::factory()->create([
            'user_id' => $user->getKey(),
            'provider' => 'google',
            'provider_user_id' => 'google-500',
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->googleUser([
                'id' => 'google-500',
                'name' => 'Inactive User',
                'email' => 'inactive-google@example.com',
            ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function googleUser(array $attributes): SocialiteUser
    {
        $raw = array_merge([
            'email_verified' => true,
            'avatar' => 'https://example.com/avatar.png',
        ], $attributes);

        return (new SocialiteUser)->map($attributes)->setRaw($raw);
    }
}
