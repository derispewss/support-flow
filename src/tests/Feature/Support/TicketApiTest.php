<?php

namespace Tests\Feature\Support;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\Tag;
use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\MessageType;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_via_api(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $category = Category::factory()->create([
            'organization_id' => $organization->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $response = $this->withHeader('X-Organization', (string) $organization->getKey())
            ->postJson('/api/v1/tickets', [
                'subject' => 'Tidak bisa login ke portal',
                'description' => 'Muncul error 500 setelah reset password.',
                'priority' => TicketPriority::High->value,
                'category_id' => $category->getKey(),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', TicketStatus::New->value)
            ->assertJsonPath('data.priority', TicketPriority::High->value)
            ->assertJsonPath('data.requester.id', $customer->getKey());

        $number = $response->json('data.ticket_number');

        $this->assertIsString($number);
        $this->assertMatchesRegularExpression('/^TKT-\d{8}-0001$/', $number);

        $this->assertDatabaseHas('tickets', [
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tidak bisa login ke portal',
        ]);
    }

    public function test_customer_list_only_contains_their_tickets(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $other = $this->addMember($organization, UserRole::Customer);

        $own = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $other->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->getKey());
    }

    public function test_staff_list_contains_all_tenant_tickets(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $agent = $this->addMember($organization, UserRole::Agent);

        Ticket::factory()->count(3)->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($agent);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_list_supports_status_filter_and_search(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $match = Ticket::factory()->open()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Masalah login portal',
        ]);

        Ticket::factory()->resolved()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Masalah login portal',
        ]);

        Sanctum::actingAs($customer);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->getJson('/api/v1/tickets?filter[status]=open&filter[search]=login')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->getKey());
    }

    public function test_list_filters_by_tag_name(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $tag = Tag::factory()->create([
            'organization_id' => $organization->getKey(),
            'name' => 'login',
        ]);

        $match = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);
        $match->tags()->attach($tag);

        Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->getJson('/api/v1/tickets?filter[tag]=login')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->getKey());
    }

    public function test_unknown_filter_returns_validation_error(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        Sanctum::actingAs($customer);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->getJson('/api/v1/tickets?filter[unknown]=value')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('filter');
    }

    public function test_agent_can_transition_status_and_timestamps_are_set(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $agent = $this->addMember($organization, UserRole::Agent);

        $ticket = Ticket::factory()->open()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($agent);

        $headers = ['X-Organization' => (string) $organization->getKey()];

        $this->withHeaders($headers)
            ->patchJson('/api/v1/tickets/'.$ticket->getKey().'/status', [
                'status' => TicketStatus::Resolved->value,
                'note' => 'Sudah diperbaiki.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TicketStatus::Resolved->value);

        $ticket->refresh();

        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->getKey(),
            'type' => MessageType::Event->value,
        ]);

        $this->withHeaders($headers)
            ->patchJson('/api/v1/tickets/'.$ticket->getKey().'/status', [
                'status' => TicketStatus::Open->value,
            ])
            ->assertOk();

        $this->assertNull($ticket->refresh()->resolved_at);
    }

    public function test_illegal_status_transition_returns_422(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $agent = $this->addMember($organization, UserRole::Agent);

        $ticket = Ticket::factory()->closed()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($agent);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->patchJson('/api/v1/tickets/'.$ticket->getKey().'/status', [
                'status' => TicketStatus::Open->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_internal_notes_are_hidden_from_customers_but_visible_to_agents(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $agent = $this->addMember($organization, UserRole::Agent);

        $ticket = Ticket::factory()->open()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        $headers = ['X-Organization' => (string) $organization->getKey()];

        Sanctum::actingAs($agent);

        $this->withHeaders($headers)
            ->postJson('/api/v1/tickets/'.$ticket->getKey().'/messages', [
                'body' => 'Catatan internal rahasia.',
                'type' => 'note',
                'is_internal' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_internal', true);

        Sanctum::actingAs($customer);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tickets/'.$ticket->getKey().'/messages')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($agent);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tickets/'.$ticket->getKey().'/messages')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_create_internal_note(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->open()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->postJson('/api/v1/tickets/'.$ticket->getKey().'/messages', [
                'body' => 'Percobaan catatan internal.',
                'type' => 'note',
                'is_internal' => true,
            ])
            ->assertForbidden();
    }

    public function test_cross_tenant_ticket_returns_not_found(): void
    {
        [$customer] = $this->createTenantUser(UserRole::Customer);
        [$other, $otherOrganization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $otherOrganization->getKey(),
            'requester_id' => $other->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $this->withHeader('X-Organization', (string) $customer->memberships()->value('organization_id'))
            ->getJson('/api/v1/tickets/'.$ticket->getKey())
            ->assertNotFound();
    }

    public function test_customer_cannot_delete_but_admin_can(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $headers = ['X-Organization' => (string) $organization->getKey()];

        $this->withHeaders($headers)
            ->deleteJson('/api/v1/tickets/'.$ticket->getKey())
            ->assertForbidden();

        $admin = $this->addMember($organization, UserRole::Admin);
        Sanctum::actingAs($admin);

        $this->withHeaders($headers)
            ->deleteJson('/api/v1/tickets/'.$ticket->getKey())
            ->assertOk();

        $this->assertSoftDeleted('tickets', ['id' => $ticket->getKey()]);
    }

    public function test_customer_can_upload_and_download_attachment(): void
    {
        Storage::fake('local');

        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        Sanctum::actingAs($customer);

        $headers = ['X-Organization' => (string) $organization->getKey()];

        $this->withHeaders($headers)
            ->postJson('/api/v1/tickets/'.$ticket->getKey().'/attachments', [
                'attachments' => [UploadedFile::fake()->create('bukti.png', 64, 'image/png')],
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'data');

        $attachment = $ticket->attachments()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $this->withHeaders($headers)
            ->get('/api/v1/tickets/'.$ticket->getKey().'/attachments/'.$attachment->getKey().'/download')
            ->assertOk()
            ->assertDownload('bukti.png');
    }

    public function test_agent_reply_sets_first_response_and_opens_ticket(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);
        $agent = $this->addMember($organization, UserRole::Agent);

        $ticket = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'status' => TicketStatus::New,
        ]);

        Sanctum::actingAs($agent);

        $this->withHeader('X-Organization', (string) $organization->getKey())
            ->postJson('/api/v1/tickets/'.$ticket->getKey().'/messages', [
                'body' => 'Kami sedang menangani laporan Anda.',
            ])
            ->assertCreated();

        $ticket->refresh();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNotNull($ticket->first_response_at);
    }

    private function addMember(Organization $organization, UserRole $role): User
    {
        $user = User::factory()->create();

        Membership::factory()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        return $user;
    }
}
