<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'organization_name' => ['nullable', 'string', 'max:120'],
            'redirect' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'terms' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->input('email'))) {
            $this->merge([
                'email' => Str::lower(trim($this->input('email'))),
            ]);
        }

        if ($this->has('organization_name') && is_string($this->input('organization_name'))) {
            $name = trim($this->input('organization_name'));

            $this->merge([
                'organization_name' => $name === '' ? null : $name,
            ]);
        }
    }
}
