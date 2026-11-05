<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketMessage;
use App\Shared\Enums\MessageType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketMessage>
 */
class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'type' => MessageType::Reply,
            'body' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }

    public function internalNote(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::Note,
            'is_internal' => true,
        ]);
    }

    public function event(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::Event,
            'is_internal' => false,
        ]);
    }
}
