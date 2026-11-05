<?php

namespace Tests\Feature\Support;

use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\Webhook;
use App\Modules\Support\Notifications\SlaBreachedNotification;
use App\Modules\Support\Services\WebhookService;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_check_marks_overdue_ticket_and_notifies_requester(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Customer);
        $ticket = Ticket::factory()->forOrganization($organization)->requestedBy($user)->create([
            'due_at' => now()->subMinute(),
        ]);
        Notification::fake();

        $this->artisan('support:sla-check')->assertSuccessful();

        $this->assertNotNull($ticket->fresh()->sla_breached_at);
        Notification::assertSentTo($user, SlaBreachedNotification::class);
    }

    public function test_webhook_delivery_is_signed_and_logged(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Agent);
        $webhook = Webhook::create([
            'organization_id' => $organization->id,
            'name' => 'CRM',
            'url' => 'https://example.test/hooks',
            'secret' => 'secret',
            'events' => ['ticket.created'],
        ]);
        $ticket = Ticket::factory()->forOrganization($organization)->requestedBy($user)->create();
        Http::fake(['https://example.test/hooks' => Http::response(['ok' => true], 200)]);

        app(WebhookService::class)->dispatch($ticket, 'ticket.created');
        $delivery = $webhook->deliveries()->firstOrFail();

        Http::assertSent(function ($request): bool {
            return $request->hasHeader('X-SupportFlow-Event', 'ticket.created')
                && $request->hasHeader('X-SupportFlow-Signature')
                && $request->url() === 'https://example.test/hooks';
        });
        $this->assertNotNull($delivery->fresh()->delivered_at);
        $this->assertSame(1, $delivery->fresh()->attempts);
    }

    public function test_webhook_events_are_scoped_to_the_ticket_organization(): void
    {
        [$user, $organization] = $this->createTenantUser(UserRole::Agent);
        [, $otherOrganization] = $this->createTenantUser(UserRole::Agent);
        $webhook = Webhook::create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other CRM',
            'url' => 'https://example.test/other',
            'secret' => 'secret',
            'events' => ['*'],
        ]);

        app(WebhookService::class)->dispatch(
            Ticket::factory()->forOrganization($organization)->requestedBy($user)->create(),
            'ticket.created',
        );

        $this->assertDatabaseCount('webhook_deliveries', 0);
        $this->assertSame(TicketStatus::New, Ticket::latest()->firstOrFail()->status);
        $this->assertNotNull($webhook);
    }
}
