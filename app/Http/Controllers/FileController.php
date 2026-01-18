<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileCreateRequest;
use App\Http\Requests\FileUpdateRequest;
use App\Models\File;
use App\Support\MediaOptimizer;
use Illuminate\Support\Facades\Http;

class FileController extends Controller
{
    public function renderFiles()
    {
        $files = File::all();

        return view('pages.library', ['files' => $files]);
    }

    public function createFile(FileCreateRequest $request)
    {
        $data = $request->validated();
        $file = new File();
        $file->title = $data['title'];
        $file->slug = $this->generateSlug($file->title);

        $uploaded = $request->file('file');
        if (!$uploaded || !$uploaded->isValid()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Le fichier est invalide.'], 422);
            }
            return redirect()->back()->withErrors(['error' => 'Le fichier est invalide.']);
        }

        $baseName = MediaOptimizer::normalizeFilename(pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME));
        $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $uploadFilename = $name . '.' . $extension;

        $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
        $response = Http::timeout(1000)->attach('file', fopen($uploaded->getPathname(), 'r'), $uploadFilename)
            ->post($endpoint, [
                'key' => env('XASSAID_UPLOAD_KEY'),
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
        if (!is_numeric($page) || (int) $page < 1) {
            return response()->json(['message' => 'Page invalide.'], 400);
        }

        $perPage = 64;
        $page = (int) $page;

        $files = File::skip(($page - 1) * $perPage)->take($perPage)->get();

        return response([
            'files' => $files
        ]);
    }

    public function getFileBySlug($slug)
    {
        $file = File::where('slug', $slug)->first();

        if (!$file) {
            return response('File not found!', 404);
        }

        return response()->json($file);
    }

    public function updateFile(FileUpdateRequest $request, File $file)
    {
        $data = $request->validated();
        $file->title = $data['title'];
        $file->slug = $data['slug'] ?: $this->generateSlug($file->title);
        $file->save();

        return redirect()->back()->with('success', 'Le fichier a été modifié !');
    }

    public function deleteFile(File $file)
    {
        $file->delete();

        return redirect()->back()->with('success', 'Le fichier a été supprimé !');
    }

    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }


    function generateTitle($string)
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        $title = preg_replace('/[^A-Za-z0-9-]+/', ' ', $string);
        $title = strtolower($title);
        $title = preg_replace('/-+/', ' ', $title);
        $title = trim($title, ' ');
        $title = ucwords($title);
        return $title;
    }

    public function sitemap() {
        $slugs = File::all()->pluck('slug');
        return response()->json($slugs);
    }    
}
