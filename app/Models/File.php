<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasNaturalSortKey;
use App\Models\Concerns\RedirectsOldSlugs;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory, HasNaturalSortKey, RedirectsOldSlugs;

    protected $fillable = [
        'slug',
        'title',
        'pathToFile',
    ];
}
