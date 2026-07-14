<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AudioRequest extends FormRequest
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
            'title' => 'required|min:4',
            'category' => 'required|exists:audio_categories,slug',
            // mp4/aac : les fichiers .m4a sont détectés comme du MP4 par la validation de contenu
            'audio' => 'required|mimes:mp3,wav,ogg,oga,m4a,mp4,aac,flac|max:102400',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.min' => 'Le titre doit contenir au moins 4 caractères.',
            'category.required' => 'La catégorie est obligatoire.',
            'category.exists' => 'Cette catégorie n\'existe pas.',
            'audio.required' => 'Le fichier audio est obligatoire.',
            'audio.mimes' => 'Format audio non supporté : le contenu du fichier n\'est pas reconnu comme mp3, wav, ogg, m4a, aac ou flac (l\'extension du nom de fichier ne suffit pas, c\'est le contenu réel qui est vérifié).',
            'audio.max' => 'Le fichier audio dépasse la taille maximale de 100 Mo.',
        ];
    }
}
