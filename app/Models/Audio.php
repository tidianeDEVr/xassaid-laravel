<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasNaturalSortKey;
use Illuminate\Database\Eloquent\Model;

class Audio extends Model
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
        'pathToFile',
        'category_id'
    ];

    protected $table = 'audios';

    protected $with = ['category'];

    public function category()
    {
        return $this->belongsTo(AudioCategory::class);
    }
}
