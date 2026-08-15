<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Http\Requests\ArticleRequest;
use App\Http\Requests\ArticleUpdateRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Support\MediaOptimizer;

class ArticleController extends Controller
{
    public function renderArticles()
    {
        $articles = Article::all();
        return view('pages.articles', ['articles' => $articles]);
    }

    public function renderCreateArticles()
    {
        return view('editor.create-article');
    }

    public function processCreateArticles(ArticleRequest $request)
    {
        $validated = $request->validated();
        unset($validated['image']);
        $article = new Article($validated);
        $article->slug = Str::slug($article->title);
        $image = $request->file('image');

        if ($request->hasFile('image') && $image->isValid()) {
            $baseName = MediaOptimizer::normalizeFilename(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
            $optimized = MediaOptimizer::optimizeImage($image, 1600, 1600, 82);
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
                $article->image = $uploadFilename;
                $article->save();
            } else {
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement de l\'image !']);
            }
        } else {
            return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
        }

        return redirect()->back()->with('success', 'L\'article a été créer avec succès !');
    }

    public function renderEditArticle(Article $article)
    {
        return view('editor.edit-article', ['article' => $article]);
    }

    public function updateArticle(ArticleUpdateRequest $request, Article $article)
    {
        $validated = $request->validated();
        unset($validated['image']);
        $article->fill($validated);
        $article->slug = Str::slug($article->title);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            if (!$image->isValid()) {
                return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
            }

            $baseName = MediaOptimizer::normalizeFilename(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $baseName !== '' ? $baseName . '-' . time() : (string) time();
            $optimized = MediaOptimizer::optimizeImage($image, 1600, 1600, 82);
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
                $article->image = $uploadFilename;
            } else {
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement de l\'image !']);
            }
        }

        $article->save();

        return redirect()->back()->with('success', 'L\'article a été modifié avec succès !');
    }

    public function deleteArticle(Article $article)
    {
        $article->delete();

        return redirect()->back()->with('success', 'L\'article a été supprimé !');
    }

    public function paginateArticle($page)
    {
        if (!is_numeric($page) || (int) $page < 1) {
            return response()->json(['message' => 'Page invalide.'], 400);
        }

        $perPage = 10;
        $page = (int) $page;

        $articles = Article::orderByTitleNatural()
            ->skip(($page - 1) * $perPage)->take($perPage)
            ->get();

        return response([
            'articles' => $articles
        ]);
    }

    public function getArticleBySlug($slug)
    {
        $article = Article::where('slug', $slug)->first();

        if (!$article) {
            return response('Article not found!', 404);
        }

        return response()->json($article);
    }

    public function sitemap() {
        $slugs = Article::all()->pluck('slug');
        return response()->json($slugs);
    }    
}
