<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileCreateRequest;
use App\Http\Requests\FileUpdateRequest;
use App\Models\File;
use App\Support\Slugger;
use App\Support\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FileController extends Controller
{
    public function renderFiles(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $files = File::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%");
            }))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('pages.library', ['files' => $files, 'q' => $q]);
    }

    public function createFile(FileCreateRequest $request)
    {
        $data = $request->validated();
        $file = new File;
        $file->title = $data['title'];
        $file->slug = Slugger::unique($file->title, 'files');

        $uploaded = $request->file('file');
        if (! $uploaded || ! $uploaded->isValid()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Le fichier est invalide.'], 422);
            }

            return redirect()->back()->withErrors(['error' => 'Le fichier est invalide.']);
        }

        $baseName = MediaOptimizer::normalizeFilename(pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME));
        $name = $baseName !== '' ? $baseName.'-'.time() : (string) time();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $uploadFilename = $name.'.'.$extension;

        $endpoint = config('services.xassaid.files_uri').'/upload.php';
        $response = Http::timeout(1000)->attach('file', fopen($uploaded->getPathname(), 'r'), $uploadFilename)
            ->post($endpoint, [
                'key' => config('services.xassaid.upload_key'),
                'filename' => $name,
            ]);

        if ($response->successful() && $response->json('status') === 'success') {
            $file->pathToFile = $uploadFilename;
        } else {
            $errorMsg = $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement !';
            if ($request->ajax()) {
                return response()->json(['error' => $errorMsg], 422);
            }

            return redirect()->back()->withErrors(['error' => $errorMsg]);
        }

        $file->save();

        if ($request->ajax()) {
            session()->flash('success', 'Le fichier a été enregistré !');

            return response()->json(['success' => 'Le fichier a été enregistré !']);
        }

        return redirect()->back()->with('success', 'Le fichier a été enregistré !');
    }

    public function paginateFiles($page)
    {
        if (! is_numeric($page) || (int) $page < 1) {
            return response()->json(['message' => 'Page invalide.'], 400);
        }

        $perPage = 64;
        $page = (int) $page;

        $files = File::skip(($page - 1) * $perPage)->take($perPage)->get();

        return response([
            'files' => $files,
        ]);
    }

    public function getFileBySlug($slug)
    {
        $file = File::findBySlugOrRedirect($slug);

        if (! $file) {
            return response('File not found!', 404);
        }

        return response()->json($file);
    }

    public function updateFile(FileUpdateRequest $request, File $file)
    {
        $data = $request->validated();
        $file->title = $data['title'];
        $wanted = $data['slug'] ?: $file->title;
        if (Slugger::base($wanted) !== $file->slug) {
            $file->slug = Slugger::unique($wanted, 'files', $file->id);
        }
        $file->save();

        return redirect()->back()->with('success', 'Le fichier a été modifié !');
    }

    public function deleteFile(Request $request, File $file)
    {
        $file->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Le fichier a été supprimé !');
    }

    public function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    public function generateTitle($string)
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        $title = preg_replace('/[^A-Za-z0-9-]+/', ' ', $string);
        $title = strtolower($title);
        $title = preg_replace('/-+/', ' ', $title);
        $title = trim($title, ' ');
        $title = ucwords($title);

        return $title;
    }

    public function sitemap()
    {
        $slugs = File::all()->pluck('slug');

        return response()->json($slugs);
    }
}
