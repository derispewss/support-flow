<?php

namespace Tests\Feature\Admin;

use App\Modules\Support\Models\Ticket;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_open_the_admin_console(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $this->actingAsTenant($customer, $organization);

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.tickets.index'))->assertForbidden();
        $this->get(route('admin.members.index'))->assertForbidden();
        $this->get(route('admin.settings.categories.index'))->assertForbidden();
        $this->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_agents_can_open_the_dashboard_and_queue(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        $this->actingAsTenant($agent, $organization);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard agent')
            ->assertSee('Queue tiket');

        $this->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('Queue tiket');
    }

    public function test_agents_cannot_manage_master_data_members_or_audit(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        $this->actingAsTenant($agent, $organization);

        $this->get(route('admin.members.index'))->assertForbidden();
        $this->get(route('admin.settings.categories.index'))->assertForbidden();
        $this->get(route('admin.settings.tags.index'))->assertForbidden();
        $this->get(route('admin.settings.sla.index'))->assertForbidden();
        $this->get(route('admin.settings.teams.index'))->assertForbidden();
        $this->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_owners_and_admins_can_open_every_admin_page(): void
    {
        foreach ([UserRole::Superadmin, UserRole::Owner, UserRole::Admin] as $role) {
            [$staff, $organization] = $this->createTenantUser($role);
            $this->actingAsTenant($staff, $organization);

            $this->get(route('admin.dashboard'))->assertOk();
            $this->get(route('admin.tickets.index'))->assertOk();
            $this->get(route('admin.members.index'))->assertOk();
            $this->get(route('admin.settings.categories.index'))->assertOk();
            $this->get(route('admin.settings.tags.index'))->assertOk();
            $this->get(route('admin.settings.sla.index'))->assertOk();
            $this->get(route('admin.settings.teams.index'))->assertOk();
            $this->get(route('admin.audit.index'))->assertOk();
        }
    }

    public function test_agents_cannot_open_another_organizations_ticket(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$otherCustomer, $otherOrganization] = $this->createTenantUser(UserRole::Customer);

        $foreign = Ticket::factory()->create([
            'organization_id' => $otherOrganization->getKey(),
            'requester_id' => $otherCustomer->getKey(),
        ]);

        $this->actingAsTenant($agent, $organization);

        $this->get(route('admin.tickets.show', $foreign))->assertNotFound();
    }
}
