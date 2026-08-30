<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => strtolower(trim($this->username))]);
        }
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
