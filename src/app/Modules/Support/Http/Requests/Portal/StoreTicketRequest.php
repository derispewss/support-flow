<?php

namespace App\Modules\Support\Http\Requests\Portal;

use App\Shared\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && tenant() !== null;
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
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true),
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:png,jpg,jpeg,gif,webp,pdf,txt,csv,zip,doc,docx,xls,xlsx',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Subjek tiket wajib diisi.',
            'description.required' => 'Deskripsi masalah wajib diisi.',
            'category_id.exists' => 'Kategori tidak tersedia untuk organisasi ini.',
            'attachments.max' => 'Maksimal 5 lampiran per tiket.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
        ];
    }
}
