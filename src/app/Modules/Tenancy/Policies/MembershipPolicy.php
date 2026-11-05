<?php

namespace App\Modules\Tenancy\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;

class MembershipPolicy
{
    public function update(User $user, Membership $membership): bool
    {
        return $this->canManage($user, $membership);
    }

    public function delete(User $user, Membership $membership): bool
    {
        if ($membership->role === UserRole::Owner) {
            return false;
        }

        return $this->canManage($user, $membership);
    }

    protected function canManage(User $user, Membership $membership): bool
    {
        $actor = $user->memberships()
            ->where('organization_id', $membership->organization_id)
            ->where('status', MembershipStatus::Active->value)
            ->first();

        if ($actor === null || ! $actor->role->canManageOrganization()) {
            return false;
        }

        if ($membership->role === UserRole::Owner && $actor->role !== UserRole::Owner) {
            return false;
        }

        if ($membership->role === UserRole::Superadmin && $actor->role !== UserRole::Superadmin) {
            return false;
        }

        return true;
    }
}
