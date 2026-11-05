<?php

namespace Tests\Feature\Api;

use App\Modules\Identity\Models\User;
use App\Shared\Enums\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_token_and_default_membership(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sari Utami',
            'email' => 'sari@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'device_name' => 'smoke-test',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registered')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'sari@example.com')
            ->assertJsonPath('data.memberships.0.role', UserRole::Owner->value);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'smoke-test']);
    }

    public function test_login_returns_token_for_active_user(): void
    {
        $this->createTenantUser(UserRole::Agent, [
            'email' => 'agent@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'agent@example.com',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'agent@example.com')
            ->assertJsonPath('data.memberships.0.role', UserRole::Agent->value)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_invalid_credentials_without_leaking_reason(): void
    {
        $this->createTenantUser(UserRole::Customer, [
            'email' => 'customer@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_rejects_inactive_user(): void
    {
        $this->createTenantUser(UserRole::Customer, [
            'email' => 'inactive@example.com',
            'password' => 'Password123',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123',
        ])->assertUnprocessable();
    }

    public function test_me_returns_authenticated_user_with_bearer_token(): void
    {
        [$user] = $this->createTenantUser(UserRole::Owner, ['email' => 'owner@example.com']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->getKey())
            ->assertJsonPath('data.memberships.0.role', UserRole::Owner->value);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer);
        $current = $user->createToken('current')->plainTextToken;
        $user->createToken('other');

        $this->withHeader('Authorization', 'Bearer '.$current)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'current']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'other']);
    }

    public function test_forgot_password_always_returns_generic_message(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'known@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'known@example.com'])
            ->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com'])
            ->assertOk();
    }
}
