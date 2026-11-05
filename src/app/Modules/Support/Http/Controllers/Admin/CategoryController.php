<?php

namespace App\Modules\Support\Http\Controllers\Admin;

use App\Modules\Support\Models\Category;
use App\Modules\Support\Services\AuditLogger;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        return view('pages.admin.settings.categories', [
            'organization' => $organization,
            'categories' => $organization->categories()->withCount('tickets')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);

        $data = $this->validated($request);

        $category = $organization->categories()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($organization, $data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $audit->log('category.created', "Kategori {$category->name} dibuat.", $category, [], $organization);

        return back()->with('status', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($category->organization_id === $organization->getKey(), 404);

        $data = $this->validated($request);

        $category->fill([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($organization, $data['name'], (int) $category->getKey()),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ])->save();

        $audit->log('category.updated', "Kategori {$category->name} diperbarui.", $category, [], $organization);

        return back()->with('status', 'Kategori diperbarui.');
    }

    public function destroy(Category $category, TenantContext $context, AuditLogger $audit): RedirectResponse
    {
        $organization = $this->organization($context);

        Gate::authorize('update', $organization);
        abort_unless($category->organization_id === $organization->getKey(), 404);

        $name = $category->name;
        $category->delete();

        $audit->log('category.deleted', "Kategori {$name} dihapus.", null, ['name' => $name], $organization);

        return back()->with('status', 'Kategori dihapus.');
    }

    protected function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    /**
     * @return array{name: string, description?: string|null, sort_order?: int|null}
     */
    protected function validated(Request $request): array
    {
        /** @var array{name: string, description?: string|null, sort_order?: int|null} $data */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        return $data;
    }

    protected function uniqueSlug(Organization $organization, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $suffix = 2;

        while ($organization->categories()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
