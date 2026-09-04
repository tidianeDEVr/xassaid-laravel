<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Slugs uniques par table (une page publique par entité : deux slugs identiques
 * seraient vus comme du contenu dupliqué).
 *
 * Stratégie : slug du titre ; s'il existe déjà, slug du titre + suffixe (pour
 * un audio : le slug de sa catégorie) ; s'il existe encore, on ajoute -2, -3…
 */
class Slugger
{
    /** Même normalisation que l'historique generateSlug des contrôleurs. */
    public static function base(string $text): string
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * @param  string  $table  table à vérifier (audios, audio_categories, articles, files)
     * @param  int|null  $ignoreId  id de la ligne en cours de modification (son propre slug ne compte pas)
     * @param  string|null  $suffix  texte ajouté en cas de collision (ex. titre de la catégorie)
     * @param  string|null  $fallback  slug si le titre ne donne rien (ex. nom de fichier)
     */
    public static function unique(string $text, string $table, ?int $ignoreId = null,
        ?string $suffix = null, ?string $fallback = null): string
    {
        $base = self::base($text);
        if ($base === '') {
            $base = $fallback !== null && $fallback !== '' ? self::base($fallback) : 'item';
        }
        if (! self::exists($table, $base, $ignoreId)) {
            return $base;
        }
        if ($suffix !== null && self::base($suffix) !== '') {
            $withSuffix = $base.'-'.self::base($suffix);
            if (! self::exists($table, $withSuffix, $ignoreId)) {
                return $withSuffix;
            }
            $base = $withSuffix;
        }
        for ($n = 2; $n < 1000; $n++) {
            $candidate = $base.'-'.$n;
            if (! self::exists($table, $candidate, $ignoreId)) {
                return $candidate;
            }
        }

        return $base.'-'.uniqid();
    }

    /** Audio : « titre-slug-de-la-catégorie » par défaut, puis -2, -3… si déjà pris. */
    public static function forAudio(string $title, ?string $categorySlug, ?int $ignoreId = null, ?string $fallback = null): string
    {
        $text = trim(self::base($title).' '.self::base((string) $categorySlug));

        return self::unique($text, 'audios', $ignoreId, null, $fallback);
    }

    public static function exists(string $table, string $slug, ?int $ignoreId = null): bool
    {
        $q = DB::table($table)->where('slug', $slug);
        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }
}
