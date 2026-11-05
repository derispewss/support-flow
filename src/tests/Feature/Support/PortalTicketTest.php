<?php

namespace Tests\Feature\Support;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Category;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
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
use Tests\TestCase;

class PortalTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_customer_can_create_ticket_with_attachment(): void
    {
        Storage::fake('local');

        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $category = Category::factory()->create([
            'organization_id' => $organization->getKey(),
        ]);

        $file = UploadedFile::fake()->create('screenshot.png', 120, 'image/png');

        $response = $this->actingAs($customer)->post(route('tickets.store'), [
            'subject' => 'Tidak bisa login ke portal',
            'description' => 'Muncul error 500 setelah reset password.',
            'priority' => TicketPriority::High->value,
            'category_id' => $category->getKey(),
            'attachments' => [$file],
        ]);

        $ticket = Ticket::query()->firstOrFail();

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame($organization->getKey(), $ticket->organization_id);
        $this->assertSame($customer->getKey(), $ticket->requester_id);
        $this->assertSame(TicketStatus::New, $ticket->status);
        $this->assertSame(TicketPriority::High, $ticket->priority);
        $this->assertMatchesRegularExpression('/^TKT-\d{8}-0001$/', $ticket->ticket_number);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->getKey(),
            'user_id' => $customer->getKey(),
            'type' => MessageType::Reply->value,
            'body' => 'Muncul error 500 setelah reset password.',
        ]);

        $attachment = $ticket->attachments()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame('screenshot.png', $attachment->original_name);
    }

    public function test_customer_only_sees_their_own_tickets_in_the_list(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $other = $this->addMember($organization, UserRole::Customer);

        $own = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
            'subject' => 'Tiket milik saya sendiri',
        ]);

        $foreign = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $other->getKey(),
            'subject' => 'Tiket milik customer lain',
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($own->subject)
            ->assertDontSee($foreign->subject);
    }

    public function test_customer_cannot_open_ticket_from_another_tenant(): void
    {
        [$customer] = $this->createTenantUser(UserRole::Customer);
        [$other, $otherOrganization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $otherOrganization->getKey(),
            'requester_id' => $other->getKey(),
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertNotFound();
    }

    public function test_internal_notes_are_not_rendered_for_customers(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        TicketMessage::query()->create([
            'ticket_id' => $ticket->getKey(),
            'user_id' => $customer->getKey(),
            'type' => MessageType::Reply,
            'body' => 'Balasan publik yang aman.',
            'is_internal' => false,
        ]);

        TicketMessage::query()->create([
            'ticket_id' => $ticket->getKey(),
            'user_id' => $customer->getKey(),
            'type' => MessageType::Note,
            'body' => 'CATATAN-RAHASIA-INTERNAL',
            'is_internal' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Balasan publik yang aman.')
            ->assertDontSee('CATATAN-RAHASIA-INTERNAL');
    }

    public function test_customer_can_reply_to_their_ticket(): void
    {
        [$customer, $organization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $organization->getKey(),
            'requester_id' => $customer->getKey(),
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'Informasi tambahan dari customer.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->getKey(),
            'user_id' => $customer->getKey(),
            'type' => MessageType::Reply->value,
            'body' => 'Informasi tambahan dari customer.',
            'is_internal' => false,
        ]);
    }

    public function test_customer_cannot_reply_to_another_tenants_ticket(): void
    {
        [$customer] = $this->createTenantUser(UserRole::Customer);
        [$other, $otherOrganization] = $this->createTenantUser(UserRole::Customer);

        $ticket = Ticket::factory()->create([
            'organization_id' => $otherOrganization->getKey(),
            'requester_id' => $other->getKey(),
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), ['body' => 'Percobaan lintas tenant.'])
            ->assertNotFound();
    }

    public function test_ticket_creation_validates_input(): void
    {
        [$customer] = $this->createTenantUser(UserRole::Customer);

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'subject' => '',
                'description' => '',
                'priority' => 'unknown',
            ])
            ->assertSessionHasErrors(['subject', 'description', 'priority']);
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
