<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\AudioCategory;
use App\Models\File;
use App\Models\Article;

class OverviewController extends Controller
{
    public function dashboard()
    {
        $overview = [
            'audiosCounts' => Audio::all()->count(),
            'articlesCounts' => Article::all()->count(),
            'filesCounts' => File::all()->count(),
            'categoriesCounts' => AudioCategory::all()->count()
        ];
        return view('pages/overview', ['overview' => $overview]);
    }

    public function frontHomepage()
    {
        $makkGni = AudioCategory::where('type', 'makk-gni')->orderBy('isFeatured', 'desc')->take(12)->get();
        $kourelsYii = AudioCategory::where('type', 'kourels-yii')->orderBy('isFeatured', 'desc')->take(12)->get();
        $rajassKatYii = AudioCategory::where('type', 'rajass-kat-yii')->orderBy('isFeatured', 'desc')->take(12)->get();
        $autres = AudioCategory::where('type', 'autres')->orderBy('isFeatured', 'desc')->take(12)->get();

        return response([
            "makkGni" => $makkGni,
            "kourelsYii" => $kourelsYii,
            "rajassKatYii" => $rajassKatYii,
            "autres" => $autres
        ]);
    }

    /**
     * Homepage de l'app mobile V2 : chaque catégorie porte son nombre d'audios.
     */
    public function frontHomepageV2()
    {
        $byType = function (string $type) {
            return AudioCategory::where('type', $type)
                ->withCount('audios')
                ->orderBy('isFeatured', 'desc')
                ->take(12)
                ->get();
        };

        return response([
            "makkGni" => $byType('makk-gni'),
            "kourelsYii" => $byType('kourels-yii'),
            "rajassKatYii" => $byType('rajass-kat-yii'),
            "autres" => $byType('autres')
        ]);
    }

    /**
     * Recherche de l'app mobile V2 : ajoute les catégories (récitants, kourels)
     * aux résultats, avec leur nombre d'audios.
     */
    public function searchV2(String $term)
    {
        $payload = $this->searchAudiosAndFile($term)->original;

        $payload['categories'] = AudioCategory::where('title', 'LIKE', "%{$term}%")
            ->withCount('audios')
            ->orderBy('isFeatured', 'desc')
            ->get();

        return response($payload);
    }

    public function searchAudiosAndFile(String $term)
    {
        $normalizedTerm = $this->normalizeForSearch($term);
        $termWords = explode(' ', $normalizedTerm);

        // Recherche LIKE existante + recherche étendue
        $audios = Audio::where('title', 'LIKE', "%{$term}%")
            ->orWhereHas('category', function($query) use ($term) {
                $query->where('title', 'LIKE', "%{$term}%");
            })->get();

        $files = File::where('title', 'LIKE', "%{$term}%")->get();
        $articles = Article::where('title', 'LIKE', "%{$term}%")->get();
        $categories = AudioCategory::where('title', 'LIKE', "%{$term}%")->get();

        // Récupérer tous les éléments pour la recherche fuzzy si peu de résultats
        if ($audios->count() < 5) {
            $allAudios = Audio::all();
            $fuzzyAudios = $this->fuzzyFilter($allAudios, 'title', $normalizedTerm, $termWords);
            $audios = $audios->merge($fuzzyAudios)->unique('id');
        }

        if ($files->count() < 5) {
            $allFiles = File::all();
            $fuzzyFiles = $this->fuzzyFilter($allFiles, 'title', $normalizedTerm, $termWords);
            $files = $files->merge($fuzzyFiles)->unique('id');
        }

        if ($articles->count() < 5) {
            $allArticles = Article::all();
            $fuzzyArticles = $this->fuzzyFilter($allArticles, 'title', $normalizedTerm, $termWords);
            $articles = $articles->merge($fuzzyArticles)->unique('id');
        }

        // Trier par score de pertinence
        $audios = $this->sortByRelevance($audios, $normalizedTerm, $termWords);
        $files = $this->sortByRelevance($files, $normalizedTerm, $termWords);
        $articles = $this->sortByRelevance($articles, $normalizedTerm, $termWords);

        return response([
            "audios" => $audios->values(),
            "files" => $files->values(),
            "articles" => $articles->values(),
        ]);
    }

    private function normalizeForSearch(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[éèêë]/u', 'e', $text);
        $text = preg_replace('/[àâä]/u', 'a', $text);
        $text = preg_replace('/[ùûü]/u', 'u', $text);
        $text = preg_replace('/[îï]/u', 'i', $text);
        $text = preg_replace('/[ôö]/u', 'o', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[^a-z0-9\s]/u', '', $text);
        return trim($text);
    }

    private function fuzzyFilter($collection, string $field, string $normalizedTerm, array $termWords)
    {
        $minScore = 0.4;

        return $collection->filter(function ($item) use ($field, $normalizedTerm, $termWords, $minScore) {
            $title = $this->normalizeForSearch($item->$field ?? '');
            $score = $this->calculateFuzzyScore($title, $normalizedTerm, $termWords);
            return $score >= $minScore;
        });
    }

    private function calculateFuzzyScore(string $title, string $normalizedTerm, array $termWords): float
    {
        $score = 0;

        // Score de similarité globale (Levenshtein normalisé)
        $maxLen = max(strlen($title), strlen($normalizedTerm));
        if ($maxLen > 0) {
            $levenshtein = levenshtein($title, $normalizedTerm);
            $score += (1 - ($levenshtein / $maxLen)) * 0.3;
        }

        // Score de correspondance par mots
        $titleWords = explode(' ', $title);
        $matchedWords = 0;
        foreach ($termWords as $termWord) {
            if (strlen($termWord) < 2) continue;

            foreach ($titleWords as $titleWord) {
                if (strlen($titleWord) < 2) continue;

                // Correspondance exacte du mot
                if ($titleWord === $termWord) {
                    $matchedWords += 1;
                    break;
                }

                // Le mot commence par le terme
                if (str_starts_with($titleWord, $termWord)) {
                    $matchedWords += 0.8;
                    break;
                }

                // Contient le terme
                if (str_contains($titleWord, $termWord)) {
                    $matchedWords += 0.6;
                    break;
                }

                // Similarité par distance de Levenshtein pour les mots courts
                $wordMaxLen = max(strlen($titleWord), strlen($termWord));
                if ($wordMaxLen > 0 && $wordMaxLen <= 15) {
                    $wordDist = levenshtein($titleWord, $termWord);
                    $allowedDist = $wordMaxLen <= 4 ? 1 : ($wordMaxLen <= 7 ? 2 : 3);
                    if ($wordDist <= $allowedDist) {
                        $matchedWords += (1 - ($wordDist / $wordMaxLen)) * 0.7;
                        break;
                    }
                }
            }
        }

        if (count($termWords) > 0) {
            $score += ($matchedWords / count($termWords)) * 0.7;
        }

        return $score;
    }

    private function sortByRelevance($collection, string $normalizedTerm, array $termWords)
    {
        return $collection->sortByDesc(function ($item) use ($normalizedTerm, $termWords) {
            $title = $this->normalizeForSearch($item->title ?? '');

            // Bonus pour correspondance exacte
            if (str_contains($title, $normalizedTerm)) {
                return 1.0 + $this->calculateFuzzyScore($title, $normalizedTerm, $termWords);
            }

            return $this->calculateFuzzyScore($title, $normalizedTerm, $termWords);
        });
    }
}
