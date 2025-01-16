<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:2048',
            'image_alt' => 'nullable|string|max:255',
            'sitemap_priority' => 'nullable|numeric|min:0|max:1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'content.required' => 'Le contenu de l’article est obligatoire.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L’image doit être au format jpeg, png, jpg, gif, webp ou svg.',
            'image.max' => 'La taille de l’image ne doit pas dépasser 2 Mo.'
        ];
    }
}
