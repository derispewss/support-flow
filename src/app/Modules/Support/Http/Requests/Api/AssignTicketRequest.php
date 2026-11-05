<?php

namespace App\Modules\Support\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
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
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('memberships', 'user_id')
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active'),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
