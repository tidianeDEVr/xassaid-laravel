<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoLike extends Model
{
    protected $fillable = ['video_id', 'app_user_id'];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
