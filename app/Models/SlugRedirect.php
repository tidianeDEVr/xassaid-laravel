<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ancien slug -> slug actuel, par entité (audios, audio_categories, articles,
 * files). Alimenté automatiquement quand un slug change (trait
 * RedirectsOldSlugs) et par `slugs:dedupe --apply`. Le front redirige en 301.
 */
class SlugRedirect extends Model
{
    protected $fillable = ['entity', 'old_slug', 'new_slug'];

    /** Slug actuel pour un ancien slug (suit les chaînes de renommages). */
    public static function resolve(string $entity, string $slug): ?string
    {
        $current = $slug;
        for ($i = 0; $i < 5; $i++) {
            $next = static::where('entity', $entity)->where('old_slug', $current)->value('new_slug');
            if (! $next || $next === $current) {
                break;
            }
            $current = $next;
        }

        return $current !== $slug ? $current : null;
    }

    public static function remember(string $entity, string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }
        // le nouveau slug redevient une adresse vivante : plus de redirection depuis lui
        static::where('entity', $entity)->where('old_slug', $newSlug)->delete();
        static::updateOrCreate(['entity' => $entity, 'old_slug' => $oldSlug], ['new_slug' => $newSlug]);
        // les redirections qui pointaient vers l'ancien slug suivent le nouveau
        static::where('entity', $entity)->where('new_slug', $oldSlug)->update(['new_slug' => $newSlug]);
    }
}
