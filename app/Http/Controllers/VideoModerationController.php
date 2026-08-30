<?php

namespace App\Http\Controllers;

use App\Jobs\ImportVideoJob;
use App\Models\AppUser;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Modération du feed vidéo (back-office) : validation des vidéos des
 * utilisateurs non certifiés + gestion de la certification des comptes.
 */
class VideoModerationController extends Controller
{
    public function renderVideos(Request $request)
    {
        $status = $request->query('status', Video::STATUS_PENDING_REVIEW);
        $allowed = [
            Video::STATUS_PENDING_REVIEW,
            Video::STATUS_PUBLISHED,
            Video::STATUS_REJECTED,
            Video::STATUS_FAILED,
            Video::STATUS_PROCESSING,
        ];
        if (!in_array($status, $allowed, true)) {
            $status = Video::STATUS_PENDING_REVIEW;
        }

        $videos = Video::with('author')
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        $counts = Video::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pages.videos', [
            'videos' => $videos,
            'status' => $status,
            'counts' => $counts,
            'appUsers' => AppUser::orderBy('username')->get(),
        ]);
    }

    public function approve(Video $video)
    {
        if ($video->status !== Video::STATUS_PENDING_REVIEW) {
            return redirect()->back()->with('error', 'Cette vidéo n\'est pas en attente de validation.');
        }

        $video->update([
            'status' => Video::STATUS_PUBLISHED,
            'published_at' => now(),
            'rejected_reason' => null,
        ]);
        $video->author?->increment('videos_count');

        return redirect()->back()->with('success', 'Vidéo approuvée et publiée.');
    }

    public function reject(Request $request, Video $video)
    {
        $request->validate(['reason' => ['required', 'string', 'max:255']]);

        if ($video->status !== Video::STATUS_PENDING_REVIEW) {
            return redirect()->back()->with('error', 'Cette vidéo n\'est pas en attente de validation.');
        }

        $video->update([
            'status' => Video::STATUS_REJECTED,
            'rejected_reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Vidéo rejetée.');
    }

    public function destroy(Video $video)
    {
        // Une vidéo publiée qui disparaît doit décrémenter le compteur.
        if ($video->status === Video::STATUS_PUBLISHED) {
            $author = $video->author;
            if ($author && $author->videos_count > 0) {
                $author->decrement('videos_count');
            }
        }

        \App\Support\MediaStorage::deleteDirectory('videos/' . $video->id);
        Storage::disk('local')->deleteDirectory('videos-src/' . $video->id);
        $video->delete();

        return redirect()->back()->with('success', 'Vidéo supprimée.');
    }

    public function renderAppUsers()
    {
        $appUsers = AppUser::orderByDesc('created_at')->get();

        return view('pages.app_users', ['appUsers' => $appUsers]);
    }

    public function toggleCertified(AppUser $appUser)
    {
        $appUser->update(['is_certified' => !$appUser->is_certified]);

        return redirect()->back()->with(
            'success',
            $appUser->is_certified
                ? "@{$appUser->username} est maintenant certifié."
                : "Certification retirée à @{$appUser->username}.",
        );
    }

    public function createAppUser(Request $request)
    {
        $request->merge(['username' => strtolower(trim((string) $request->username))]);
        $validated = $request->validate([
            'username' => ['required', 'string', 'regex:/^[a-z0-9_.]{3,30}$/', 'unique:app_users,username'],
            'display_name' => ['required', 'string', 'min:2', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'username.regex' => 'Pseudo invalide : 3 à 30 caractères, lettres minuscules, chiffres, « . » ou « _ ».',
            'username.unique' => 'Ce pseudo est déjà pris.',
        ]);

        $appUser = AppUser::create($validated);
        if ($request->boolean('is_certified')) {
            $appUser->update(['is_certified' => true]);
        }

        return redirect()->back()->with('success', "Compte @{$appUser->username} créé.");
    }

    public function resetAppUserPassword(Request $request, AppUser $appUser)
    {
        $request->validate(['password' => ['required', 'string', 'min:6']]);

        $appUser->update(['password' => $request->password]);
        // Les sessions mobiles existantes sont révoquées.
        $appUser->tokens()->delete();

        return redirect()->back()->with('success', "Mot de passe de @{$appUser->username} mis à jour.");
    }

    /**
     * Import par liens (YouTube Shorts, Instagram Reels...) : une vidéo par
     * ligne, téléchargée par yt-dlp puis transcodée et publiée directement
     * sur le compte choisi.
     */
    public function importVideos(Request $request)
    {
        $request->validate([
            'app_user_id' => ['required', 'exists:app_users,id'],
            'links' => ['required', 'string'],
        ]);

        $links = collect(preg_split('/\r?\n/', $request->links))
            ->map(fn ($link) => trim($link))
            ->filter(fn ($link) => str_starts_with($link, 'http'))
            ->unique()
            ->take(20);

        if ($links->isEmpty()) {
            return redirect()->back()->with('error', 'Aucun lien valide (un lien http par ligne).');
        }

        foreach ($links as $link) {
            $video = Video::create([
                'app_user_id' => (int) $request->app_user_id,
                // Remplacée par le titre de la vidéo une fois téléchargée.
                'description' => mb_substr($link, 0, 2000),
                'status' => Video::STATUS_PROCESSING,
            ]);
            ImportVideoJob::dispatch($video->id, $link);
        }

        return redirect('/videos?status=processing')->with(
            'success',
            $links->count() . ' import(s) lancé(s) — le téléchargement et le transcodage tournent en arrière-plan.',
        );
    }
}
