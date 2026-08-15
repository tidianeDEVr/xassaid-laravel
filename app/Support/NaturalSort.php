<?php

namespace App\Support;

class NaturalSort
{
    /**
     * Construit une clé de tri qui se compare correctement en SQL :
     * chaque suite de chiffres est complétée à gauche par des zéros,
     * ainsi "audio 2" (000...002) passe bien avant "audio 11" (000...011).
     */
    public static function key(?string $string): string
    {
        $string = trim((string) $string);

        if ($string === '') {
            return '';
        }

        $normalized = mb_strtolower($string);

        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $normalized);
        if ($transliterated !== false) {
            $normalized = mb_strtolower($transliterated);
        }

        // On ignore la ponctuation de tête ("1. Titre", "- Titre", ...)
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized));

        $normalized = preg_replace_callback(
            '/\d+/',
            fn ($matches) => str_pad($matches[0], 10, '0', STR_PAD_LEFT),
            $normalized
        );

        return mb_substr($normalized, 0, 512);
    }
}
