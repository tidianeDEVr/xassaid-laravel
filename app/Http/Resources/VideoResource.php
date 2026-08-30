<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vidéo du feed, calquée sur le modèle FeedVideo de l'app mobile.
 *
 * `is_liked` / `is_followed` sont posés par le contrôleur (en une requête
 * groupée pour toute la page) sur l'instance avant sérialisation.
 *
 * @mixin \App\Models\Video
 */
class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'khassida_title' => $this->khassida_title,
            'duration_ms' => $this->duration_ms,
            'likes' => $this->likes_count,
            'comments' => $this->comments_count,
            'views' => $this->views_count,
            'is_liked' => (bool) ($this->is_liked ?? false),
            'is_followed' => (bool) ($this->author_is_followed ?? false),
            'video_url' => $this->hlsUrl(),
            'poster_url' => $this->posterUrl(),
            'download_url' => $this->downloadUrl(),
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => new AppUserResource($this->whenLoaded('author')),
        ];
    }
}
