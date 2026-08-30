<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUploadRequest;
use App\Jobs\TranscodeVideoJob;
use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /** Vidéos simultanément en cours de traitement / d'examen par compte. */
    private const MAX_IN_FLIGHT = 3;

    public function store(VideoUploadRequest $request)
    {
        // Le transcodage est la ressource chère : un compte ne peut pas
        // remplir la file d'attente, même en restant sous le quota horaire.
        $inFlight = Video::where('app_user_id', $request->user()->id)
            ->whereIn('status', [Video::STATUS_PROCESSING, Video::STATUS_PENDING_REVIEW])
            ->count();
        if ($inFlight >= self::MAX_IN_FLIGHT) {
            return response()->json([
                'message' => 'Vous avez déjà plusieurs vidéos en cours de traitement. Attendez leur publication.',
            ], 429);
        }

        $video = Video::create([
            'app_user_id' => $request->user()->id,
            'description' => $request->description,
            'khassida_title' => $request->khassida_title,
            'status' => Video::STATUS_PROCESSING,
        ]);

        // Extension déduite du contenu réel, jamais du nom envoyé par le
        // client (qui finirait dans un chemin du disque).
        $extension = $request->file('video')->guessExtension() ?: 'mp4';
        $path = $request->file('video')->storeAs(
            'videos-src/' . $video->id,
            'original.' . $extension,
            'local',
        );
        $video->update(['original_path' => $path]);

        TranscodeVideoJob::dispatch($video->id);

        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
        ], 201);
    }

    public function status(Request $request, int $id)
    {
        $video = Video::where('app_user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
            'rejected_reason' => $video->rejected_reason,
        ]);
    }
}
