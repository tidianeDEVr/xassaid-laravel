<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\AudioCategory;
use App\Models\User;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function dashboard()
    {
        $overview = [
            'audiosCounts' => Audio::all()->count(),
            'usersCounts' => User::all()->count(),
            'categoriesCounts' => AudioCategory::all()->count()
        ];
        return view('pages/overview', ['overview' => $overview]);
    }
}
