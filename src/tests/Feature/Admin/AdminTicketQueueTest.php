<?php

namespace Tests\Feature\Admin;

use App\Modules\Support\Livewire\Admin\TicketQueue;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\Tag;
use App\Modules\Support\Models\Ticket;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTicketQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_queue_lists_only_tickets_from_the_current_tenant(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        [$otherAgent, $otherOrganization] = $this->createTenantUser(UserRole::Agent);
        [$otherCustomer] = $this->addTenantMember($otherOrganization, UserRole::Customer);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket milik tenant ini',
        ]);

        Ticket::factory()->create([
            'organization_id' => $otherOrganization->getKey(),
            'requester_id' => $otherCustomer->getKey(),
            'subject' => 'Tiket tenant lain',
        ]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketQueue::class)
            ->assertSee('Tiket milik tenant ini')
            ->assertDontSee('Tiket tenant lain');
    }

    public function test_status_priority_and_assignee_filters_narrow_the_queue(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket terbuka prioritas tinggi',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
            'assignee_id' => $agent->getKey(),
        ]);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket selesai prioritas normal',
            'status' => TicketStatus::Resolved,
            'priority' => TicketPriority::Normal,
        ]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketQueue::class)
            ->set('status', TicketStatus::Resolved->value)
            ->assertSee('Tiket selesai prioritas normal')
            ->assertDontSee('Tiket terbuka prioritas tinggi')
            ->set('status', '')
            ->set('priority', TicketPriority::High->value)
            ->assertSee('Tiket terbuka prioritas tinggi')
            ->assertDontSee('Tiket selesai prioritas normal')
            ->set('priority', '')
            ->set('assignee', 'me')
            ->assertSee('Tiket terbuka prioritas tinggi')
            ->assertDontSee('Tiket selesai prioritas normal')
            ->set('assignee', 'unassigned')
            ->assertSee('Tiket selesai prioritas normal')
            ->assertDontSee('Tiket terbuka prioritas tinggi');
    }

    public function test_search_matches_ticket_number_subject_and_requester(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer, ['name' => 'Sari Kusuma']);

        $target = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Invoice ganda pada tagihan',
        ]);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Permintaan fitur lain',
        ]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketQueue::class)
            ->set('search', 'Invoice ganda')
            ->assertSee('Invoice ganda pada tagihan')
            ->assertDontSee('Permintaan fitur lain')
            ->set('search', $target->ticket_number)
            ->assertSee('Invoice ganda pada tagihan')
            ->set('search', 'Sari Kusuma')
            ->assertSee('Invoice ganda pada tagihan');
    }

    public function test_tag_and_category_filters_work(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        $category = Category::factory()->create(['organization_id' => $organization->getKey(), 'name' => 'Billing']);
        $tag = Tag::factory()->create(['organization_id' => $organization->getKey(), 'name' => 'refund']);

        $tagged = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'category_id' => $category->getKey(),
            'subject' => 'Tiket dengan tag refund',
        ]);
        $tagged->tags()->attach($tag);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket tanpa tag',
        ]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketQueue::class)
            ->set('tag', (string) $tag->getKey())
            ->assertSee('Tiket dengan tag refund')
            ->assertDontSee('Tiket tanpa tag')
            ->set('tag', '')
            ->set('category', (string) $category->getKey())
            ->assertSee('Tiket dengan tag refund')
            ->assertDontSee('Tiket tanpa tag');
    }

    public function test_queue_paginates_and_resets_to_the_first_page_when_filtering(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        foreach (range(1, 12) as $index) {
            Ticket::factory()->create([
                'organization_id' => $organization->getKey(),
                'requester_id' => $customer->getKey(),
                'subject' => sprintf('Tiket urutan %02d', $index),
                'last_activity_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketQueue::class)
            ->set('perPage', 5)
            ->assertSee('Tiket urutan 01')
            ->assertDontSee('Tiket urutan 10')
            ->call('gotoPage', 2)
            ->assertSee('Tiket urutan 06')
            ->set('search', 'Tiket urutan 12')
            ->assertSee('Tiket urutan 12')
            ->assertSet('paginators.page', 1);
    }

    public function test_overdue_view_only_lists_open_tickets_past_due(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket lewat batas',
            'status' => TicketStatus::Open,
            'due_at' => now()->subHours(2),
        ]);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket masih aman',
            'status' => TicketStatus::Open,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketQueue::class)
            ->call('setView', 'overdue')
            ->assertSee('Tiket lewat batas')
            ->assertDontSee('Tiket masih aman');
    }
}
