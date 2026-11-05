<?php

namespace Tests\Feature\KnowledgeBase;

use App\Modules\KnowledgeBase\Models\Article;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_articles_are_visible_and_drafts_are_hidden(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Agent, ['email_verified_at' => now()]);
        $published = Article::create([
            'organization_id' => $organization->id,
            'author_id' => $user->id,
            'title' => 'Reset password',
            'slug' => 'reset-password',
            'body' => 'Follow these steps.',
            'status' => 'published',
            'published_at' => now(),
        ]);
        Article::create([
            'organization_id' => $organization->id,
            'author_id' => $user->id,
            'title' => 'Internal draft',
            'slug' => 'internal-draft',
            'body' => 'Not public.',
            'status' => 'draft',
        ]);

        $this->withoutVite()
            ->get(route('help.organization', $organization))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Internal draft');
    }

    public function test_article_detail_is_tenant_isolated(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Agent, ['email_verified_at' => now()]);
        [, $otherOrganization] = $this->createTenantUser(UserRole::Agent);
        $article = Article::create([
            'organization_id' => $otherOrganization->id,
            'author_id' => $user->id,
            'title' => 'Private article',
            'slug' => 'private-article',
            'body' => 'Private.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->withoutVite()
            ->get(route('help.article', [$organization, $article]))
            ->assertNotFound();
    }

    public function test_owner_can_create_an_article_from_admin_console(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Owner, ['email_verified_at' => now()]);
        $this->actingAsTenant($user, $organization);

        $this->post(route('admin.knowledge-base.articles.store'), [
            'title' => 'Verifikasi email',
            'body' => 'Buka tautan verifikasi.',
            'status' => 'published',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'organization_id' => $organization->id,
            'slug' => 'verifikasi-email',
            'status' => 'published',
        ]);
    }
}
