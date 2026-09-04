<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

class MediaOptimizer
{
    public static function normalizeFilename(string $name): string
    {
        $name = trim($name);
        $name = strtolower($name);
        $name = preg_replace('/\\s+/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);

        return trim($name, '-');
    }

    /**
     * @return array{path: string, extension: string, cleanup: bool}
     */
    public static function optimizeImage(UploadedFile $file, int $maxWidth = 1600, int $maxHeight = 1600, int $quality = 82): array
    {
        $path = $file->getPathname();
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['svg', 'gif'], true)) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        $info = @getimagesize($path);
        if (! $info) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        [$width, $height, $type] = $info;
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);

        if ($ratio >= 1) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        switch ($type) {
            case IMAGETYPE_JPEG:
                if (! function_exists('imagecreatefromjpeg')) {
                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                $source = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                if (! function_exists('imagecreatefrompng')) {
                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                $source = imagecreatefrompng($path);
                break;
            case IMAGETYPE_WEBP:
                if (! function_exists('imagecreatefromwebp')) {
                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                $source = imagecreatefromwebp($path);
                break;
            default:
                return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        if (! $source) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        if (! function_exists('imagecreatetruecolor')) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }
        $dest = imagecreatetruecolor($newWidth, $newHeight);
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $tmpPath = tempnam(sys_get_temp_dir(), 'img_');
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($dest, $tmpPath, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($dest, $tmpPath, 6);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($dest, $tmpPath, $quality);
                } else {
                    imagedestroy($dest);
                    imagedestroy($source);

                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                break;
        }

        imagedestroy($dest);
        imagedestroy($source);

        return ['path' => $tmpPath, 'extension' => $extension, 'cleanup' => true];
    }

    /**
     * Avatar carré : recadrage centré puis redimensionnement en
     * {$size}x{$size} (600 par défaut), sortie JPEG.
     *
     * @return array{path: string, extension: string, cleanup: bool}|null
     *                                                                    null si l'image est illisible.
     */
    public static function squareAvatar(UploadedFile $file, int $size = 600, int $quality = 85): ?array
    {
        $path = $file->getPathname();
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        [$width, $height, $type] = $info;
        $source = match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
        if (! $source) {
            return null;
        }

        // Recadrage centré sur le plus petit côté.
        $crop = min($width, $height);
        $cropX = (int) (($width - $crop) / 2);
        $cropY = (int) (($height - $crop) / 2);

        $dest = imagecreatetruecolor($size, $size);
        // Le JPEG n'a pas d'alpha : les PNG/WebP transparents sont aplatis
        // sur le fond sombre de l'app.
        $background = imagecolorallocate($dest, 24, 24, 27);
        imagefill($dest, 0, 0, $background);
        imagecopyresampled($dest, $source, 0, 0, $cropX, $cropY, $size, $size, $crop, $crop);

        $tmpPath = tempnam(sys_get_temp_dir(), 'ava_');
        imagejpeg($dest, $tmpPath, $quality);
        imagedestroy($dest);
        imagedestroy($source);

        return ['path' => $tmpPath, 'extension' => 'jpg', 'cleanup' => true];
    }

    /**
     * @return array{path: string, extension: string, cleanup: bool, error: ?string}
     */
    /**
     * Carré {$size}x{$size} en WebP (recadrage centré). null si l'image est
     * illisible ou si GD n'a pas le WebP : l'appelant garde alors son repli.
     *
     * @return array{path: string, extension: string, cleanup: bool}|null
     */
    public static function squareWebp(UploadedFile $file, int $size = 400, int $quality = 85): ?array
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $path = $file->getPathname();
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }
        [$width, $height, $type] = $info;
        $source = match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            IMAGETYPE_GIF => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : null,
            default => null,
        };
        if (! $source) {
            return null;
        }
        $side = min($width, $height);
        $srcX = (int) floor(($width - $side) / 2);
        $srcY = (int) floor(($height - $side) / 2);
        $dest = imagecreatetruecolor($size, $size);
        // fond sombre pour les PNG transparents (couleur du site)
        imagefill($dest, 0, 0, imagecolorallocate($dest, 24, 24, 27));
        imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $size, $size, $side, $side);
        $tmpPath = tempnam(sys_get_temp_dir(), 'img_');
        imagewebp($dest, $tmpPath, $quality);
        imagedestroy($dest);
        imagedestroy($source);

        return ['path' => $tmpPath, 'extension' => 'webp', 'cleanup' => true];
    }

    public static function optimizeAudio(UploadedFile $file, int $bitrateKbps = 96): array
    {
        $path = $file->getPathname();
        $extension = strtolower($file->getClientOriginalExtension());
        $ffmpeg = config('services.xassaid.ffmpeg_bin') ?: null;

        if (! $ffmpeg) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false, 'error' => 'FFMPEG_BIN non configuré : audio envoyé sans compression.'];
        }

        // Chemin inexistant (ex. .env copié d'une autre machine) : PATH.
        if (str_contains($ffmpeg, '/') && ! is_file($ffmpeg)) {
            $ffmpeg = 'ffmpeg';
        }

        $output = tempnam(sys_get_temp_dir(), 'aud_').'.mp3';

        $process = new Process([
            $ffmpeg,
            '-y',
            '-i',
            $path,
            '-b:a',
            $bitrateKbps.'k',
            '-ac',
            '2',
            '-ar',
            '44100',
            $output,
        ]);
        $process->setTimeout(120);

        try {
            $process->run();
        } catch (\Throwable $e) {
            if (file_exists($output)) {
                @unlink($output);
            }

            return ['path' => $path, 'extension' => $extension, 'cleanup' => false, 'error' => 'FFmpeg : '.$e->getMessage()];
        }

        if ($process->isSuccessful() && file_exists($output) && filesize($output) > 0) {
            return ['path' => $output, 'extension' => 'mp3', 'cleanup' => true, 'error' => null];
        }

        if (file_exists($output)) {
            @unlink($output);
        }

        $stderr = trim($process->getErrorOutput());
        $error = 'FFmpeg a échoué (code '.$process->getExitCode().')';
        if ($stderr !== '') {
            // FFmpeg est très verbeux : on ne garde que les dernières lignes, les plus utiles
            $lines = preg_split('/\r?\n/', $stderr);
            $error .= ' : '.implode(' | ', array_slice($lines, -3));
        }

        return ['path' => $path, 'extension' => $extension, 'cleanup' => false, 'error' => $error];
    }
}
