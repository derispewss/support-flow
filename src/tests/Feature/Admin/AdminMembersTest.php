<?php

namespace Tests\Feature\Admin;

use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMembersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_change_a_member_role(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        [, $membership] = $this->addTenantMember($organization, UserRole::Customer);

        $this->actingAsTenant($owner, $organization);

        $this->patch(route('admin.members.role.update', $membership), [
            'role' => UserRole::Agent->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Agent, $membership->fresh()->role);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->getKey(),
            'event' => 'member.role_changed',
            'subject_id' => $membership->getKey(),
        ]);
    }

    public function test_admin_cannot_grant_owner_role(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        [, $membership] = $this->addTenantMember($organization, UserRole::Agent);

        $this->actingAsTenant($admin, $organization);

        $this->patch(route('admin.members.role.update', $membership), [
            'role' => UserRole::Owner->value,
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Agent, $membership->fresh()->role);
    }

    public function test_member_cannot_change_their_own_role(): void
    {
        [$owner, $organization, $ownerMembership] = $this->createTenantUser(UserRole::Owner);
        $this->actingAsTenant($owner, $organization);

        $this->patch(route('admin.members.role.update', $ownerMembership), [
            'role' => UserRole::Admin->value,
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Owner, $ownerMembership->fresh()->role);
    }

    public function test_owner_can_suspend_and_reactivate_a_member(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        [, $membership] = $this->addTenantMember($organization, UserRole::Agent);

        $this->actingAsTenant($owner, $organization);

        $this->patch(route('admin.members.status.update', $membership), [
            'status' => MembershipStatus::Suspended->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(MembershipStatus::Suspended, $membership->fresh()->status);

        $this->patch(route('admin.members.status.update', $membership), [
            'status' => MembershipStatus::Active->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(MembershipStatus::Active, $membership->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'member.status_changed']);
    }

    public function test_member_cannot_suspend_themselves(): void
    {
        [$owner, $organization, $ownerMembership] = $this->createTenantUser(UserRole::Owner);
        $this->actingAsTenant($owner, $organization);

        $this->patch(route('admin.members.status.update', $ownerMembership), [
            'status' => MembershipStatus::Suspended->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(MembershipStatus::Active, $ownerMembership->fresh()->status);
    }

    public function test_owner_can_remove_another_member(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        [, $membership] = $this->addTenantMember($organization, UserRole::Agent);

        $this->actingAsTenant($owner, $organization);

        $this->delete(route('admin.members.destroy', $membership))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('memberships', ['id' => $membership->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'member.removed']);
    }

    public function test_owner_cannot_be_removed_through_the_member_endpoint(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        [$owner, $ownerMembership] = $this->addTenantMember($organization, UserRole::Owner);

        $this->actingAsTenant($admin, $organization);

        $this->delete(route('admin.members.destroy', $ownerMembership))->assertForbidden();

        $this->assertDatabaseHas('memberships', ['id' => $ownerMembership->getKey()]);
        $this->assertNotNull($owner->fresh());
    }

    public function test_admin_cannot_change_an_owner_role(): void
    {
        [$owner, $organization, $ownerMembership] = $this->createTenantUser(UserRole::Owner);
        [$admin] = $this->addTenantMember($organization, UserRole::Admin);

        $this->actingAsTenant($admin, $organization);

        $this->patch(route('admin.members.role.update', $ownerMembership), [
            'role' => UserRole::Admin->value,
        ])->assertForbidden();

        $this->assertSame(UserRole::Owner, $ownerMembership->fresh()->role);
        $this->assertNotNull($owner->fresh());
    }

    public function test_members_are_scoped_to_the_current_organization(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);

        [, $otherOrganization] = $this->createTenantUser(UserRole::Owner);
        [, $foreignMembership] = $this->addTenantMember($otherOrganization, UserRole::Agent);

        $this->actingAsTenant($owner, $organization);

        $this->patch(route('admin.members.role.update', $foreignMembership), [
            'role' => UserRole::Admin->value,
        ])->assertNotFound();
    }
}
