<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 512000 Ko = 500 Mo, aligné sur php.ini (upload_max_filesize).
            'video' => ['required', 'file', 'mimes:mp4,mov,m4v,webm', 'max:512000'],
            'description' => ['required', 'string', 'max:2000'],
            'khassida_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'video.max' => 'La vidéo dépasse la taille maximale autorisée (500 Mo).',
            'video.mimes' => 'Format non pris en charge (mp4, mov, m4v ou webm attendu).',
        ];
    }
}
