<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Http\Requests\AudioCategoriesRequest;
use App\Http\Requests\AudioRequest;
use App\Models\Audio;
use App\Models\AudioCategory;

class AudioController extends Controller
{

    public function renderCategories()
    {
        $categories = AudioCategory::all();
        return view('categories.audios', ['categories' => $categories]);
    }

    public function renderAudios()
    {
        $categories = AudioCategory::all();
        $audios = Audio::all();

        return view('pages.audios', ['categories' => $categories, 'audios' => $audios]);
    }



    public function createCategory(AudioCategoriesRequest $request)
    {
        $data = $request->validated();
        $category = new AudioCategory($data);
        $category->slug = $this->generateSlug($category->title);
        if ($request->hasFile('coverImage')) {
            $image = $request->file('coverImage');
            $name = time();

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($image->getPathname(), 'r'), $image->getClientOriginalName())
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $category->coverImagePath = $name . '.' . $image->getClientOriginalExtension();
            } else {
                // Gérer les erreurs
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de la création !']);
            }
        }
        $category->save();
        return redirect()->back()->with('success', 'La catégorie a été créer avec succès !');
    }

    public function createAudio(AudioRequest $request)
    {
        $data = $request->validated();
        $audio = new Audio($data);
        $audio->slug = $this->generateSlug($audio->title);
        if ($request->hasFile('audio')) {
            $audioFile = $request->file('audio');
            $name = time();

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($audioFile->getPathname(), 'r'), $audioFile->getClientOriginalName())
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $audio->pathToFile = $name . '.' . $audioFile->getClientOriginalExtension();
            } else {
                // Gérer les erreurs
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement !']);
            }
        }
        $category = AudioCategory::where('slug', $data['category'])->first();
        if ($category) $audio->category_id = $category->id;
        $audio->save();

        return redirect()->back()->with('success', 'Le fichier audio a été enregistrer !');
    }

    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public function frontAudiopage()
    {
        // $categories = AudioCategory::orderBy('title', 'asc')->get();
        $audios = Audio::orderBy('title', 'asc')->get();


        return response([
            // 'categories' => $categories,
            'audios' => $audios
        ]);
    }

    public function frontAudioCategoriesbyType($type)
    {
        $categories = AudioCategory::where('type', $type)
            ->orderBy('title', 'asc')
            ->get();

        return response([
            'categories' => $categories
        ]);
    }

    public function frontAudiosbyCategory($category)
    {
        $category = AudioCategory::where('slug', $category)->first();

        if (!$category) {
            return response()->json([
                'message' => 'Categorie introuvable !'
            ], 404);
        }

        $audios = Audio::where('category_id', $category->id)->get();

        return response([
            'category' => $category,
            'audios' => $audios
        ]);
        // return response(['ok' => 'yes']);
    }
}
