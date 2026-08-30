<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoCommentResource;
use App\Http\Resources\VideoResource;
use App\Models\AppUser;
use App\Models\Video;
use App\Models\VideoComment;
use App\Models\VideoLike;
use App\Support\ApiCursor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoFeedController extends Controller
{
    private const PAGE_SIZE_MAX = 20;

    /**
     * GET /v2/feed?kind=forYou|following&cursor=&limit=
     * Public ; le token (optionnel) enrichit is_liked / is_followed.
     */
    public function feed(Request $request)
    {
        $user = auth('sanctum')->user();
        $kind = $request->query('kind', 'forYou');
        $limit = min(max((int) $request->query('limit', 5), 1), self::PAGE_SIZE_MAX);

        $query = Video::with('author')
            ->where('status', Video::STATUS_PUBLISHED)
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($kind === 'following') {
            if (!$user) {
                return response()->json(['message' => 'Connexion requise pour le fil Abonnements.'], 401);
            }
            $query->whereIn('app_user_id', $user->following()->pluck('app_users.id'));
        }

        // Le curseur vient du client : on ne lui fait pas confiance sur le
        // type des valeurs (un tableau ferait exploser la requête).
        $cursor = ApiCursor::decode($request->query('cursor'));
        if ($cursor !== null && is_scalar($cursor['p'] ?? null) && is_scalar($cursor['i'] ?? null)) {
            $publishedAt = (string) $cursor['p'];
            $lastId = (int) $cursor['i'];
            $query->where(function ($q) use ($publishedAt, $lastId) {
                $q->where('published_at', '<', $publishedAt)
                    ->orWhere(function ($q2) use ($publishedAt, $lastId) {
                        $q2->where('published_at', $publishedAt)->where('id', '<', $lastId);
                    });
            });
        }

        $videos = $query->limit($limit + 1)->get();
        $hasMore = $videos->count() > $limit;
        $videos = $videos->take($limit);

        $this->attachViewerState($videos, $user);

        $last = $videos->last();

        return response()->json([
            'data' => VideoResource::collection($videos),
            'next_cursor' => $hasMore && $last
                ? ApiCursor::encode(['p' => $last->published_at->toDateTimeString(), 'i' => $last->id])
                : null,
        ]);
    }

    public function like(Request $request, int $id)
    {
        $video = Video::where('status', Video::STATUS_PUBLISHED)->findOrFail($id);

        $like = VideoLike::firstOrCreate([
            'video_id' => $video->id,
            'app_user_id' => $request->user()->id,
        ]);
        if ($like->wasRecentlyCreated) {
            $video->increment('likes_count');
        }

        return response()->json(['likes' => (int) $video->fresh()->likes_count, 'is_liked' => true]);
    }

    public function unlike(Request $request, int $id)
    {
        $video = Video::where('status', Video::STATUS_PUBLISHED)->findOrFail($id);

        $deleted = VideoLike::where('video_id', $video->id)
            ->where('app_user_id', $request->user()->id)
            ->delete();
        if ($deleted > 0 && $video->likes_count > 0) {
            $video->decrement('likes_count');
        }

        return response()->json(['likes' => (int) $video->fresh()->likes_count, 'is_liked' => false]);
    }

    /**
     * GET /v2/videos/{id}/comments?cursor=&limit=
     * Racines (récentes d'abord) avec leurs réponses (chronologiques).
     */
    public function comments(Request $request, int $id)
    {
        $user = auth('sanctum')->user();
        $video = Video::where('status', Video::STATUS_PUBLISHED)->findOrFail($id);
        $limit = min(max((int) $request->query('limit', 20), 1), 50);

        $query = VideoComment::with(['author', 'replies.author'])
            ->where('video_id', $video->id)
            ->whereNull('parent_id')
            ->orderByDesc('id');

        if ($cursor = ApiCursor::decode($request->query('cursor'))) {
            $query->where('id', '<', (int) ($cursor['i'] ?? 0));
        }

        $roots = $query->limit($limit + 1)->get();
        $hasMore = $roots->count() > $limit;
        $roots = $roots->take($limit);

        if ($user) {
            $ids = $roots->pluck('id')
                ->merge($roots->flatMap(fn ($c) => $c->replies->pluck('id')));
            $liked = DB::table('video_comment_likes')
                ->where('app_user_id', $user->id)
                ->whereIn('video_comment_id', $ids)
                ->pluck('video_comment_id')
                ->flip();
            $mark = function ($comment) use ($liked) {
                $comment->is_liked = $liked->has($comment->id);
            };
            $roots->each(function ($root) use ($mark) {
                $mark($root);
                $root->replies->each($mark);
            });
        }

        return response()->json([
            'data' => VideoCommentResource::collection($roots),
            'next_cursor' => $hasMore && $roots->last()
                ? ApiCursor::encode(['i' => $roots->last()->id])
                : null,
            'total' => (int) $video->comments_count,
        ]);
    }

    public function storeComment(Request $request, int $id)
    {
        $video = Video::where('status', Video::STATUS_PUBLISHED)->findOrFail($id);

        $validated = $request->validate([
            // Un commentaire de 500 espaces n'en est pas un.
            'body' => ['required', 'string', 'max:500', 'regex:/\S/'],
            'parent_id' => ['nullable', 'integer'],
        ], [
            'body.regex' => 'Le commentaire ne peut pas être vide.',
        ]);
        $validated['body'] = trim($validated['body']);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId !== null) {
            $parent = VideoComment::where('video_id', $video->id)->find($parentId);
            if (!$parent) {
                return response()->json(['message' => 'Commentaire parent introuvable.'], 422);
            }
            // Un seul niveau : répondre à une réponse rattache au fil racine.
            $parentId = $parent->parent_id ?? $parent->id;
        }

        $comment = VideoComment::create([
            'video_id' => $video->id,
            'app_user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $validated['body'],
        ]);
        $video->increment('comments_count');

        $comment->load('author');
        $comment->setRelation('replies', collect());

        return response()->json([
            'comment' => new VideoCommentResource($comment),
            'total' => (int) $video->fresh()->comments_count,
        ], 201);
    }

    public function likeComment(Request $request, int $id)
    {
        return $this->setCommentLiked($request, $id, true);
    }

    public function unlikeComment(Request $request, int $id)
    {
        return $this->setCommentLiked($request, $id, false);
    }

    public function follow(Request $request, int $id)
    {
        return $this->setFollowing($request, $id, true);
    }

    public function unfollow(Request $request, int $id)
    {
        return $this->setFollowing($request, $id, false);
    }

    /**
     * POST /v2/videos/{id}/view — public, comptage best-effort.
     */
    public function view(int $id)
    {
        Video::where('status', Video::STATUS_PUBLISHED)
            ->where('id', $id)
            ->increment('views_count');

        return response()->json(['ok' => true]);
    }

    /**
     * Pose is_liked / author_is_followed sur chaque vidéo d'une page,
     * en deux requêtes groupées (pas de N+1).
     */
    private function attachViewerState($videos, ?AppUser $user): void
    {
        if (!$user || $videos->isEmpty()) {
            return;
        }

        $likedIds = VideoLike::where('app_user_id', $user->id)
            ->whereIn('video_id', $videos->pluck('id'))
            ->pluck('video_id')
            ->flip();

        $followedIds = DB::table('follows')
            ->where('follower_id', $user->id)
            ->whereIn('followed_id', $videos->pluck('app_user_id')->unique())
            ->pluck('followed_id')
            ->flip();

        foreach ($videos as $video) {
            $video->is_liked = $likedIds->has($video->id);
            $video->author_is_followed = $followedIds->has($video->app_user_id);
        }
    }

    private function setCommentLiked(Request $request, int $commentId, bool $liked)
    {
        $comment = VideoComment::findOrFail($commentId);
        $userId = $request->user()->id;

        if ($liked) {
            $like = DB::table('video_comment_likes')
                ->where('video_comment_id', $comment->id)
                ->where('app_user_id', $userId)
                ->exists();
            if (!$like) {
                DB::table('video_comment_likes')->insert([
                    'video_comment_id' => $comment->id,
                    'app_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $comment->increment('likes_count');
            }
        } else {
            $deleted = DB::table('video_comment_likes')
                ->where('video_comment_id', $comment->id)
                ->where('app_user_id', $userId)
                ->delete();
            if ($deleted > 0 && $comment->likes_count > 0) {
                $comment->decrement('likes_count');
            }
        }

        return response()->json([
            'likes' => (int) $comment->fresh()->likes_count,
            'is_liked' => $liked,
        ]);
    }

    private function setFollowing(Request $request, int $followedId, bool $following)
    {
        $user = $request->user();
        if ($user->id === $followedId) {
            return response()->json(['message' => 'Impossible de se suivre soi-même.'], 422);
        }

        $followed = AppUser::findOrFail($followedId);

        if ($following) {
            $exists = DB::table('follows')
                ->where('follower_id', $user->id)
                ->where('followed_id', $followed->id)
                ->exists();
            if (!$exists) {
                DB::table('follows')->insert([
                    'follower_id' => $user->id,
                    'followed_id' => $followed->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $followed->increment('followers_count');
                $user->increment('following_count');
            }
        } else {
            $deleted = DB::table('follows')
                ->where('follower_id', $user->id)
                ->where('followed_id', $followed->id)
                ->delete();
            if ($deleted > 0) {
                if ($followed->followers_count > 0) {
                    $followed->decrement('followers_count');
                }
                if ($user->following_count > 0) {
                    $user->decrement('following_count');
                }
            }
        }

        return response()->json([
            'followers' => (int) $followed->fresh()->followers_count,
            'is_followed' => $following,
        ]);
    }
}
