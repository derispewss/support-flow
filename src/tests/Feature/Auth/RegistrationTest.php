<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
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

    public function test_new_user_can_register_and_is_sent_to_onboarding(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Sari Utami',
            'email' => 'sari@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => true,
        ]);

        $response->assertRedirect(route('onboarding.create'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'sari@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);

        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('memberships', 0);

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
        ])->assertRedirect(route('onboarding.create'));

        $user = User::query()->where('email', 'andi@example.com')->firstOrFail();

        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('memberships', 0);
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

    public function test_registration_does_not_create_membership_before_onboarding(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Rani',
            'email' => 'rani@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => true,
        ]);

        $this->assertSame(0, Membership::query()->count());
    }
}
