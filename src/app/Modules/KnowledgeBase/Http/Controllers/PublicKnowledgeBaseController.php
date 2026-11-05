<?php

namespace App\Modules\KnowledgeBase\Http\Controllers;

use App\Modules\KnowledgeBase\Models\Article;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicKnowledgeBaseController
{
    public function index(Request $request, Organization $organization): View
    {
        $articles = Article::query()->where('organization_id', $organization->id)->published()->with('category')
            ->when($request->string('q')->trim()->value(), function ($query, string $search): void {
                $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%"));
            })
            ->when($request->string('category')->trim()->value(), fn ($query, string $slug) => $query->whereHas('category', fn ($category) => $category->where('slug', $slug)))
            ->latest('published_at')->paginate(12)->withQueryString();

        return view('pages.public.knowledge-base.index', [
            'organization' => $organization,
            'articles' => $articles,
            'categories' => $organization->kbCategories()->withCount(['articles' => fn ($query) => $query->published()])->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Organization $organization, Article $article): View
    {
        abort_unless($article->organization_id === $organization->id, 404);
        abort_unless($article->status === 'published' && $article->published_at !== null, 404);
        $article->increment('views');

        return view('pages.public.knowledge-base.show', compact('organization', 'article'));
    }

    public function vote(Request $request, Organization $organization, Article $article): RedirectResponse
    {
        abort_unless($article->organization_id === $organization->id, 404);
        abort_unless($article->status === 'published', 404);

        $request->validate(['helpful' => ['required', 'boolean']]);
        $userId = $request->user()?->id;
        $fingerprint = $userId === null ? hash('sha256', $request->ip().'|'.$request->userAgent()) : null;

        DB::transaction(function () use ($article, $userId, $fingerprint, $request): void {
            $vote = $article->votes()
                ->where('user_id', $userId)
                ->when($userId === null, fn ($query) => $query->where('fingerprint', $fingerprint))
                ->first();
            if ($vote !== null) {
                $vote->update(['helpful' => $request->boolean('helpful')]);

                return;
            }
            $article->votes()->create(['user_id' => $userId, 'fingerprint' => $fingerprint, 'helpful' => $request->boolean('helpful')]);
        });

        return back()->with('status', 'Terima kasih atas masukan Anda.');
    }
}
