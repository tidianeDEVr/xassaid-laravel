<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * Téléchargement d'un média depuis un lien public (YouTube, Instagram...)
 * via yt-dlp, pour les imports back-office : la vidéo complète
 * (ImportVideoJob) ou seulement la piste audio convertie en MP3
 * (ImportAudioJob).
 */
class YtDlp
{
    /**
     * Télécharge `$url` dans `$directory` sous le nom `original.<ext>`.
     *
     * En mode audio, la piste est extraite et convertie en MP3 par yt-dlp
     * (via ffmpeg) : le résultat est toujours `original.mp3`.
     *
     * @return array{path: string, title: string} chemin absolu du fichier +
     *                                            titre de la vidéo source (chaîne vide si indisponible)
     *
     * @throws YtDlpException motif lisible pour le back-office
     */
    public static function download(
        string $url,
        string $directory,
        int $timeout,
        bool $audioOnly = false,
        int $audioBitrateKbps = 96,
    ): array {
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        // Instagram/YouTube bloquent souvent les IP de datacenter : un fichier
        // de cookies (format Netscape, exporté d'un navigateur connecté) lève
        // le blocage. Optionnel : YTDLP_COOKIES=/chemin/cookies.txt dans .env.
        $cookies = (string) config('services.ytdlp.cookies', '');
        $proxy = (string) config('services.ytdlp.proxy', '');
        // Générateur de PO tokens (bgutil) : lève le blocage anti-bot que
        // YouTube impose aux IP de datacenter.
        $potProvider = (string) config('services.ytdlp.pot_provider', '');

        $process = new Process([
            self::bin(),
            '--no-playlist',
            '--no-simulate',
            '--max-filesize', '500M',
            ...($cookies !== '' && is_file($cookies) ? ['--cookies', $cookies] : []),
            ...($proxy !== '' ? ['--proxy', $proxy] : []),
            ...($potProvider !== ''
                ? ['--extractor-args', 'youtubepot-bgutilhttp:base_url=' . $potProvider]
                : []),
            ...($audioOnly
                // Meilleure piste audio, extraite et convertie en MP3 au
                // bitrate de l'app (même valeur que l'upload manuel).
                ? ['-f', 'ba/b', '-x', '--audio-format', 'mp3', '--audio-quality', $audioBitrateKbps.'K']
                // Meilleure piste vidéo+audio, sortie mp4 (fusion via ffmpeg).
                : ['-f', 'bv*[ext=mp4]+ba[ext=m4a]/b[ext=mp4]/b', '--merge-output-format', 'mp4']),
            '--print-to-file', 'title', "$directory/title.txt",
            '-o', "$directory/original.%(ext)s",
            $url,
        ]);
        $process->setTimeout($timeout);
        $startError = null;
        try {
            $process->run();
        } catch (\Throwable $e) {
            // Binaire introuvable ou non exécutable : pas de stderr du tout.
            $startError = $e->getMessage();
        }

        $path = self::downloadedFile($directory, $audioOnly);
        if ($startError !== null || ! $process->isSuccessful() || $path === null) {
            $stderr = $startError ?? trim($process->getErrorOutput());
            throw new YtDlpException(self::failureReason($stderr), $stderr);
        }

        $title = is_file("$directory/title.txt")
            ? trim((string) file_get_contents("$directory/title.txt"))
            : '';
        @unlink("$directory/title.txt");

        return ['path' => $path, 'title' => $title];
    }

    public static function bin(): string
    {
        return (string) config('services.ytdlp.bin', 'yt-dlp');
    }

    /**
     * Fichier réellement produit. En vidéo, le sélecteur de format retombe
     * parfois sur un conteneur non-mp4 (webm...) : on l'accepte, le
     * transcodage HLS ré-encode de toute façon.
     */
    private static function downloadedFile(string $directory, bool $audioOnly): ?string
    {
        if ($audioOnly) {
            return is_file("$directory/original.mp3") ? "$directory/original.mp3" : null;
        }

        if (is_file("$directory/original.mp4")) {
            return "$directory/original.mp4";
        }
        foreach (glob("$directory/original.*") ?: [] as $candidate) {
            if (! str_ends_with($candidate, '.txt') && ! str_ends_with($candidate, '.part')) {
                return $candidate;
            }
        }

        return null;
    }

    /** Motif lisible dans le back-office selon la cause réelle de l'échec. */
    private static function failureReason(string $stderr): string
    {
        $haystack = strtolower($stderr);

        if (str_contains($haystack, 'sign in to confirm') || str_contains($haystack, 'not a bot')) {
            return 'YouTube bloque le serveur (vérification anti-bot) — configurer YTDLP_COOKIES ou réessayer plus tard.';
        }
        if (str_contains($haystack, 'n challenge') || str_contains($haystack, 'needs to be reloaded')
            || str_contains($haystack, 'javascript runtime') || str_contains($haystack, 'js runtime')) {
            return 'Runtime JavaScript (Deno) absent du serveur — rebuild de l\'image Docker requis (make all).';
        }
        if (str_contains($haystack, 'login') || str_contains($haystack, 'rate-limit')
            || str_contains($haystack, 'not available') || str_contains($haystack, 'restricted')) {
            return 'La plateforme bloque le serveur (connexion requise) — configurer YTDLP_COOKIES.';
        }
        if (str_contains($haystack, '404') || str_contains($haystack, 'not found')) {
            return 'Lien introuvable ou vidéo supprimée.';
        }
        if (str_contains($haystack, 'no such file') || str_contains($haystack, 'not found: yt-dlp')) {
            return 'yt-dlp absent du serveur — rebuild de l\'image Docker requis.';
        }
        if (str_contains($haystack, 'max-filesize')) {
            return 'Vidéo trop volumineuse (500 Mo maximum).';
        }
        if (str_contains($haystack, 'unsupported url')) {
            return 'Plateforme non supportée par yt-dlp.';
        }
        // Les extracteurs YouTube des vieilles versions de yt-dlp finissent
        // toujours par casser : le premier réflexe est de mettre à jour.
        if (str_contains($haystack, 'nsig') || str_contains($haystack, 'player')
            || str_contains($haystack, 'requested format is not available')
            || str_contains($haystack, 'unable to extract')) {
            return 'Extraction impossible — yt-dlp est probablement obsolète, redéployer l\'image Docker (make all).';
        }

        return 'Téléchargement impossible depuis le lien.';
    }
}
