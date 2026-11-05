<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\User;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke akun Anda');
    }

    public function test_owner_is_redirected_to_admin_after_login(): void
    {
        [$owner] = $this->createTenantUser(UserRole::Owner, [
            'email' => 'owner@example.com',
            'password' => 'Password123',
        ]);

        $this->post('/login', [
            'email' => 'owner@example.com',
            'password' => 'Password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_customer_is_redirected_to_portal_after_login(): void
    {
        $this->createTenantUser(UserRole::Customer, [
            'email' => 'customer@example.com',
            'password' => 'Password123',
        ]);

        $this->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'Password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_unverified_user_is_sent_to_verification_notice(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer, [
            'email' => 'unverified@example.com',
            'password' => 'Password123',
            'email_verified_at' => null,
        ]);

        $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'Password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $this->createTenantUser(UserRole::Customer, [
            'email' => 'customer@example.com',
            'password' => 'Password123',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->createTenantUser(UserRole::Customer, [
            'email' => 'inactive@example.com',
            'password' => 'Password123',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_google_only_user_cannot_login_with_password(): void
    {
        User::factory()->googleOnly()->create([
            'email' => 'google@example.com',
            'password' => null,
        ]);

        $this->post('/login', [
            'email' => 'google@example.com',
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->createTenantUser(UserRole::Customer, [
            'email' => 'limited@example.com',
            'password' => 'Password123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
