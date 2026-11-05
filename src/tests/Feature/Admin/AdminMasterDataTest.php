<?php

namespace Tests\Feature\Admin;

use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\SlaPolicy;
use App\Modules\Support\Models\Tag;
use App\Modules\Tenancy\Models\Team;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_manage_categories(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        $this->actingAsTenant($admin, $organization);

        $this->post(route('admin.settings.categories.store'), [
            'name' => 'Billing',
            'description' => 'Masalah tagihan.',
            'sort_order' => 2,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $category = Category::query()->firstOrFail();

        $this->assertSame($organization->getKey(), $category->organization_id);
        $this->assertSame('Billing', $category->name);
        $this->assertSame('billing', $category->slug);
        $this->assertTrue($category->is_active);

        $this->patch(route('admin.settings.categories.update', $category), [
            'name' => 'Billing & Invoice',
            'description' => 'Tagihan dan invoice.',
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $category->refresh();

        $this->assertSame('Billing & Invoice', $category->name);
        $this->assertSame(1, $category->sort_order);

        $this->delete(route('admin.settings.categories.destroy', $category))->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->getKey()]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'category.created']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'category.updated']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'category.deleted']);
    }

    public function test_admin_can_manage_tags(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        $this->actingAsTenant($admin, $organization);

        $this->post(route('admin.settings.tags.store'), [
            'name' => 'refund',
            'color' => '#cf4a1b',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $tag = Tag::query()->firstOrFail();

        $this->assertSame('refund', $tag->name);
        $this->assertSame('#cf4a1b', $tag->color);

        $this->patch(route('admin.settings.tags.update', $tag), [
            'name' => 'pengembalian',
            'color' => '#16181c',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('pengembalian', $tag->fresh()->name);

        $this->delete(route('admin.settings.tags.destroy', $tag))->assertRedirect();

        $this->assertDatabaseMissing('tags', ['id' => $tag->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'tag.created']);
    }

    public function test_tag_name_must_be_unique_per_organization(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        Tag::factory()->create(['organization_id' => $organization->getKey(), 'name' => 'refund']);

        $this->actingAsTenant($admin, $organization);

        $this->post(route('admin.settings.tags.store'), ['name' => 'refund'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_manage_sla_policies_and_default_is_exclusive(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        $existing = SlaPolicy::factory()->create([
            'organization_id' => $organization->getKey(),
            'is_default' => true,
        ]);

        $this->actingAsTenant($admin, $organization);

        $this->post(route('admin.settings.sla.store'), [
            'name' => 'Premium',
            'first_response_minutes' => 15,
            'resolution_minutes' => 240,
            'is_default' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $policy = SlaPolicy::query()->where('name', 'Premium')->firstOrFail();

        $this->assertTrue($policy->is_default);
        $this->assertFalse($existing->fresh()->is_default);

        $this->delete(route('admin.settings.sla.destroy', $policy))->assertRedirect();

        $this->assertDatabaseMissing('sla_policies', ['id' => $policy->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'sla.created']);
    }

    public function test_admin_can_manage_teams_with_lead_and_members(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        [$agent, $agentMembership] = $this->addTenantMember($organization, UserRole::Agent);

        $this->actingAsTenant($admin, $organization);

        $this->post(route('admin.settings.teams.store'), [
            'name' => 'Layanan Pelanggan',
            'description' => 'Tim frontline.',
            'lead_id' => $agentMembership->user_id,
            'members' => [$agentMembership->user_id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $team = Team::query()->firstOrFail();

        $this->assertSame($organization->getKey(), $team->organization_id);
        $this->assertSame($agent->getKey(), $team->lead_id);
        $this->assertTrue($team->members->contains($agent));

        $this->patch(route('admin.settings.teams.update', $team), [
            'name' => 'Customer Care',
            'description' => 'Tim frontline baru.',
            'lead_id' => $agentMembership->user_id,
            'members' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Customer Care', $team->fresh()->name);

        $this->delete(route('admin.settings.teams.destroy', $team))->assertRedirect();

        $this->assertDatabaseMissing('teams', ['id' => $team->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'team.created']);
    }

    public function test_master_data_cannot_be_changed_across_tenants(): void
    {
        [$admin, $organization] = $this->createTenantUser(UserRole::Admin);
        [, $otherOrganization] = $this->createTenantUser(UserRole::Admin);
        $foreignCategory = Category::factory()->create(['organization_id' => $otherOrganization->getKey()]);

        $this->actingAsTenant($admin, $organization);

        $this->patch(route('admin.settings.categories.update', $foreignCategory), [
            'name' => 'Hijack',
            'is_active' => '1',
        ])->assertNotFound();

        $this->assertNotSame('Hijack', $foreignCategory->fresh()->name);
    }
}
