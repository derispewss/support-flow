<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Services\AuditLogger;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Modules\Tenancy\Models\Team;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        return view('pages.admin.settings.teams', [
            'organization' => $organization,
            'teams' => $organization->teams()->with(['lead', 'members'])->withCount('tickets')->orderBy('name')->get(),
            'staff' => $this->staff($organization),
        ]);
    }

    public function store(Request $request, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        $data = $this->validated($request, $organization);
        $memberIds = $this->memberIds($data, $organization);

        $team = DB::transaction(function () use ($organization, $data, $memberIds): Team {
            $team = $organization->teams()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'lead_id' => $data['lead_id'] ?? null,
            ]);

            $team->members()->sync($memberIds);

            return $team;
        });

        $audit->log('team.created', "Tim {$team->name} dibuat.", $team, ['members' => $memberIds], $organization);

        return back()->with('status', 'Tim ditambahkan.');
    }

    public function update(Request $request, Team $team, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($team->organization_id === $organization->getKey(), 404);

        $data = $this->validated($request, $organization, $team);
        $memberIds = $this->memberIds($data, $organization);

        DB::transaction(function () use ($team, $data, $memberIds): void {
            $team->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'lead_id' => $data['lead_id'] ?? null,
            ])->save();

            $team->members()->sync($memberIds);
        });

        $audit->log('team.updated', "Tim {$team->name} diperbarui.", $team, ['members' => $memberIds], $organization);

        return back()->with('status', 'Tim diperbarui.');
    }

    public function destroy(Team $team, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($team->organization_id === $organization->getKey(), 404);

        $name = $team->name;
        $team->members()->detach();
        $team->delete();

        $audit->log('team.deleted', "Tim {$name} dihapus.", null, ['name' => $name], $organization);

        return back()->with('status', 'Tim dihapus.');
    }

    protected function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, Organization $organization, ?Team $team = null): array
    {
        $staffIds = $this->staff($organization)->pluck('user_id')->all();

        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('teams', 'name')
                    ->where('organization_id', $organization->getKey())
                    ->ignore($team?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'lead_id' => ['nullable', 'integer', Rule::in($staffIds)],
            'members' => ['nullable', 'array'],
            'members.*' => ['integer', Rule::in($staffIds)],
        ]);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    protected function memberIds(array $data, Organization $organization): array
    {
        $rawMembers = $data['members'] ?? [];
        $ids = collect(is_array($rawMembers) ? $rawMembers : [])->map(fn ($id) => (int) $id);

        if (isset($data['lead_id'])) {
            $ids->push((int) $data['lead_id']);
        }

        $allowed = $this->staff($organization)->pluck('user_id')->all();

        return $ids
            ->unique()
            ->filter(fn (int $id) => in_array($id, $allowed, true))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Membership>
     */
    protected function staff(Organization $organization): Collection
    {
        return $organization->memberships()
            ->active()
            ->whereIn('role', ['owner', 'admin', 'agent'])
            ->with('user')
            ->get()
            ->sortBy(fn ($membership) => $membership->user?->name)
            ->values();
    }
}
