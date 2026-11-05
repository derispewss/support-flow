<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_switch_to_organization_they_do_not_belong_to(): void
    {
        [$user] = $this->createTenantUser(UserRole::Owner);
        $foreignOrganization = Organization::factory()->create(['name' => 'Org Asing']);

        $this->actingAs($user)
            ->post(route('organizations.switch', $foreignOrganization))
            ->assertForbidden();
    }

    public function test_dashboard_only_shows_current_organization(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Customer, [
            'name' => 'Budi Santoso',
        ]);
        Organization::factory()->create(['name' => 'Organisasi Lain']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($organization->name)
            ->assertDontSee('Organisasi Lain');
    }

    public function test_suspended_membership_cannot_be_used_to_switch_organization(): void
    {
        [$user] = $this->createTenantUser(UserRole::Customer);
        $foreignOrganization = Organization::factory()->create();

        Membership::factory()->create([
            'organization_id' => $foreignOrganization->getKey(),
            'user_id' => $user->getKey(),
            'role' => UserRole::Agent,
            'status' => MembershipStatus::Suspended,
        ]);

        $this->actingAs($user)
            ->post(route('organizations.switch', $foreignOrganization))
            ->assertForbidden();
    }

    public function test_customer_cannot_manage_members(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($customer)
            ->get(route('admin.members.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.members.invitations.store'), [
                'email' => 'new@example.com',
                'role' => UserRole::Agent->value,
            ])
            ->assertForbidden();

        $this->assertTrue($organization->exists);
    }

    public function test_owner_can_view_members_of_own_organization(): void
    {
        $this->withoutVite();

        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);

        $this->actingAs($owner)
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee($organization->name)
            ->assertSee($owner->email);
    }

    public function test_invitation_from_another_organization_cannot_be_revoked(): void
    {
        [$owner] = $this->createTenantUser(UserRole::Owner);

        $foreignOrganization = Organization::factory()->create();
        $foreignInvitation = Invitation::factory()->create([
            'organization_id' => $foreignOrganization->getKey(),
            'email' => 'foreign@example.com',
        ]);

        $this->actingAs($owner)
            ->delete(route('admin.members.invitations.destroy', $foreignInvitation))
            ->assertNotFound();

        $this->assertDatabaseHas('invitations', ['id' => $foreignInvitation->getKey()]);
    }

    public function test_tenant_context_prefers_staff_membership_when_available(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $customerOrganization = Organization::factory()->create(['name' => 'Org Customer']);
        $staffOrganization = Organization::factory()->create(['name' => 'Org Staff']);

        Membership::factory()->customer()->create([
            'organization_id' => $customerOrganization->getKey(),
            'user_id' => $user->getKey(),
        ]);
        Membership::factory()->agent()->create([
            'organization_id' => $staffOrganization->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Org Staff');
    }
}
