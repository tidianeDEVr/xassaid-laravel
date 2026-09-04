<?php

namespace App\Models\Concerns;

use App\Models\SlugRedirect;

/**
 * Mémorise les anciens slugs à chaque renommage et permet de retrouver une
 * entité par son slug actuel OU un ancien slug (redirection 301 côté front).
 */
trait RedirectsOldSlugs
{
    public static function bootRedirectsOldSlugs(): void
    {
        static::updated(function ($model) {
            if ($model->wasChanged('slug')) {
                SlugRedirect::remember($model->getTable(), (string) $model->getOriginal('slug'), (string) $model->slug);
            }
        });
    }

    /** Entité pour ce slug, ou pour l'ancien slug si elle a été renommée. */
    public static function findBySlugOrRedirect(string $slug, array $with = []): ?static
    {
        $q = static::query()->with($with);
        $found = (clone $q)->where('slug', $slug)->first();
        if ($found) {
            return $found;
        }
        $current = SlugRedirect::resolve((new static)->getTable(), $slug);

        return $current ? $q->where('slug', $current)->first() : null;
    }
}
