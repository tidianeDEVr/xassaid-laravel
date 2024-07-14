<?php

namespace App\Http\Controllers;

use App\Http\Requests\AudioCategoriesRequest;
use App\Models\AudioCategory;

class AudioController extends Controller
{

    public function renderCategories()
    {
        $categories = AudioCategory::all();
        return view('categories.audios', ['categories' => $categories]);
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

    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}
