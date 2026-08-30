<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'app_user_id',
        'description',
        'khassida_title',
        'status',
        'original_path',
        'hls_path',
        'poster_path',
        'download_path',
        'duration_ms',
        'width',
        'height',
        'renditions',
        'published_at',
        'rejected_reason',
    ];

    protected $attributes = [
        'likes_count' => 0,
        'comments_count' => 0,
        'views_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'renditions' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function likes()
    {
        return $this->hasMany(VideoLike::class);
    }

    public function comments()
    {
        return $this->hasMany(VideoComment::class);
    }

    public function hlsUrl(): ?string
    {
        return \App\Support\MediaStorage::url($this->hls_path);
    }

    public function posterUrl(): ?string
    {
        return \App\Support\MediaStorage::url($this->poster_path);
    }

    public function downloadUrl(): ?string
    {
        return \App\Support\MediaStorage::url($this->download_path);
    }
}
