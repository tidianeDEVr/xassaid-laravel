<?php

namespace App\Http\Controllers;

use App\Jobs\ImportVideoJob;
use App\Models\AppUser;
use App\Models\Video;
use App\Models\VideoComment;
use App\Models\VideoLike;
use App\Support\AvatarFile;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        if (! in_array($status, $allowed, true)) {
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

        \App\Support\MediaStorage::deleteDirectory('videos/'.$video->id);
        Storage::disk('local')->deleteDirectory('videos-src/'.$video->id);
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
        // forceFill : is_certified est volontairement hors des $fillable
        // (personne ne doit pouvoir se certifier via l'API mobile).
        $appUser->forceFill(['is_certified' => ! $appUser->is_certified])->save();

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
            // Même traitement que depuis l'app : carré 600x600, JPEG.
            'avatar' => array_merge(['nullable'], AvatarFile::RULES),
        ], [
            'username.regex' => 'Pseudo invalide : 3 à 30 caractères, lettres minuscules, chiffres, « . » ou « _ ».',
            'username.unique' => 'Ce pseudo est déjà pris.',
            'avatar.image' => 'L\'avatar doit être une image (jpg, png ou webp).',
            'avatar.max' => 'L\'avatar ne doit pas dépasser 5 Mo.',
        ]);

        unset($validated['avatar']);
        $appUser = AppUser::create($validated);

        if ($request->hasFile('avatar')) {
            $appUser->update(['avatar_path' => AvatarFile::store($request->file('avatar'))]);
        }
        if ($request->boolean('is_certified')) {
            $appUser->forceFill(['is_certified' => true])->save();
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
     * POST /app-users/{appUser}/avatar — remplace l'avatar d'un compte.
     * Même traitement que depuis l'application (carré 600x600, JPEG).
     */
    public function updateAppUserAvatar(Request $request, AppUser $appUser)
    {
        $request->validate([
            'avatar' => array_merge(['required'], AvatarFile::RULES),
        ], [
            'avatar.image' => "L'avatar doit être une image (jpg, png ou webp).",
            'avatar.max' => "L'avatar ne doit pas dépasser 5 Mo.",
        ]);

        $appUser->update([
            'avatar_path' => AvatarFile::replace($request->file('avatar'), $appUser->avatar_path),
        ]);

        return redirect()->back()->with('success', "Avatar de @{$appUser->username} mis à jour.");
    }

    /**
     * DELETE /app-users/{appUser} — supprime un compte et tout ce qu'il a
     * produit.
     *
     * Les clés étrangères effacent en cascade les vidéos, likes, commentaires
     * et abonnements du compte, mais deux choses leur échappent : les
     * compteurs dénormalisés portés par les AUTRES lignes (une vidéo qu'il
     * avait likée, un compte qu'il suivait) et les fichiers du disque média.
     * On recalcule donc les compteurs touchés à partir des lignes réellement
     * restantes — plus sûr qu'une soustraction, qui dériverait au moindre
     * enchaînement de cascades.
     */
    public function destroyAppUser(AppUser $appUser)
    {
        $username = $appUser->username;

        // Relevé AVANT suppression : après, les lignes n'existent plus.
        $ownedVideos = Video::where('app_user_id', $appUser->id)->pluck('id');
        $touchedVideos = VideoLike::where('app_user_id', $appUser->id)->pluck('video_id')
            ->merge(VideoComment::where('app_user_id', $appUser->id)->pluck('video_id'))
            ->unique()
            ->diff($ownedVideos);
        $touchedComments = DB::table('video_comment_likes')
            ->where('app_user_id', $appUser->id)
            ->pluck('video_comment_id');
        $touchedUsers = DB::table('follows')->where('follower_id', $appUser->id)->pluck('followed_id')
            ->merge(DB::table('follows')->where('followed_id', $appUser->id)->pluck('follower_id'))
            ->unique();
        $avatarPath = $appUser->avatar_path;

        DB::transaction(function () use ($appUser, $touchedVideos, $touchedComments, $touchedUsers) {
            // Polymorphe, donc sans contrainte de clé étrangère : à effacer
            // explicitement, sinon les jetons survivent au compte.
            $appUser->tokens()->delete();
            $appUser->delete();

            // forceFill : les compteurs ne sont pas dans les $fillable des
            // modèles (ailleurs ils passent par increment/decrement, qui
            // ignorent la protection). Un update() classique ne les écrirait
            // pas, sans lever d'erreur.
            Video::whereIn('id', $touchedVideos)->get()->each(fn (Video $video) => $video->forceFill([
                'likes_count' => VideoLike::where('video_id', $video->id)->count(),
                'comments_count' => VideoComment::where('video_id', $video->id)->count(),
            ])->save());

            VideoComment::whereIn('id', $touchedComments)->get()->each(
                fn (VideoComment $comment) => $comment->forceFill([
                    'likes_count' => DB::table('video_comment_likes')
                        ->where('video_comment_id', $comment->id)
                        ->count(),
                ])->save(),
            );

            AppUser::whereIn('id', $touchedUsers)->get()->each(fn (AppUser $user) => $user->forceFill([
                'followers_count' => DB::table('follows')->where('followed_id', $user->id)->count(),
                'following_count' => DB::table('follows')->where('follower_id', $user->id)->count(),
            ])->save());
        });

        // Hors transaction : les fichiers ne se rejouent pas en arrière, on ne
        // les touche qu'une fois la base réellement à jour.
        foreach ($ownedVideos as $videoId) {
            MediaStorage::deleteDirectory('videos/'.$videoId);
            Storage::disk('local')->deleteDirectory('videos-src/'.$videoId);
        }
        if ($avatarPath) {
            MediaStorage::delete($avatarPath);
        }

        return redirect('/app-users')->with(
            'success',
            "Compte @{$username} supprimé, avec ses {$ownedVideos->count()} vidéo(s).",
        );
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

        foreach ($links->values() as $i => $link) {
            $video = Video::create([
                'app_user_id' => (int) $request->app_user_id,
                // Remplacée par le titre de la vidéo une fois téléchargée.
                'description' => mb_substr($link, 0, 2000),
                'status' => Video::STATUS_PROCESSING,
                // Conservé pour pouvoir relancer un import en échec.
                'source_url' => mb_substr($link, 0, 500),
            ]);
            // Étalés de 2 minutes : des téléchargements enchaînés depuis la
            // même IP re-déclenchent la vérification anti-bot de YouTube,
            // cookies ou pas.
            ImportVideoJob::dispatch($video->id, $link)
                ->delay(now()->addSeconds($i * 120));
        }

        return redirect('/videos?status=processing')->with(
            'success',
            $links->count().' import(s) lancé(s), espacés de 2 minutes pour ne pas déclencher l\'anti-bot de YouTube — le téléchargement et le transcodage tournent en arrière-plan.',
        );
    }

    /**
     * POST /videos/{video}/retry — relance un import en échec.
     *
     * Ne concerne que les vidéos importées par lien (source_url connu) : pour
     * un upload depuis l'application, l'original est supprimé à l'échec et il
     * n'y a rien à rejouer.
     */
    public function retryImport(Video $video)
    {
        if ($video->status !== Video::STATUS_FAILED) {
            return redirect()->back()->with('error', 'Cette vidéo n\'est pas en échec.');
        }
        if (! $video->source_url) {
            return redirect()->back()->with('error', 'Pas de lien d\'origine pour cette vidéo : impossible de relancer.');
        }

        $video->update([
            'status' => Video::STATUS_PROCESSING,
            'rejected_reason' => null,
        ]);
        ImportVideoJob::dispatch($video->id, $video->source_url);

        return redirect('/videos?status=processing')->with(
            'success',
            'Import relancé — suivez l\'avancement dans l\'onglet « En traitement ».',
        );
    }
}
