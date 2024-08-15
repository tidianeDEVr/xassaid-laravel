<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
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

        // Create Static
        // [
        //     "title" => 'Foulkou 01',
        //     "slug" => 'foulkou-01',
        //     "pathToFile" => "Foulkou_01.mp3",
        //     "category_id" => 31
        // ],
        $toCreates = [

            [
                "title" => "Rahiya",
                "slug" => "Rahiya",
                "pathToFile" => "15-Raiya.mp3",
                "category_id" => 30
            ],
            [
                "title" => "S A Gassama 01",
                "slug" => "s_a_gassama_01",
                "pathToFile" => "1204.mp3",
                "category_id" => 30
            ],
            [
                "title" => "S A Gassama 02",
                "slug" => "s_a_gassama_02",
                "pathToFile" => "1208.mp3",
                "category_id" => 30
            ],
            [
                "title" => "S A Gassama 03",
                "slug" => "s_a_gassama_03",
                "pathToFile" => "1209.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Assirou",
                "slug" => "assirou",
                "pathToFile" => "Assirou New.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Ayassamine",
                "slug" => "ayassamine",
                "pathToFile" => "AYASSAMINE (ABDOUL KHADRE GASSAMA).mp3",
                "category_id" => 30
            ],
            [
                "title" => "Mafatihul Jinan",
                "slug" => "mafatihul_jinan",
                "pathToFile" => "Gassama Mafatihul Jinan.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Mawahibu 2",
                "slug" => "mawahibu_2",
                "pathToFile" => "Gassama Mawahibu 2.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Midadi",
                "slug" => "Midadi",
                "pathToFile" => "Gassama Midadi.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Muxadimat Kurel",
                "slug" => "muxadimat_kurel",
                "pathToFile" => "Gassama Muxadimat kurel.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Mawaahibou",
                "slug" => "mawaahibou",
                "pathToFile" => "mawaahibou.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Moustapha Mbaye et Gasama",
                "slug" => "moustapha_mbaye_et_gasama",
                "pathToFile" => "Moustapha-Mbaye-et-Gasama.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Raditu",
                "slug" => "raditu",
                "pathToFile" => "Raditu.mp3",
                "category_id" => 30
            ],
            [
                "title" => "S A Gassama 04",
                "slug" => "s_a_gassama_04",
                "pathToFile" => "S Gassama.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Assirou 2",
                "slug" => "assirou_2",
                "pathToFile" => "S. A KH GASSAMA  Assirou new .mp3",
                "category_id" => 30
            ],
            [
                "title" => "Assiru",
                "slug" => "assiru",
                "pathToFile" => "S. A. K. GASSAMA Assiru.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Jazbu",
                "slug" => "jazbu",
                "pathToFile" => "S. A. K. GASSAMA Jazbu.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Mawahibu",
                "slug" => "mawahibu",
                "pathToFile" => "S. A. K. GASSAMA Mawahibu.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Midadi et",
                "slug" => "et",
                "pathToFile" => "S. A. K. GASSAMA Midadi et.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Alaman Shakawtu",
                "slug" => "alaman_shakawtu",
                "pathToFile" => "S. A. Kh GASSAMA Alaman Shakawtu.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Assiru et Mafatuhul",
                "slug" => "assiru_et_mafatuhul",
                "pathToFile" => "S. A. Kh. GASSAMA Assiru et Mafatuhul.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Hamdi Wa Shukri",
                "slug" => "hamdi_wa_shukri",
                "pathToFile" => "S. A. Kh. GASSAMA F1 Hamdi wa shukri.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Mafatihul_Jinan",
                "slug" => "mafatihul_jinan",
                "pathToFile" => "S. A. Kh. GASSAMA F1 Mafatihul Jinan.mp3",
                "category_id" => 30
            ],
            [
                "title" => "F2_Karamna",
                "slug" => "f2_karamna",
                "pathToFile" => "S. A. Kh. GASSAMA F2 Karamna.mp3",
                "category_id" => 30
            ],
            [
                "title" => "F2 Midadi",
                "slug" => "f2_midadi",
                "pathToFile" => "S. A. Kh. GASSAMA F2 Midadi.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Madal Xabiiru Lisanu shukri",
                "slug" => "madal_xabiiru_lisanu_shukri",
                "pathToFile" => "S. A. Kh. GASSAMA Madal Xabiiru Lisanu shukri.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Mawaahibu",
                "slug" => "Mawaahibu",
                "pathToFile" => "S. A. Kh. GASSAMA Mawaahibi.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Muxadimat Kurel",
                "slug" => "muxadimat_kurel",
                "pathToFile" => "S. A. Kh. GASSAMA Muxadimat kurel.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Muxadimat",
                "slug" => "muxadimat",
                "pathToFile" => "S. A. Kh. GASSAMA Muxadimat.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Rumnaa",
                "slug" => "rumnaa",
                "pathToFile" => "S. A. Kh. GASSAMA Rumnaa.mp3",
                "category_id" => 30
            ],
            [
                "title" => "Touhfatou",
                "slug" => "Touhfatou",
                "pathToFile" => "TOUXFATOUGasama.mp3",
                "category_id" => 30
            ],
        ];

        // foreach ($toCreates as $audio) {
        //     $aud = new Audio($audio);
        //     $aud->save();
        // }

        return view('pages.audios', ['categories' => $categories, 'audios' => $audios]);
    }



    public function createCategory(AudioCategoriesRequest $request)
    {
        $data = $request->validated();
        $category = new AudioCategory($data);
        $category->slug = $this->generateSlug($category->title);
        if ($request->hasFile('coverImage')) {
            $image = $request->file('coverImage');
            $name = time();

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($image->getPathname(), 'r'), $image->getClientOriginalName())
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $category->coverImagePath = $name . '.' . $image->getClientOriginalExtension();
            } else {
                // Gérer les erreurs
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de la création !']);
            }
        }
        $category->save();
        return redirect()->back()->with('success', 'La catégorie a été créer avec succès !');
    }

    public function createAudio(AudioRequest $request)
    {
        $data = $request->validated();
        $audio = new Audio($data);
        $audio->slug = $this->generateSlug($audio->title);
        if ($request->hasFile('audio')) {
            $audioFile = $request->file('audio');
            $name = time();

            // UPLOAD FILE
            $endpoint = env('XASSAID_FILES_URI') . '/upload.php';
            $response = Http::timeout(1000)->attach('file', fopen($audioFile->getPathname(), 'r'), $audioFile->getClientOriginalName())
                ->post($endpoint, [
                    'key' => env('XASSAID_UPLOAD_KEY'),
                    'filename' => $name,
                ]);

            // Vérifiez la réponse
            if ($response->successful() && $response->json('status') === 'success') {
                $audio->pathToFile = $name . '.' . $audioFile->getClientOriginalExtension();
            } else {
                // Gérer les erreurs
                return redirect()->back()->withErrors(['error' => $response->json('message') ?? 'Erreur inconnue lors de l\'enregristrement !']);
            }
        }
        $category = AudioCategory::where('slug', $data['category'])->first();
        if ($category) $audio->category_id = $category->id;
        $audio->save();

        return redirect()->back()->with('success', 'Le fichier audio a été enregistrer !');
    }

    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public function paginateAudio($page)
    {
        $perPage = 64;

        $audios = Audio::skip(($page - 1) * $perPage)->take($perPage)
            ->orderBy('title', 'asc')->get();

        return response([
            'audios' => $audios
        ]);
    }

    public function frontAudioCategoriesbyType($type)
    {
        $categories = AudioCategory::where('type', $type)
            ->orderBy('title', 'asc')
            ->get();

        return response([
            'categories' => $categories
        ]);
    }

    public function frontAudiosbyCategory($category)
    {
        $category = AudioCategory::where('slug', $category)->first();

        if (!$category) {
            return response()->json([
                'message' => 'Categorie introuvable !'
            ], 404);
        }

        $audios = Audio::where('category_id', $category->id)->orderBy('title', 'asc')->get();

        return response([
            'category' => $category,
            'audios' => $audios
        ]);
    }
}
