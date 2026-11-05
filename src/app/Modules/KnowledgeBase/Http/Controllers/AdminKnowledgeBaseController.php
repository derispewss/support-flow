<?php

namespace App\Modules\KnowledgeBase\Http\Controllers;

use App\Modules\KnowledgeBase\Models\Article;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminKnowledgeBaseController
{
    public function index(TenantContext $context): View
    {
        $organization = $this->organization($context);

        return view('pages.admin.knowledge-base.index', [
            'organization' => $organization,
            'articles' => $organization->articles()->with('category')->latest()->paginate(20),
            'categories' => $organization->kbCategories()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, TenantContext $context): RedirectResponse
    {
        $organization = $this->organization($context);
        $data = $this->validated($request, $organization);
        $organization->articles()->create([
            ...$data,
            'slug' => $this->slug($organization, $data['title']),
            'author_id' => $request->user()->id,
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return back()->with('status', 'Artikel dibuat.');
    }

    public function update(Request $request, Article $article, TenantContext $context): RedirectResponse
    {
        $organization = $this->organization($context);
        abort_unless($article->organization_id === $organization->id, 404);
        $data = $this->validated($request, $organization);
        $article->update([
            ...$data,
            'slug' => $this->slug($organization, $data['title'], $article->id),
            'published_at' => $data['status'] === 'published' ? ($article->published_at ?? now()) : null,
        ]);

        return back()->with('status', 'Artikel diperbarui.');
    }

    public function destroy(Article $article, TenantContext $context): RedirectResponse
    {
        $organization = $this->organization($context);
        abort_unless($article->organization_id === $organization->id, 404);
        $article->delete();

        return back()->with('status', 'Artikel dihapus.');
    }

    private function organization(TenantContext $context): Organization
    {
        $organization = $context->organization();
        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    /**
     * @return array{title: string, body: string, kb_category_id?: int|null, status: string}
     */
    private function validated(Request $request, Organization $organization): array
    {
        /** @var array{title: string, body: string, kb_category_id?: int|null, status: string} $data */
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'kb_category_id' => [
                'nullable',
                'integer',
                Rule::exists('kb_categories', 'id')->where('organization_id', $organization->id),
            ],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        return $data;
    }

    private function slug(Organization $organization, string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title) ?: 'artikel';
        $slug = $base;
        $suffix = 2;
        while ($organization->articles()->where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
