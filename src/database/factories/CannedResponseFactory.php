<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\CannedResponse;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CannedResponse>
 */
class CannedResponseFactory extends Factory
{
    protected $model = CannedResponse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
