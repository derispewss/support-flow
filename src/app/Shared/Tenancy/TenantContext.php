<?php

namespace App\Shared\Tenancy;

use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\UserRole;

class TenantContext
{
    protected ?Organization $organization = null;

    protected ?Membership $membership = null;

    public function set(Organization $organization, ?Membership $membership = null): void
    {
        $this->organization = $organization;
        $this->membership = $membership;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function membership(): ?Membership
    {
        return $this->membership;
    }

    public function id(): ?int
    {
        return $this->organization?->getKey();
    }

    public function check(): bool
    {
        return $this->organization !== null;
    }

    public function clear(): void
    {
        $this->organization = null;
        $this->membership = null;
    }

    public function role(): ?UserRole
    {
        return $this->membership?->role;
    }

    public function hasRole(UserRole ...$roles): bool
    {
        $role = $this->role();

        return $role !== null && in_array($role, $roles, true);
    }

    public function isStaff(): bool
    {
        return $this->role()?->isStaff() ?? false;
    }

    public function canManageOrganization(): bool
    {
        return $this->role()?->canManageOrganization() ?? false;
    }
}
