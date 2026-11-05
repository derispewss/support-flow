<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\Tag;
use App\Modules\Support\Services\AuditLogger;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        return view('pages.admin.settings.tags', [
            'organization' => $organization,
            'tags' => $organization->tags()->withCount('tickets')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        $data = $this->validated($request, $organization);

        $tag = $organization->tags()->create([
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
        ]);

        $audit->log('tag.created', "Tag {$tag->name} dibuat.", $tag, [], $organization);

        return back()->with('status', 'Tag ditambahkan.');
    }

    public function update(Request $request, Tag $tag, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($tag->organization_id === $organization->getKey(), 404);

        $data = $this->validated($request, $organization, $tag);

        $tag->fill([
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
        ])->save();

        $audit->log('tag.updated', "Tag {$tag->name} diperbarui.", $tag, [], $organization);

        return back()->with('status', 'Tag diperbarui.');
    }

    public function destroy(Tag $tag, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($tag->organization_id === $organization->getKey(), 404);

        $name = $tag->name;
        $tag->tickets()->detach();
        $tag->delete();

        $audit->log('tag.deleted', "Tag {$name} dihapus.", null, ['name' => $name], $organization);

        return back()->with('status', 'Tag dihapus.');
    }

    protected function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    /**
     * @return array{name: string, color?: string|null}
     */
    protected function validated(Request $request, Organization $organization, ?Tag $tag = null): array
    {
        /** @var array{name: string, color?: string|null} $data */
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('tags', 'name')
                    ->where('organization_id', $organization->getKey())
                    ->ignore($tag?->getKey()),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        return $data;
    }
}
