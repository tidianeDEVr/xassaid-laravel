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

    public function searchAudiosAndFile(String $term)
    {
        $audios = Audio::where('title', 'LIKE', "%{$term}%")
        ->orWhereHas('category', function($query) use ($term) {
            $query->where('title', 'LIKE', "%{$term}%");
        })->get();
        $files = File::where('title', 'LIKE', "%{$term}%")->get();
        $articles = Article::where('title', 'LIKE', "%{$term}%")->get();
        $categories = AudioCategory::where('title', 'LIKE', "%{$term}%")->get();

        return response([
            "audios" => $audios->values(),
            "files" => $files->values(),
            "articles" => $articles->values(),
        ]);
    }
}
