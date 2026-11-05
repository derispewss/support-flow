<?php

namespace App\Modules\Support\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
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
        return [
            'body' => ['required', 'string', 'max:20000'],
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
            'body.required' => 'Tulis balasan terlebih dahulu.',
            'attachments.max' => 'Maksimal 5 lampiran per balasan.',
        ];
    }
}
