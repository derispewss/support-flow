<?php

namespace Database\Factories;

use App\Modules\Tenancy\Models\Organization;
use App\Shared\Enums\OrganizationPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'plan' => OrganizationPlan::Free,
            'trial_ends_at' => null,
            'settings' => null,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => OrganizationPlan::Pro,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => OrganizationPlan::Enterprise,
        ]);
    }
}
