<?php

namespace App\Modules\Support\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\AuditLog;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Organization $organization = null,
        ?User $actor = null,
    ): AuditLog {
        $organization ??= $this->resolveOrganization($subject);
        $actor ??= Auth::user();

        return AuditLog::query()->create([
            'organization_id' => $organization?->getKey(),
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => request()->ip(),
        ]);
    }

    protected function resolveOrganization(?Model $subject): ?Organization
    {
        $organization = tenant();

        if ($organization instanceof Organization) {
            return $organization;
        }

        if ($subject === null) {
            return null;
        }

        $id = $subject->getAttribute('organization_id');

        return is_numeric($id) ? Organization::query()->find((int) $id) : null;
    }
}
