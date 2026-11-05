<?php

namespace Tests\Feature\Support;

use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketDailyStat;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_stats_command_aggregates_ticket_metrics(): void
    {
        Carbon::setTestNow('2026-11-03 12:00:00');
        [$user, $organization] = $this->createTenantUser(UserRole::Owner);
        Ticket::factory()->forOrganization($organization)->requestedBy($user)->create([
            'created_at' => Carbon::parse('2026-11-02 09:00:00'),
            'first_response_at' => Carbon::parse('2026-11-02 09:30:00'),
            'resolved_at' => Carbon::parse('2026-11-02 12:00:00'),
            'due_at' => Carbon::parse('2026-11-02 13:00:00'),
            'status' => TicketStatus::Resolved,
        ]);

        $this->artisan('support:daily-stats', ['date' => '2026-11-02'])->assertSuccessful();

        $stat = TicketDailyStat::query()
            ->where('organization_id', $organization->id)
            ->whereDate('stat_date', '2026-11-02')
            ->firstOrFail();

        $this->assertSame(1, $stat->created_count);
        $this->assertSame(1, $stat->resolved_count);
        $this->assertSame(1, $stat->first_response_count);
        $this->assertSame(1, $stat->sla_on_time_count);
        $this->assertSame(1, $stat->sla_evaluated_count);
        $this->assertSame(30, $stat->avg_first_response_minutes);
        $this->assertSame(180, $stat->avg_resolution_minutes);
    }

    public function test_reports_page_and_csv_export_are_tenant_scoped(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Owner);
        $ticket = Ticket::factory()->forOrganization($organization)->requestedBy($user)->create([
            'subject' => 'Export this ticket',
        ]);
        [$otherUser, $otherOrganization] = $this->createTenantUser(UserRole::Owner);
        Ticket::factory()->forOrganization($otherOrganization)->requestedBy($otherUser)->create([
            'subject' => 'Do not export this ticket',
        ]);
        $this->actingAsTenant($user, $organization);

        $this->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Laporan tiket');

        $response = $this->get(route('admin.reports.export'));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringContainsString($ticket->ticket_number, $csv);
        $this->assertStringContainsString('Export this ticket', $csv);
        $this->assertStringNotContainsString('Do not export this ticket', $csv);
    }

    public function test_daily_stat_model_belongs_to_organization(): void
    {
        [, $organization] = $this->createTenantUser(UserRole::Agent);
        $stat = TicketDailyStat::create([
            'organization_id' => $organization->id,
            'stat_date' => '2026-11-03',
        ]);

        $this->assertTrue($stat->organization->is($organization));
    }
}
