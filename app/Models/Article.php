<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasNaturalSortKey;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
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
        'seo_keywords',
        'seo_title',
        'seo_description',
        'image',
        'content'
    ];

    protected $table = 'articles';
}
