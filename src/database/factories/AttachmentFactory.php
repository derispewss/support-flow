<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Support\Models\Attachment;
use App\Modules\Support\Models\Ticket;
use App\Modules\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'attachable_type' => Ticket::class,
            'attachable_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'disk' => 'local',
            'path' => 'attachments/'.Str::random(24).'.txt',
            'original_name' => 'lampiran.txt',
            'mime_type' => 'text/plain',
            'size' => 128,
        ];
    }
}
