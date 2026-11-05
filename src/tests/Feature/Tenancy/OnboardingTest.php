<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/onboarding')->assertRedirect(route('login'));
    }

    public function test_user_without_organization_can_open_empty_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Anda belum terhubung ke organisasi.')
            ->assertSee($user->email);
    }

    public function test_user_without_organization_can_open_notification_empty_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Belum terhubung ke organisasi')
            ->assertSee('Notifikasi tiket dan aktivitas organisasi');
    }

    public function test_onboarding_screen_can_be_rendered(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['name' => 'Sari Utami']);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertOk()
            ->assertSee('Buat meja kerja')
            ->assertSee('Ruang Kerja Sari Utami');
    }

    public function test_role_selection_is_saved_and_cannot_be_changed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/onboarding/role', ['role' => 'customer'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('customer', $user->fresh()->onboarding_role);

        $this->actingAs($user)
            ->post('/onboarding/role', ['role' => 'owner'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('customer', $user->fresh()->onboarding_role);
    }

    public function test_verified_user_can_create_organization(): void
    {
        $user = User::factory()->create(['onboarding_role' => UserRole::Owner->value]);

        $this->actingAs($user)
            ->post('/onboarding', ['name' => 'Meja Bantuan Sari'])
            ->assertRedirect(route('admin.dashboard'));

        $organization = Organization::query()->where('name', 'Meja Bantuan Sari')->firstOrFail();

        $this->assertDatabaseHas('memberships', [
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'role' => UserRole::Owner->value,
        ]);

        $this->assertSame($organization->getKey(), session('current_organization_id'));
    }

    public function test_unverified_user_cannot_create_organization(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/onboarding', ['name' => 'Organisasi Baru'])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('organizations', ['name' => 'Organisasi Baru']);
    }

    public function test_user_with_membership_is_redirected_away_from_onboarding(): void
    {
        [$user] = $this->createTenantUser(UserRole::Agent);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect(route('admin.dashboard'));
    }
}
