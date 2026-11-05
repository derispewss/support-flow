<?php

namespace Tests\Feature\Support;

use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_uses_plan_rate_limit_headers(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Customer);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Organization', (string) $organization->id)
            ->getJson('/api/v1/tickets');

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_messages_support_cursor_pagination(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Customer);
        $ticket = Ticket::factory()->forOrganization($organization)->requestedBy($user)->create();
        TicketMessage::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Organization', (string) $organization->id)
            ->getJson("/api/v1/tickets/{$ticket->id}/messages?cursor=1&per_page=2")
            ->assertOk()
            ->assertJsonStructure(['data', 'links' => ['next', 'prev'], 'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor']]);
    }

    public function test_search_fallback_returns_matching_ticket_on_sqlite(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Customer);
        $ticket = Ticket::factory()->forOrganization($organization)->requestedBy($user)->create([
            'subject' => 'Invoice tidak terbaca',
        ]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Organization', (string) $organization->id)
            ->getJson('/api/v1/tickets?filter[search]=Invoice')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ticket->id);
    }
}
