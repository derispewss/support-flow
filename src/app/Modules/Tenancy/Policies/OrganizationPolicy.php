<?php

namespace App\Modules\Tenancy\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;

class OrganizationPolicy
{
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->activeMembership($user, $organization) !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->activeMembership($user, $organization)?->role->canManageOrganization() ?? false;
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->activeMembership($user, $organization)?->role->canManageOrganization() ?? false;
    }

    public function delete(User $user, Organization $organization): bool
    {
        $membership = $this->activeMembership($user, $organization);

        return $membership !== null && $membership->role->value === 'owner';
    }

    protected function activeMembership(User $user, Organization $organization): ?Membership
    {
        return $user->memberships()
            ->where('organization_id', $organization->getKey())
            ->where('status', MembershipStatus::Active->value)
            ->first();
    }
}
