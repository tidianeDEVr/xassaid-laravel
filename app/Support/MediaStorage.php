<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Disque des médias du feed vidéo (avatars, HLS, posters, MP4).
 *
 * `MEDIA_DISK=public` en dev (fichiers servis par /storage), `MEDIA_DISK=s3`
 * en production : tout le code passe par ici, la bascule est une variable
 * d'environnement.
 */
class MediaStorage
{
    public static function diskName(): string
    {
        return config('filesystems.media_disk', 'public');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    /** URL publique d'un chemin du disque média. */
    public static function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // En local, APP_URL pointe le site vitrine : on suit l'hôte de la
        // requête. Sur S3, l'URL vient du disque (endpoint/bucket ou AWS_URL).
        if (self::diskName() === 'public') {
            return url('storage/' . $path);
        }

        return self::disk()->url($path);
    }

    /**
     * Écrit un fichier (contenu brut). Aucune ACL par objet : sur S3 la
     * lecture publique vient de la bucket policy, en local du serveur web.
     */
    public static function put(string $path, string $contents): void
    {
        self::disk()->put($path, $contents);
    }

    /**
     * Publie récursivement un dossier local vers `$remotePrefix` sur le
     * disque média. Sur le disque `public`, simple déplacement ; sur S3,
     * upload fichier par fichier.
     */
    public static function publishDirectory(string $localDirectory, string $remotePrefix): void
    {
        if (self::diskName() === 'public') {
            $target = self::disk()->path($remotePrefix);
            if (is_dir($target)) {
                self::disk()->deleteDirectory($remotePrefix);
            }
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
            }
            rename($localDirectory, $target);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $localDirectory,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = ltrim(substr($file->getPathname(), strlen($localDirectory)), '/');
            $directory = trim($remotePrefix . '/' . dirname($relative), '/.');
            self::disk()->putFileAs(
                $directory === '' ? $remotePrefix : $directory,
                new File($file->getPathname()),
                $file->getBasename(),
            );
        }

        Storage::disk('local')->deleteDirectory(
            str_replace(Storage::disk('local')->path(''), '', $localDirectory),
        );
    }

    public static function deleteDirectory(string $path): void
    {
        self::disk()->deleteDirectory($path);
    }

    public static function delete(string $path): void
    {
        self::disk()->delete($path);
    }
}
