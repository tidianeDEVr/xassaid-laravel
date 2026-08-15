<?php

namespace App\Models\Concerns;

use App\Support\NaturalSort;
use Illuminate\Database\Eloquent\Builder;

trait HasNaturalSortKey
{
    public static function bootHasNaturalSortKey(): void
    {
        static::saving(function ($model) {
            $model->sort_key = NaturalSort::key($model->title);
        });
    }

    public function scopeOrderByTitleNatural(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('sort_key', $direction)->orderBy('title', $direction);
    }
}
