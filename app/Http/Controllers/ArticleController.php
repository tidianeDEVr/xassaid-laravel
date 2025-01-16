<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Requests\ArticleRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        $article = new Article($validated);
        $article->slug = Str::slug($article->title);
        $article->save();
        $image = $request->file('image');

        if ($request->hasFile('image') && $image->isValid()) {
            $name = time();

            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($image->getPathname(), 'r'), $image->getClientOriginalName())
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $article->image = $name . '.' . $image->getClientOriginalExtension();
                $article->save();
            } else {
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement de l\'image !']);
            }
        } else {
            return redirect()->back()->withErrors(['error' => 'Le fichier image est invalide.']);
        }

        return redirect()->back()->with('success', 'L\'article a été créer avec succès !');
    }

    public function paginateArticle($page)
    {
        $perPage = 10;

        $articles = Article::skip(($page - 1) * $perPage)->take($perPage)
            ->orderBy('title', 'asc')->get();

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
