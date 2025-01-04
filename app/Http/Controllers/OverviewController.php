<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\AudioCategory;
use App\Models\File;
use App\Models\User;

class OverviewController extends Controller
{
    public function dashboard()
    {
        $overview = [
            'audiosCounts' => Audio::all()->count(),
            'usersCounts' => User::all()->count(),
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
        $sammFallYii = AudioCategory::where('type', 'samm-fall')->orderBy('isFeatured', 'desc')->take(12)->get();

        return response([
            "makkGni" => $makkGni,
            "kourelsYii" => $kourelsYii,
            "rajassKatYii" => $rajassKatYii,
            "sammFallYii" => $sammFallYii
        ]);
    }

    public function searchAudiosAndFile(String $term)
    {
        $audios = Audio::where('title', 'LIKE', "%{$term}%")->get();
        $files = File::where('title', 'LIKE', "%{$term}%")->get();
        $categories = AudioCategory::where('title', 'LIKE', "%{$term}%")->get();
        foreach ($categories as $category) {
            $audios = $audios->merge($category->audios);
        }

        return response([
            "audios" => $audios,
            "files" => $files
        ]);
    }
}
