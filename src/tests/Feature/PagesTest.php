<?php

namespace Tests\Feature;

use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_landing_page_is_accessible(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tiket masuk.')
            ->assertSee('Semua yang tim support butuhkan');
    }

    public function test_public_help_center_is_accessible(): void
    {
        $this->get('/help')
            ->assertOk()
            ->assertSee('Pusat Bantuan');
    }

    public function test_auth_pages_are_accessible(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke akun Anda');
        $this->get('/register')->assertOk()->assertSee('Buat akun gratis');
        $this->get('/forgot-password')->assertOk()->assertSee('Lupa password?');
    }

    public function test_guest_is_redirected_to_login_from_portal(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/tickets')->assertRedirect(route('login'));
        $this->get('/tickets/create')->assertRedirect(route('login'));
        $this->get('/notifications')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_customer_portal_pages_are_accessible(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Tiket terbaru');
        $this->actingAs($user)->get('/tickets')->assertOk()->assertSee('Tiket Saya');
        $this->actingAs($user)->get('/tickets/create')->assertOk()->assertSee('Buat tiket baru');
        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('Notifikasi');
    }

    public function test_admin_dashboard_is_accessible_to_staff(): void
    {
        [$agent] = $this->createTenantUser(UserRole::Agent);

        $this->actingAs($agent)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard agent')
            ->assertSee('Queue tiket');
    }

    public function test_owner_is_redirected_to_admin_workspace_from_customer_portal(): void
    {
        [$owner] = $this->createTenantUser(UserRole::Owner);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($owner)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Buat Organisasi Baru')
            ->assertDontSee('Buka Portal Customer');
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        [$customer] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
