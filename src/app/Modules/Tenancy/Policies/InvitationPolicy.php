<?php

namespace App\Modules\Tenancy\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;

class InvitationPolicy
{
    public function create(User $user, Organization $organization): bool
    {
        return $this->canManage($user, $organization->getKey());
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $this->canManage($user, $invitation->organization_id);
    }

    protected function canManage(User $user, int $organizationId): bool
    {
        $membership = $user->memberships()
            ->where('organization_id', $organizationId)
            ->where('status', MembershipStatus::Active->value)
            ->first();

        return $membership?->role->canManageOrganization() ?? false;
    }
}
