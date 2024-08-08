<?php

namespace App\Http\Controllers;

use App\Http\Requests\AudioCategoriesRequest;
use App\Http\Requests\AudioRequest;
use App\Models\Audio;
use App\Models\AudioCategory;

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

        return view('pages.audios', ['categories' => $categories, 'audios' => $audios]);
    }



    public function createCategory(AudioCategoriesRequest $request)
    {
        $data = $request->validated();
        $category = new AudioCategory($data);
        $category->slug = $this->generateSlug($category->title);
        if ($request->hasFile('coverImage')) {
            $destination = 'public/images';
            $image = $request->file('coverImage');
            $name = time() . '.' . $image->getClientOriginalExtension();
            $image->storePubliclyAs($destination, $name);
            $category->coverImagePath = $name;
        }
        $category->save();
        return redirect('/categories/audios');
    }

    public function createAudio(AudioRequest $request)
    {
        $data = $request->validated();
        $audio = new Audio($data);
        $audio->slug = $this->generateSlug($audio->title);
        if ($request->hasFile('audio')) {
            $destination = 'public/audios';
            $audioFile = $request->file('audio');
            $name = time() . '.' . $audioFile->getClientOriginalExtension();
            $audioFile->storePubliclyAs($destination, $name);
            $audio->pathToFile = $name;
        }
        $category = AudioCategory::where('slug', $data['category'])->first();
        if ($category) $audio->category_id = $category->id;
        $audio->save();

        return redirect('/audios');
    }

    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public function frontAudiopage()
    {
        $categories = AudioCategory::orderBy('isFeatured', 'desc')->get();
        $audios = Audio::all();
        // return response()->json($categories, $audios);
        return response([
            'categories' => $categories,
            'audios' => $audios
        ]);
    }
}
