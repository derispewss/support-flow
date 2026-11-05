<?php

namespace Tests\Feature\Admin;

use App\Modules\Support\Models\AuditLog;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\AuditLogger;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_creating_a_ticket_writes_an_audit_log(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($customer)
            ->withSession(['current_organization_id' => $organization->getKey()])
            ->post(route('tickets.store'), [
                'subject' => 'Bukti audit tiket',
                'description' => 'Pastikan pembuatan tiket tercatat.',
                'priority' => TicketPriority::Normal->value,
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->getKey(),
            'actor_id' => $customer->getKey(),
            'event' => 'ticket.created',
            'subject_type' => $ticket->getMorphClass(),
            'subject_id' => $ticket->getKey(),
        ]);
    }

    public function test_audit_index_filters_by_event_actor_and_search(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        [$agent, $agentMembership] = $this->addTenantMember($organization, UserRole::Agent);
        [$otherAgent] = $this->addTenantMember($organization, UserRole::Agent);

        $logger = app(AuditLogger::class);

        $logger->log('ticket.created', 'Tiket pertama dibuat.', null, [], $organization, $agent);
        $logger->log('tag.created', 'Tag refund dibuat.', null, [], $organization, $otherAgent);
        $logger->log('member.role_changed', 'Role anggota berubah.', null, [], $organization, $agent);

        $this->actingAsTenant($owner, $organization);

        $this->get(route('admin.audit.index'))->assertOk()->assertSee('Tiket pertama dibuat.');

        $this->get(route('admin.audit.index', ['event' => 'tag.created']))
            ->assertOk()
            ->assertSee('Tag refund dibuat.')
            ->assertDontSee('Tiket pertama dibuat.');

        $this->get(route('admin.audit.index', ['actor' => $agentMembership->user_id]))
            ->assertOk()
            ->assertSee('Tiket pertama dibuat.')
            ->assertSee('Role anggota berubah.')
            ->assertDontSee('Tag refund dibuat.');

        $this->get(route('admin.audit.index', ['q' => 'Role anggota']))
            ->assertOk()
            ->assertSee('Role anggota berubah.')
            ->assertDontSee('Tiket pertama dibuat.');
    }

    public function test_audit_log_only_shows_the_current_organization(): void
    {
        [$owner, $organization] = $this->createTenantUser(UserRole::Owner);
        [, $otherOrganization] = $this->createTenantUser(UserRole::Owner);

        $logger = app(AuditLogger::class);
        $logger->log('ticket.created', 'Aktivitas organisasi lain.', null, [], $otherOrganization);

        $this->actingAsTenant($owner, $organization);

        $this->get(route('admin.audit.index'))
            ->assertOk()
            ->assertDontSee('Aktivitas organisasi lain.');
    }

    public function test_audit_log_model_labels_known_events(): void
    {
        $log = new AuditLog(['event' => 'ticket.status_changed']);

        $this->assertSame('Status tiket', $log->eventLabel());

        $unknown = new AuditLog(['event' => 'custom.event']);

        $this->assertSame('custom.event', $unknown->eventLabel());
    }
}
