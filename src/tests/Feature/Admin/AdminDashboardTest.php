<?php

namespace Tests\Feature\Admin;

use App\Modules\Support\Models\Ticket;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_metrics_reflect_the_current_tenant(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        Ticket::factory()->count(2)->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'status' => TicketStatus::Open,
        ]);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'status' => TicketStatus::Open,
            'assignee_id' => $agent->getKey(),
            'due_at' => now()->subDay(),
        ]);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'status' => TicketStatus::Resolved,
            'resolved_at' => now()->subHour(),
        ]);

        [$otherAgent, $otherOrganization] = $this->createTenantUser(UserRole::Agent);
        [$otherCustomer] = $this->addTenantMember($otherOrganization, UserRole::Customer);

        Ticket::factory()->create([
            'organization_id' => $otherOrganization->getKey(),
            'requester_id' => $otherCustomer->getKey(),
            'status' => TicketStatus::Open,
        ]);

        $this->actingAsTenant($agent, $organization);

        $response = $this->get(route('admin.dashboard'))->assertOk();

        /** @var array<int, array{label: string, value: string}> $metrics */
        $metrics = collect($response->viewData('metrics'))->keyBy('label');

        $this->assertSame('3', $metrics['Tiket terbuka']['value']);
        $this->assertSame('2', $metrics['Belum ditugaskan']['value']);
        $this->assertSame('1', $metrics['Lewat SLA']['value']);
        $this->assertSame('1', $metrics['Resolved 7 hari']['value']);
    }

    public function test_dashboard_lists_sla_risk_and_recent_queue(): void
    {
        [$agent, $organization] = $this->createTenantUser(UserRole::Agent);
        [$customer] = $this->addTenantMember($organization, UserRole::Customer);

        $risky = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Urgent,
            'subject' => 'Tiket hampir lewat SLA',
            'due_at' => now()->subHour(),
        ]);

        $this->actingAsTenant($agent, $organization);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tiket hampir lewat SLA')
            ->assertSee($risky->ticket_number);
    }
}
