<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppUserResource;
use App\Http\Resources\VideoResource;
use App\Models\AppUser;
use App\Models\Video;
use App\Support\ApiCursor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Profils publics + vidéos d'un utilisateur (grille du profil). */
class AppUserController extends Controller
{
    public function show(Request $request, string $username)
    {
        $viewer = auth('sanctum')->user();
        $user = AppUser::where('username', strtolower($username))->firstOrFail();

        $isFollowed = $viewer && DB::table('follows')
            ->where('follower_id', $viewer->id)
            ->where('followed_id', $user->id)
            ->exists();

        return response()->json([
            'user' => new AppUserResource($user),
            'is_followed' => (bool) $isFollowed,
            'is_me' => $viewer?->id === $user->id,
        ]);
    }

    /**
     * GET /v2/users/{username}/videos?cursor=&limit=
     * Publiées seulement ; le propriétaire voit aussi ses vidéos en
     * traitement / en attente / rejetées.
     */
    public function videos(Request $request, string $username)
    {
        $viewer = auth('sanctum')->user();
        $user = AppUser::where('username', strtolower($username))->firstOrFail();
        $isOwner = $viewer?->id === $user->id;
        $limit = min(max((int) $request->query('limit', 12), 1), 30);

        // La grille du profil est stable : cursor sur id décroissant.
        $query = Video::with('author')
            ->where('app_user_id', $user->id)
            ->orderByDesc('id');

        if (!$isOwner) {
            $query->where('status', Video::STATUS_PUBLISHED);
        }

        if ($cursor = ApiCursor::decode($request->query('cursor'))) {
            $query->where('id', '<', (int) ($cursor['i'] ?? 0));
        }

        $videos = $query->limit($limit + 1)->get();
        $hasMore = $videos->count() > $limit;
        $videos = $videos->take($limit);

        // Sans ça, une vidéo ouverte depuis la grille d'un profil s'affiche
        // toujours « non aimée », même si le spectateur l'a déjà likée.
        $this->attachViewerState($videos, $viewer);

        return response()->json([
            'data' => VideoResource::collection($videos),
            'next_cursor' => $hasMore && $videos->last()
                ? ApiCursor::encode(['i' => $videos->last()->id])
                : null,
        ]);
    }

    public function myVideos(Request $request)
    {
        return $this->videos($request, $request->user()->username);
    }

    /**
     * Pose is_liked (et l'abonnement à l'auteur) sur une page de vidéos, en
     * deux requêtes groupées.
     */
    private function attachViewerState($videos, ?AppUser $viewer): void
    {
        if (!$viewer || $videos->isEmpty()) {
            return;
        }

        $likedIds = DB::table('video_likes')
            ->where('app_user_id', $viewer->id)
            ->whereIn('video_id', $videos->pluck('id'))
            ->pluck('video_id')
            ->flip();

        $followedIds = DB::table('follows')
            ->where('follower_id', $viewer->id)
            ->whereIn('followed_id', $videos->pluck('app_user_id')->unique())
            ->pluck('followed_id')
            ->flip();

        foreach ($videos as $video) {
            $video->is_liked = $likedIds->has($video->id);
            $video->author_is_followed = $followedIds->has($video->app_user_id);
        }
    }
}
