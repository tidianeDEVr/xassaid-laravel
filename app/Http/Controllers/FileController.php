<?php

namespace App\Http\Controllers;

use App\Models\File;

class FileController extends Controller
{
    public function renderFiles()
    {
        $files = File::all();

        return view('pages.library', ['files' => $files]);
    }

    public function paginateFiles($page)
    {
        $perPage = 64;

        $files = File::skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json($files);
    }

    public function getFileBySlug($slug)
    {
        $file = File::where('slug', $slug)->first();

        if (!$file) {
            return response('File not found!', 404);
        }

        return response()->json($file);
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
}
