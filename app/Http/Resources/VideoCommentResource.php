<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Commentaire du feed, calqué sur le modèle VideoComment de l'app mobile
 * (un seul niveau de réponses). `is_liked` est posé par le contrôleur.
 *
 * @mixin \App\Models\VideoComment
 */
class VideoCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'likes' => $this->likes_count,
            'is_liked' => (bool) ($this->is_liked ?? false),
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => new AppUserResource($this->whenLoaded('author')),
            'replies' => VideoCommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
