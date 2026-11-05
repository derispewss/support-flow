<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Membership;
use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\MembershipStatus;
use App\Shared\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'role' => UserRole::Agent,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Owner]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Superadmin]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Agent]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Customer]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => MembershipStatus::Suspended]);
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Invited,
            'joined_at' => null,
        ]);
    }
}
