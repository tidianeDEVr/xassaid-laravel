<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppRegisterRequest extends FormRequest
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
            'username' => ['required', 'string', 'regex:/^[a-z0-9_.]{3,30}$/', 'unique:app_users,username'],
            'display_name' => ['required', 'string', 'min:2', 'max:50'],
            'password' => ['required', 'string', 'min:6', 'max:72'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Le pseudo ne peut contenir que des lettres minuscules, chiffres, points et tirets bas (3 à 30 caractères).',
            'username.unique' => 'Ce pseudo est déjà pris.',
        ];
    }
}
