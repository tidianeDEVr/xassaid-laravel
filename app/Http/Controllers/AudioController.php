<?php

namespace App\Http\Controllers;

use App\Http\Requests\AudioCategoriesRequest;
use App\Http\Requests\AudioCategoryUpdateRequest;
use App\Http\Requests\AudioRequest;
use App\Http\Requests\AudioUpdateRequest;
use App\Jobs\ImportAudioJob;
use App\Models\Audio;
use App\Models\AudioCategory;
use App\Models\AudioImport;
use App\Support\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        return view('pages.audios', [
            'categories' => $categories,
            'audios' => $audios,
            'imports' => AudioImport::with('category')->orderByDesc('id')->get(),
        ]);
    }

    /**
     * Import par liens (YouTube, Instagram...) : la piste audio de chaque
     * vidéo est téléchargée, convertie en MP3 puis uploadée, le tout en
     * arrière-plan. Une ligne par lien, 20 max — même règle que l'import
     * de vidéos.
     */
    public function importAudios(Request $request)
    {
        $request->validate([
            'category_id' => ['required', 'exists:audio_categories,id'],
            'links' => ['required', 'string'],
        ]);

        $links = collect(preg_split('/\r?\n/', $request->links))
            ->map(fn ($link) => trim($link))
            ->filter(fn ($link) => str_starts_with($link, 'http'))
            ->unique()
            ->take(20);

        if ($links->isEmpty()) {
            return redirect()->back()->withErrors(['error' => 'Aucun lien valide (un lien http par ligne).']);
        }

        foreach ($links->values() as $i => $link) {
            $import = AudioImport::create([
                'category_id' => (int) $request->category_id,
                'source_url' => mb_substr($link, 0, 500),
                'status' => AudioImport::STATUS_PROCESSING,
            ]);
            // Étalés de 2 minutes : des téléchargements enchaînés depuis la
            // même IP re-déclenchent la vérification anti-bot de YouTube.
            ImportAudioJob::dispatch($import->id)
                ->delay(now()->addSeconds($i * 120));
        }

        return redirect()->back()->with(
            'success',
            $links->count().' import(s) audio lancé(s), espacés de 2 minutes — téléchargement et conversion MP3 en arrière-plan, rechargez la page pour suivre.',
        );
    }

    /** POST /audios/imports/{import}/retry — relance un import en échec. */
    public function retryAudioImport(AudioImport $import)
    {
        if ($import->status !== AudioImport::STATUS_FAILED) {
            return redirect()->back()->withErrors(['error' => 'Cet import n\'est pas en échec.']);
        }

        $import->update([
            'status' => AudioImport::STATUS_PROCESSING,
            'error' => null,
        ]);
        ImportAudioJob::dispatch($import->id);

        return redirect()->back()->with('success', 'Import audio relancé.');
    }

    /** DELETE /audios/imports/{import} — abandonne un import. */
    public function destroyAudioImport(AudioImport $import)
    {
        $import->delete();

        return redirect()->back()->with('success', 'Import supprimé.');
    }

    public function createCategory(AudioCategoriesRequest $request)
    {
        $data = $request->validated();
        $category = new AudioCategory($data);
        $category->slug = $this->generateSlug($category->title);
        if ($request->hasFile('coverImage')) {
            $image = $request->file('coverImage');
            if (! $image->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName.'-'.time() : (string) time();
            $optimized = MediaOptimizer::optimizeImage($image, 1200, 1200, 82);
            $uploadFilename = $name.'.'.$optimized['extension'];

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI').'/upload.php';
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
                return redirect()->back()->withErrors(['error' => $this->uploadErrorMessage($response)]);
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
            if (! $audioFile->isValid()) {
                if ($request->ajax()) {
                    return response()->json(['error' => 'Le fichier audio est invalide.'], 422);
                }

                return redirect()->back()->withErrors(['error' => 'Le fichier audio est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName.'-'.time() : (string) time();

            try {
                $optimized = MediaOptimizer::optimizeAudio($audioFile, (int) env('XASSAID_AUDIO_BITRATE', 96));

                if (! empty($optimized['error'])) {
                    Log::warning('Optimisation audio échouée, fichier original utilisé.', ['file' => $uploadFilename ?? $name, 'error' => $optimized['error']]);
                }

                $uploadFilename = $name.'.'.$optimized['extension'];

                // UPLOAD FILE
                $endpoint = env('XASSAID_FILES_URI').'/upload.php';
                $response = Http::timeout(1000)->attach('file', fopen($optimized['path'], 'r'), $uploadFilename)
                    ->post($endpoint, [
                        'key' => env('XASSAID_UPLOAD_KEY'),
                        'filename' => $name,
                    ]);
            } catch (\Throwable $e) {
                Log::error('Exception lors du traitement de l\'audio.', ['error' => $e->getMessage()]);
                $errorMsg = 'Exception lors du traitement du fichier : '.$e->getMessage();
                if ($request->ajax()) {
                    return response()->json(['error' => $errorMsg], 500);
                }

                return redirect()->back()->withErrors(['error' => $errorMsg]);
            } finally {
                if (isset($optimized) && $optimized['cleanup']) {
                    @unlink($optimized['path']);
                }
            }

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $audio->pathToFile = $uploadFilename;
            } else {
                $errorMsg = $this->uploadErrorMessage($response, $optimized['error'] ?? null);
                Log::error('Upload audio refusé par le serveur de fichiers.', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
                if ($request->ajax()) {
                    return response()->json(['error' => $errorMsg], 422);
                }

                return redirect()->back()->withErrors(['error' => $errorMsg]);
            }
        }
        $category = AudioCategory::where('slug', $data['category'])->first();
        if (! $category) {
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
        if (! $category) {
            return redirect()->back()->withErrors(['error' => 'Catégorie introuvable.']);
        }
        $audio->category_id = $category->id;

        if ($request->hasFile('audio')) {
            $audioFile = $request->file('audio');
            if (! $audioFile->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier audio est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($audioFile->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName.'-'.time() : (string) time();
            $optimized = MediaOptimizer::optimizeAudio($audioFile, (int) env('XASSAID_AUDIO_BITRATE', 96));
            $uploadFilename = $name.'.'.$optimized['extension'];

            $endpoint = env('XASSAID_FILES_URI').'/upload.php';
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
                return redirect()->back()->withErrors(['error' => $this->uploadErrorMessage($response, $optimized['error'] ?? null)]);
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
            if (! $image->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
            }
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName.'-'.time() : (string) time();
            $optimized = MediaOptimizer::optimizeImage($image, 1200, 1200, 82);
            $uploadFilename = $name.'.'.$optimized['extension'];

            $endpoint = env('XASSAID_FILES_URI').'/upload.php';
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
                return redirect()->back()->withErrors(['error' => $this->uploadErrorMessage($response)]);
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

    private function uploadErrorMessage($response, ?string $optimizeError = null): string
    {
        $detail = $response->json('message');
        if (! $detail) {
            $body = trim(mb_substr($response->body(), 0, 200));
            $detail = $body !== '' ? $body : 'aucune réponse du serveur de fichiers';
        }

        $message = 'Échec de l\'upload (HTTP '.$response->status().') : '.$detail;

        if ($optimizeError) {
            $message .= ' — Optimisation : '.$optimizeError;
        }

        return $message;
    }

    public function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    public function paginateAudio($page)
    {
        if (! is_numeric($page) || (int) $page < 1) {
            return response()->json(['message' => 'Page invalide.'], 400);
        }

        $perPage = 64;
        $page = (int) $page;
        $audios = Audio::orderByTitleNatural()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response([
            'audios' => $audios,
        ]);
    }

    public function frontAudioCategoriesbyType($type)
    {
        $categories = AudioCategory::where('type', $type)
            ->orderByTitleNatural()
            ->get();

        return response([
            'categories' => $categories,
        ]);
    }

    /**
     * Liste des catégories d'un type, avec leur nombre d'audios (app V2).
     */
    public function frontAudioCategoriesbyTypeV2($type)
    {
        $categories = AudioCategory::where('type', $type)
            ->withCount('audios')
            ->orderByTitleNatural()
            ->get();

        return response([
            'categories' => $categories,
        ]);
    }

    public function frontAudiosbyCategory($category)
    {
        $category = AudioCategory::where('slug', $category)->first();

        if (! $category) {
            return response()->json([
                'message' => 'Categorie introuvable !',
            ], 404);
        }

        $audios = Audio::where('category_id', $category->id)->orderByTitleNatural()->get();

        return response([
            'category' => $category,
            'audios' => $audios,
        ]);
    }

    public function getAudioBySlug($slug)
    {
        $audio = Audio::where('slug', $slug)->with('category')->first();

        if (! $audio) {
            return response()->json([
                'message' => 'Audio introuvable !',
            ], 404);
        }

        return response([
            'audio' => $audio,
        ]);
    }

    public function sitemap()
    {
        $slugs = Audio::all()->pluck('slug');

        return response()->json($slugs);
    }
}
