<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Support\Services\AuditLogger;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SlaPolicyController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        return view('pages.admin.settings.sla', [
            'organization' => $organization,
            'policies' => $organization->slaPolicies()->withCount('tickets')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        $data = $this->validated($request, $organization);
        $isDefault = $request->boolean('is_default');

        $policy = DB::transaction(function () use ($organization, $data, $isDefault): SlaPolicy {
            if ($isDefault) {
                $organization->slaPolicies()->update(['is_default' => false]);
            }

            return $organization->slaPolicies()->create([
                'name' => $data['name'],
                'first_response_minutes' => (int) $data['first_response_minutes'],
                'resolution_minutes' => (int) $data['resolution_minutes'],
                'is_default' => $isDefault,
            ]);
        });

        $audit->log('sla.created', "SLA {$policy->name} dibuat.", $policy, [], $organization);

        return back()->with('status', 'Kebijakan SLA ditambahkan.');
    }

    public function update(Request $request, SlaPolicy $slaPolicy, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($slaPolicy->organization_id === $organization->getKey(), 404);

        $data = $this->validated($request, $organization, $slaPolicy);
        $isDefault = $request->boolean('is_default');

        DB::transaction(function () use ($organization, $slaPolicy, $data, $isDefault): void {
            if ($isDefault) {
                $organization->slaPolicies()
                    ->where('id', '!=', $slaPolicy->getKey())
                    ->update(['is_default' => false]);
            }

            $slaPolicy->fill([
                'name' => $data['name'],
                'first_response_minutes' => (int) $data['first_response_minutes'],
                'resolution_minutes' => (int) $data['resolution_minutes'],
                'is_default' => $isDefault,
            ])->save();
        });

        $audit->log('sla.updated', "SLA {$slaPolicy->name} diperbarui.", $slaPolicy, [], $organization);

        return back()->with('status', 'Kebijakan SLA diperbarui.');
    }

    public function destroy(SlaPolicy $slaPolicy, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($slaPolicy->organization_id === $organization->getKey(), 404);

        $name = $slaPolicy->name;
        $slaPolicy->delete();

        $audit->log('sla.deleted', "SLA {$name} dihapus.", null, ['name' => $name], $organization);

        return back()->with('status', 'Kebijakan SLA dihapus.');
    }

    protected function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    /**
     * @return array{name: string, first_response_minutes: int|string, resolution_minutes: int|string}
     */
    protected function validated(Request $request, Organization $organization, ?SlaPolicy $policy = null): array
    {
        /** @var array{name: string, first_response_minutes: int|string, resolution_minutes: int|string} $data */
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('sla_policies', 'name')
                    ->where('organization_id', $organization->getKey())
                    ->ignore($policy?->getKey()),
            ],
            'first_response_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'resolution_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
        ]);

        return $data;
    }
}
