<?php

namespace Tests\Feature\Admin;

use App\Modules\Support\Livewire\Admin\TicketDetail;
use App\Modules\Support\Models\AuditLog;
use App\Modules\Support\Models\CannedResponse;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\Tag;
use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MessageType;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTicketDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_agent_can_reply_to_a_ticket(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('reply', 'Kami sudah memeriksa tagihan Anda.')
            ->set('internal', false)
            ->call('postReply')
            ->assertHasNoErrors()
            ->assertSet('reply', '');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->getKey(),
            'user_id' => $agent->getKey(),
            'type' => MessageType::Reply->value,
            'body' => 'Kami sudah memeriksa tagihan Anda.',
            'is_internal' => false,
        ]);
    }

    public function test_internal_note_is_stored_and_marked_internal(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('reply', 'CATATAN-INTERNAL-AGENT')
            ->set('internal', true)
            ->call('postReply')
            ->assertHasNoErrors()
            ->assertSee('CATATAN-INTERNAL-AGENT');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->getKey(),
            'type' => MessageType::Note->value,
            'body' => 'CATATAN-INTERNAL-AGENT',
            'is_internal' => true,
        ]);
    }

    public function test_reply_requires_a_body_or_attachment(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('reply', '')
            ->call('postReply')
            ->assertHasErrors('reply');
    }

    public function test_agent_can_transition_status_and_event_is_logged(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey(), ['status' => TicketStatus::Open]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('status', TicketStatus::Resolved->value)
            ->set('statusNote', 'Sudah dijelaskan ke customer.')
            ->call('changeStatus')
            ->assertHasNoErrors();

        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->getKey(),
            'type' => MessageType::Event->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->getKey(),
            'event' => 'ticket.status_changed',
            'actor_id' => $agent->getKey(),
        ]);
    }

    public function test_illegal_status_transition_is_rejected(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey(), ['status' => TicketStatus::New]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('status', TicketStatus::Closed->value)
            ->call('changeStatus')
            ->assertHasErrors();

        $this->assertSame(TicketStatus::New, $ticket->fresh()->status);
    }

    public function test_agent_cannot_assign_or_update_classification(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $category = Category::factory()->create(['organization_id' => $organization->getKey()]);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('assigneeId', (string) $agent->getKey())
            ->call('saveAssignment')
            ->assertForbidden();

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('priority', TicketPriority::Urgent->value)
            ->set('categoryId', (string) $category->getKey())
            ->call('saveClassification')
            ->assertForbidden();

        $ticket->refresh();

        $this->assertNull($ticket->assignee_id);
        $this->assertSame(TicketPriority::Normal, $ticket->priority);
        $this->assertNull($ticket->category_id);
    }

    public function test_agent_cannot_sync_tags_or_manage_watchers(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer, $customerMembership] = $this->addTenantMember($organization, UserRole::Customer);
        $tag = Tag::factory()->create(['organization_id' => $organization->getKey()]);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('tagIds', [$tag->getKey()])
            ->call('saveTags')
            ->assertForbidden();

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('watcherId', (string) $customerMembership->user_id)
            ->call('addWatcher')
            ->assertForbidden();

        $this->assertFalse($ticket->fresh()->tags->contains($tag));
        $this->assertFalse($ticket->fresh()->watchers->contains($customer));
    }

    public function test_tag_from_another_organization_is_rejected(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Owner);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        [, $otherOrganization] = $this->createTenantUser(UserRole::Agent);
        $foreignTag = Tag::factory()->create(['organization_id' => $otherOrganization->getKey()]);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('tagIds', [$foreignTag->getKey()])
            ->call('saveTags')
            ->assertHasErrors('tagIds');

        $this->assertCount(0, $ticket->fresh()->tags);
    }

    public function test_canned_response_populates_the_reply(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey());

        $canned = CannedResponse::factory()->create([
            'organization_id' => $organization->getKey(),
            'title' => 'Salam pembuka',
            'body' => 'Halo, terima kasih sudah menghubungi kami.',
        ]);

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->call('applyCanned', $canned->getKey())
            ->assertSet('reply', $canned->body);
    }

    public function test_only_managers_can_delete_a_ticket(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);
        $ticket = $this->ticket($organization, $customer->getKey());

        $this->actingAsTenant($agent, $organization);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->call('deleteTicket')
            ->assertForbidden();

        $this->assertNull($ticket->fresh()->deleted_at);

        [$owner, $ownerOrganization] = $this->createTenantUser(UserRole::Owner);
        [$ownerCustomer] = $this->addTenantMember($ownerOrganization, UserRole::Customer);
        $ownerTicket = $this->ticket($ownerOrganization, $ownerCustomer->getKey());

        $this->actingAsTenant($owner, $ownerOrganization);

        Livewire::test(TicketDetail::class, ['ticket' => $ownerTicket])
            ->call('deleteTicket')
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertSoftDeleted('tickets', ['id' => $ownerTicket->getKey()]);
        $this->assertTrue(AuditLog::query()->where('event', 'ticket.deleted')->exists());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ticket(Organization $organization, int $requesterId, array $attributes = []): Ticket
    {
        return Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $requesterId,
            ...$attributes,
        ]);
    }
}
