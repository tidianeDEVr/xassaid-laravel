<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AppUser */
class AppUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatarUrl(),
            'is_certified' => $this->is_certified,
            'videos_count' => $this->videos_count,
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
        ];
    }
}
