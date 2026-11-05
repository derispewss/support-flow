<?php

namespace App\Modules\Support\Http\Requests\Api;

use App\Shared\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'channel' => ['sometimes', Rule::in(['web', 'email', 'api', 'portal'])],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->where('organization_id', $organizationId),
            ],
            'tag_ids' => ['sometimes', 'array', 'max:10'],
            'tag_ids.*' => [
                'integer',
                Rule::exists('tags', 'id')->where('organization_id', $organizationId),
            ],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:png,jpg,jpeg,gif,webp,pdf,txt,csv,zip,doc,docx,xls,xlsx',
            ],
        ];
    }
}
