<?php

namespace App\Modules\Support\Http\Requests\Api;

use App\Shared\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = tenant()?->getKey();

        return [
            'subject' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'string', 'max:20000'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true),
            ],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('memberships', 'user_id')
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active'),
            ],
            'tag_ids' => ['sometimes', 'array', 'max:10'],
            'tag_ids.*' => [
                'integer',
                Rule::exists('tags', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
