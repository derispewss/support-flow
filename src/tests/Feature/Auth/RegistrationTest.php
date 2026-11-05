<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\UserRole;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->withoutVite();

        $this->get('/register')
            ->assertOk()
            ->assertSee('Buat akun gratis');
    }

    public function test_new_user_can_register_and_gets_default_organization(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Sari Utami',
            'email' => 'sari@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => true,
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'sari@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);

        $organization = Organization::query()->where('name', 'Ruang Kerja Sari Utami')->firstOrFail();

        $this->assertDatabaseHas('memberships', [
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'role' => UserRole::Owner->value,
        ]);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_can_supply_organization_name_during_registration(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Andi Wijaya',
            'email' => 'andi@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'organization_name' => 'PT Andi Jaya',
            'terms' => true,
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'andi@example.com')->firstOrFail();
        $organization = Organization::query()->where('name', 'PT Andi Jaya')->firstOrFail();

        $this->assertSame($organization->getKey(), $user->memberships()->firstOrFail()->organization_id);
        $this->assertSame(UserRole::Owner, $user->memberships()->firstOrFail()->role);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => true,
        ])->assertSessionHasErrors('email');
    }

    public function test_registration_requires_terms_and_valid_password(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'weak@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors(['password', 'terms']);
    }

    public function test_registration_creates_membership_for_each_registered_user(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Rani',
            'email' => 'rani@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => true,
        ]);

        $this->assertSame(1, Membership::query()->count());
    }
}
