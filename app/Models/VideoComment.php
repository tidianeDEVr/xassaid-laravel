<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoComment extends Model
{
    protected $fillable = ['video_id', 'app_user_id', 'parent_id', 'body'];

    protected $attributes = [
        'likes_count' => 0,
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function author()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function replies()
    {
        return $this->hasMany(VideoComment::class, 'parent_id');
    }
}
