<?php

namespace App\Support;

/**
 * Curseur opaque pour la pagination du feed : le contenu bouge (nouvelles
 * publications), une page numérotée dupliquerait ou sauterait des éléments.
 */
class ApiCursor
{
    public static function encode(array $payload): string
    {
        return rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    }

    public static function decode(?string $cursor): ?array
    {
        if (!$cursor) {
            return null;
        }

        $json = base64_decode(strtr($cursor, '-_', '+/'), true);
        $payload = $json !== false ? json_decode($json, true) : null;

        return is_array($payload) ? $payload : null;
    }
}
