<?php

namespace Database\Factories;

use App\Modules\Identity\Models\SocialAccount;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'google',
            'provider_user_id' => Str::uuid()->toString(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => null,
        ];
    }
}
