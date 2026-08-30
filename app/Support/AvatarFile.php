<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Enregistrement d'un avatar de compte mobile.
 *
 * Un seul chemin de traitement pour les trois entrées possibles —
 * inscription depuis l'app, changement d'avatar depuis l'app, création d'un
 * compte depuis le back-office : carré 600x600 recadré au centre, JPEG, posé
 * sur le disque média (public en dev, S3 en prod).
 */
class AvatarFile
{
    /** Contrainte de validation, identique partout. */
    public const RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public const SIZE = 600;

    /**
     * Range l'image et rend son chemin sur le disque média.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException si l'image est illisible
     */
    public static function store(UploadedFile $file): string
    {
        // Sans GD, squareAvatar rend null quelle que soit l'image : autant le
        // dire, plutôt que d'accuser le fichier de l'utilisateur.
        if (!extension_loaded('gd')) {
            abort(500, "Traitement d'image indisponible : l'extension GD manque sur le serveur.");
        }

        $optimized = MediaOptimizer::squareAvatar($file, self::SIZE);
        if ($optimized === null) {
            abort(422, 'Image illisible.');
        }

        $filename = 'avatars/' . Str::uuid() . '.' . $optimized['extension'];
        MediaStorage::put($filename, file_get_contents($optimized['path']));

        if ($optimized['cleanup']) {
            @unlink($optimized['path']);
        }

        return $filename;
    }

    /** Range le nouvel avatar puis supprime le précédent. */
    public static function replace(UploadedFile $file, ?string $previous): string
    {
        $path = self::store($file);

        if ($previous) {
            MediaStorage::delete($previous);
        }

        return $path;
    }
}
