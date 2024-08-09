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
        $categories = AudioCategory::orderBy('isFeatured', 'desc')->take(12)->get();
        return response()->json($categories);
    }
}
