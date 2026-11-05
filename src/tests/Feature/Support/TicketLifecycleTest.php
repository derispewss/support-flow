<?php

namespace Tests\Feature\Support;

use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_numbers_are_sequential_per_organization(): void
    {
        $organization = Organization::factory()->create();
        $other = Organization::factory()->create();

        $first = Ticket::factory()->forOrganization($organization)->create();
        $second = Ticket::factory()->forOrganization($organization)->create();
        $foreign = Ticket::factory()->forOrganization($other)->create();

        $date = now()->format('Ymd');

        $this->assertSame("TKT-{$date}-0001", $first->ticket_number);
        $this->assertSame("TKT-{$date}-0002", $second->ticket_number);
        $this->assertSame("TKT-{$date}-0001", $foreign->ticket_number);
    }

    public function test_status_changes_set_and_clear_lifecycle_timestamps(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
        ]);

        $ticket->status = TicketStatus::Resolved;
        $ticket->save();

        $this->assertNotNull($ticket->refresh()->resolved_at);
        $this->assertNull($ticket->closed_at);

        $ticket->status = TicketStatus::Closed;
        $ticket->save();

        $ticket->refresh();

        $this->assertNotNull($ticket->closed_at);

        $ticket->status = TicketStatus::Open;
        $ticket->save();

        $ticket->refresh();

        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);
    }

    public function test_status_transition_matrix_rejects_invalid_moves(): void
    {
        $this->assertTrue(TicketStatus::New->canTransitionTo(TicketStatus::Open));
        $this->assertTrue(TicketStatus::Open->canTransitionTo(TicketStatus::Resolved));
        $this->assertTrue(TicketStatus::Resolved->canTransitionTo(TicketStatus::Open));
        $this->assertFalse(TicketStatus::Closed->canTransitionTo(TicketStatus::Open));
        $this->assertFalse(TicketStatus::New->canTransitionTo(TicketStatus::Resolved));
    }
}
