<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\TicketChannel;
use App\Shared\Enums\TicketPriority;
use App\Shared\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'ticket_number' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::New,
            'priority' => TicketPriority::Normal,
            'channel' => TicketChannel::Portal,
            'category_id' => null,
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'team_id' => null,
            'sla_policy_id' => null,
            'first_response_at' => null,
            'first_response_due_at' => null,
            'due_at' => null,
            'sla_breached_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'last_activity_at' => now(),
            'satisfaction_rating' => null,
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function requestedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'requester_id' => $user->getKey(),
            'organization_id' => $user->memberships()->value('organization_id') ?? $attributes['organization_id'],
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignee_id' => $user->getKey(),
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Open,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Pending,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Closed,
            'resolved_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TicketPriority::Urgent,
        ]);
    }
}
