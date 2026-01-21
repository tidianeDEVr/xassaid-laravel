<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Http\Requests\AudioCategoriesRequest;
use App\Http\Requests\AudioRequest;
use App\Http\Requests\AudioUpdateRequest;
use App\Http\Requests\AudioCategoryUpdateRequest;
use App\Models\Audio;
use App\Models\AudioCategory;
use App\Support\MediaOptimizer;

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

        // Create Static
        // [
        //     "title" => 'Foulkou 01',
        //     "slug" => 'foulkou-01',
        //     "pathToFile" => "Foulkou_01.mp3",
        //     "category_id" => 31
        // ],
        $toCreates = [];

        // foreach ($toCreates as $audio) {
        //     $aud = new Audio($audio);
        //     $aud->save();
        // }

        return view('pages.audios', ['categories' => $categories, 'audios' => $audios]);
    }



    public function createCategory(AudioCategoriesRequest $request)
    {
        $data = $request->validated();
        $category = new AudioCategory($data);
        $category->slug = $this->generateSlug($category->title);
        if ($request->hasFile('coverImage')) {
            $image = $request->file('coverImage');
            if (!$image->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
            $optimized = MediaOptimizer::optimizeImage($image, 1200, 1200, 82);
            $uploadFilename = $name . '.' . $optimized['extension'];

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($optimized['path'], 'r'), $uploadFilename)
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            if ($optimized['cleanup']) {
                @unlink($optimized['path']);
            }

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $category->coverImagePath = $uploadFilename;
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
            if (!$audioFile->isValid()) {
                if ($request->ajax()) {
                    return response()->json(['error' => 'Le fichier audio est invalide.'], 422);
                }
                return redirect()->back()->withErrors(['error' => 'Le fichier audio est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
            $optimized = MediaOptimizer::optimizeAudio($audioFile, (int) env('XASSAID_AUDIO_BITRATE', 96));
            $uploadFilename = $name . '.' . $optimized['extension'];

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($optimized['path'], 'r'), $uploadFilename)
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            if ($optimized['cleanup']) {
                @unlink($optimized['path']);
            }

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $audio->pathToFile = $uploadFilename;
            } else {
                $errorMsg = $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement !';
                if ($request->ajax()) {
                    return response()->json(['error' => $errorMsg], 422);
                }
                return redirect()->back()->withErrors(['error' => $errorMsg]);
            }
        }
        $category = AudioCategory::where('slug', $data['category'])->first();
        if (!$category) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Catégorie introuvable.'], 422);
            }
            return redirect()->back()->withErrors(['error' => 'Catégorie introuvable.']);
        }
        $audio->category_id = $category->id;
        $audio->save();

        if ($request->ajax()) {
            session()->flash('success', 'Le fichier audio a été enregistré !');
            return response()->json(['success' => 'Le fichier audio a été enregistré !']);
        }
        return redirect()->back()->with('success', 'Le fichier audio a été enregistré !');
    }

    public function updateAudio(AudioUpdateRequest $request, Audio $audio)
    {
        $data = $request->validated();
        $audio->title = $data['title'];
        $audio->slug = $this->generateSlug($audio->title);

        $category = AudioCategory::where('slug', $data['category'])->first();
        if (!$category) {
            return redirect()->back()->withErrors(['error' => 'Catégorie introuvable.']);
        }
        $audio->category_id = $category->id;

        if ($request->hasFile('audio')) {
            $audioFile = $request->file('audio');
            if (!$audioFile->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier audio est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
            $optimized = MediaOptimizer::optimizeAudio($audioFile, (int) env('XASSAID_AUDIO_BITRATE', 96));
            $uploadFilename = $name . '.' . $optimized['extension'];

            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($optimized['path'], 'r'), $uploadFilename)
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            if ($optimized['cleanup']) {
                @unlink($optimized['path']);
            }

            if ($response->successful() && $response->json('status') === 'success') {
                $audio->pathToFile = $uploadFilename;
            } else {
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement !']);
            }
        }

        $audio->save();

        return redirect()->back()->with('success', 'Le fichier audio a été modifié !');
    }

    public function deleteAudio(Audio $audio)
    {
        $audio->delete();

        return redirect()->back()->with('success', 'Le fichier audio a été supprimé !');
    }

    public function updateCategory(AudioCategoryUpdateRequest $request, AudioCategory $category)
    {
        $data = $request->validated();
        $category->title = $data['title'];
        $category->type = $data['type'];
        $category->slug = $this->generateSlug($category->title);

        if ($request->hasFile('coverImage')) {
            $image = $request->file('coverImage');
            if (!$image->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
            $optimized = MediaOptimizer::optimizeImage($image, 1200, 1200, 82);
            $uploadFilename = $name . '.' . $optimized['extension'];

            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($optimized['path'], 'r'), $uploadFilename)
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            if ($optimized['cleanup']) {
                @unlink($optimized['path']);
            }

            if ($response->successful() && $response->json('status') === 'success') {
                $category->coverImagePath = $uploadFilename;
            } else {
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de la modification !']);
            }
        }

        $category->save();

        return redirect()->back()->with('success', 'La catégorie a été modifiée avec succès !');
    }

    public function deleteCategory(AudioCategory $category)
    {
        if ($category->audios()->exists()) {
            return redirect()->back()->withErrors(['error' => 'Supprimez d\'abord les audios de cette catégorie.']);
        }

        $category->delete();

        return redirect()->back()->with('success', 'La catégorie a été supprimée !');
    }

    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public function paginateAudio($page)
    {
        if (!is_numeric($page) || (int) $page < 1) {
            return response()->json(['message' => 'Page invalide.'], 400);
        }

        $perPage = 64;
        $page = (int) $page;
        $audios = Audio::skip(($page - 1) * $perPage)
            ->take($perPage)
            ->orderBy('title', 'asc')->get();

        return response([
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

        $audios = Audio::where('category_id', $category->id)->orderBy('title', 'asc')->get();

        return response([
            'category' => $category,
            'audios' => $audios
        ]);
    }

    public function getAudioBySlug($slug)
    {
        $audio = Audio::where('slug', $slug)->with('category')->first();

        if (!$audio) {
            return response()->json([
                'message' => 'Audio introuvable !'
            ], 404);
        }

        return response([
            'audio' => $audio
        ]);
    }

    public function sitemap()
    {
        $slugs = Audio::all()->pluck('slug');
        return response()->json($slugs);
    }
}
