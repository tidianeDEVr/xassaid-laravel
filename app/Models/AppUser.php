<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Utilisateur de l'application mobile (feed vidéo).
 *
 * Distinct des `User` du back-office : identité = pseudo, pas d'email.
 * Seul modèle porteur de HasApiTokens — un token Sanctum ne peut donc
 * résoudre que vers un AppUser.
 */
class AppUser extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'username',
        'display_name',
        'avatar_path',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $attributes = [
        'is_certified' => false,
        'videos_count' => 0,
        'followers_count' => 0,
        'following_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_certified' => 'boolean',
        ];
    }

    public function avatarUrl(): ?string
    {
        return \App\Support\MediaStorage::url($this->avatar_path);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function followers()
    {
        return $this->belongsToMany(AppUser::class, 'follows', 'followed_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(AppUser::class, 'follows', 'follower_id', 'followed_id');
    }
}
