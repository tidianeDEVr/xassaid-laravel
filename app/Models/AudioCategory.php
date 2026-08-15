<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasNaturalSortKey;
use Illuminate\Database\Eloquent\Model;

class AudioCategory extends Model
{
    use HasFactory, HasNaturalSortKey;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'title',
        'type',
        'coverImagePath',
    ];

    public function audios()
    {
        return $this->hasMany(Audio::class, 'category_id');
    }
}
