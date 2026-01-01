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
        if (!$info) {
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
                if (!function_exists('imagecreatefromjpeg')) {
                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                $source = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                if (!function_exists('imagecreatefrompng')) {
                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                $source = imagecreatefrompng($path);
                break;
            case IMAGETYPE_WEBP:
                if (!function_exists('imagecreatefromwebp')) {
                    return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
                }
                $source = imagecreatefromwebp($path);
                break;
            default:
                return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        if (!$source) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        if (!function_exists('imagecreatetruecolor')) {
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
     * @return array{path: string, extension: string, cleanup: bool}
     */
    public static function optimizeAudio(UploadedFile $file, int $bitrateKbps = 96): array
    {
        $path = $file->getPathname();
        $extension = strtolower($file->getClientOriginalExtension());
        $ffmpeg = env('FFMPEG_BIN');

        if (!$ffmpeg) {
            return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
        }

        $output = tempnam(sys_get_temp_dir(), 'aud_') . '.mp3';

        $process = new Process([
            $ffmpeg,
            '-y',
            '-i',
            $path,
            '-b:a',
            $bitrateKbps . 'k',
            '-ac',
            '2',
            '-ar',
            '44100',
            $output,
        ]);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful() && file_exists($output) && filesize($output) > 0) {
            return ['path' => $output, 'extension' => 'mp3', 'cleanup' => true];
        }

        if (file_exists($output)) {
            @unlink($output);
        }

        return ['path' => $path, 'extension' => $extension, 'cleanup' => false];
    }
}
