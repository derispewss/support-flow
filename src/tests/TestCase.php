<?php

namespace Tests;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $userAttributes
     * @return array{0: User, 1: Organization, 2: Membership}
     */
    protected function createTenantUser(UserRole $role = UserRole::Customer, array $userAttributes = []): array
    {
        $user = User::factory()->create($userAttributes);
        $organization = Organization::factory()->create();

        $membership = Membership::factory()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        return [$user, $organization, $membership];
    }

    /**
     * Authenticate the user and bind the tenant context so Livewire components
     * (whose update requests skip route middleware) can resolve the organization.
     */
    protected function actingAsTenant(User $user, Organization $organization): Membership
    {
        $membership = $user->membershipFor($organization);

        if (! $membership instanceof Membership) {
            $membership = Membership::factory()->create([
                'organization_id' => $organization->getKey(),
                'user_id' => $user->getKey(),
                'role' => UserRole::Agent,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);
        }

        $this->app->make(TenantContext::class)->set($organization, $membership);
        $this->actingAs($user);

        return $membership;
    }

    /**
     * Add an extra member to an organization.
     *
     * @param  array<string, mixed>  $userAttributes
     * @return array{0: User, 1: Membership}
     */
    protected function addTenantMember(Organization $organization, UserRole $role = UserRole::Agent, array $userAttributes = []): array
    {
        $user = User::factory()->create($userAttributes);

        $membership = Membership::factory()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        return [$user, $membership];
    }
}
