<?php

namespace App\Jobs;

use App\Models\Video;
use App\Support\MediaStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Transcode l'original uploadé en HLS multi-qualités + poster.
 *
 * Ladder sur le PETIT côté (portrait-aware) : 720 / 480 / 360, sans jamais
 * upscaler. Chaque rendition est encodée séparément puis le master.m3u8 est
 * écrit en PHP — plus simple à maintenir et à déboguer qu'un var_stream_map.
 */
class TranscodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;
    public int $timeout = 1200;

    /** Durée maximale acceptée pour une vidéo du feed. */
    private const MAX_DURATION_SECONDS = 300;

    /** Petit côté cible => [bitrate vidéo kbps, bitrate audio kbps]. */
    private const LADDER = [
        720 => [2500, 128],
        480 => [1200, 96],
        360 => [700, 64],
    ];

    /**
     * [$publishDirectly] court-circuite la modération (imports back-office) :
     * la vidéo est publiée dès le transcodage terminé, quel que soit le
     * statut de certification de l'auteur.
     */
    public function __construct(
        public int $videoId,
        public bool $publishDirectly = false,
    ) {
    }

    public function handle(): void
    {
        $video = Video::find($this->videoId);
        if (!$video || $video->status !== Video::STATUS_PROCESSING) {
            return;
        }

        $source = Storage::disk('local')->path($video->original_path);
        if (!is_file($source)) {
            $this->markFailed($video, 'Fichier original introuvable.');
            return;
        }

        $probe = $this->probe($source);
        if ($probe === null) {
            $this->markFailed($video, 'Impossible de lire la vidéo (ffprobe).');
            return;
        }

        if ($probe['duration'] > self::MAX_DURATION_SECONDS) {
            $this->markFailed($video, 'Vidéo trop longue (5 minutes maximum).');
            return;
        }

        $smallSide = min($probe['width'], $probe['height']);
        $portrait = $probe['height'] > $probe['width'];

        // Renditions à produire : jamais d'upscale ; si la source est plus
        // petite que 360p, une seule rendition à la taille d'origine.
        $targets = array_filter(array_keys(self::LADDER), fn ($t) => $t <= $smallSide);
        if ($targets === []) {
            $targets = [$smallSide - ($smallSide % 2)];
        }

        // Tout est produit dans un dossier de travail local (ffmpeg a besoin
        // du filesystem), puis publié d'un bloc sur le disque média
        // (public en dev, S3 en prod).
        $outputDir = 'videos/' . $video->id;
        $workDir = Storage::disk('local')->path('transcode-tmp/' . $video->id);
        Storage::disk('local')->deleteDirectory('transcode-tmp/' . $video->id);

        $renditions = [];
        foreach (array_values($targets) as $i => $target) {
            [$videoKbps, $audioKbps] = self::LADDER[$target] ?? [700, 64];

            $scale = $portrait ? "scale=$target:-2" : "scale=-2:$target";
            $dest = "$workDir/v$i";
            if (!is_dir($dest)) {
                mkdir($dest, 0775, true);
            }

            $process = new Process([
                $this->ffmpegBin(), '-y', '-i', $source,
                '-vf', $scale,
                '-c:v', 'libx264', '-profile:v', 'main', '-pix_fmt', 'yuv420p',
                '-b:v', $videoKbps . 'k',
                '-maxrate', (int) round($videoKbps * 1.07) . 'k',
                '-bufsize', ($videoKbps * 2) . 'k',
                '-preset', 'veryfast', '-g', '48', '-keyint_min', '48', '-sc_threshold', '0',
                '-c:a', 'aac', '-b:a', $audioKbps . 'k', '-ac', '2', '-ar', '44100',
                '-f', 'hls', '-hls_time', '4', '-hls_playlist_type', 'vod',
                '-hls_segment_filename', "$dest/seg_%03d.ts",
                "$dest/index.m3u8",
            ]);
            $process->setTimeout($this->timeout - 60);
            $process->run();

            if (!$process->isSuccessful() || !is_file("$dest/index.m3u8")) {
                Log::error('Transcodage échoué', [
                    'video_id' => $video->id,
                    'target' => $target,
                    'stderr' => self::tailOf($process->getErrorOutput()),
                ]);
                Storage::disk('local')->deleteDirectory('transcode-tmp/' . $video->id);
                $this->markFailed($video, "Le transcodage a échoué ({$target}p).");
                return;
            }

            $renditions[] = [
                'name' => $target . 'p',
                'playlist' => "v$i/index.m3u8",
                'width' => $portrait ? $target : $this->scaledLargeSide($probe, $target),
                'height' => $portrait ? $this->scaledLargeSide($probe, $target) : $target,
                'bandwidth' => (int) (($videoKbps + $audioKbps) * 1000 * 1.1),
            ];
        }

        file_put_contents("$workDir/master.m3u8", $this->masterPlaylist($renditions));
        $this->extractPoster($source, $workDir, min(720, $smallSide), $portrait);
        $hasPoster = is_file("$workDir/poster.jpg");
        $hasDownload = $this->buildDownloadMp4($workDir);

        // Publication : remplace l'éventuel contenu précédent du même id.
        MediaStorage::deleteDirectory($outputDir);
        MediaStorage::publishDirectory($workDir, $outputDir);

        $isCertified = $this->publishDirectly || (bool) $video->author?->is_certified;
        $video->update([
            'status' => $isCertified ? Video::STATUS_PUBLISHED : Video::STATUS_PENDING_REVIEW,
            'published_at' => $isCertified ? now() : null,
            'hls_path' => "$outputDir/master.m3u8",
            'poster_path' => $hasPoster ? "$outputDir/poster.jpg" : null,
            'download_path' => $hasDownload ? "$outputDir/download.mp4" : null,
            'duration_ms' => (int) round($probe['duration'] * 1000),
            'width' => $probe['width'],
            'height' => $probe['height'],
            'renditions' => $renditions,
        ]);

        if ($isCertified) {
            $video->author->increment('videos_count');
        }

        // L'original ne sert plus : le disque du VPS est la contrainte.
        Storage::disk('local')->deleteDirectory('videos-src/' . $video->id);
    }

    public function failed(?\Throwable $exception): void
    {
        if ($video = Video::find($this->videoId)) {
            $this->markFailed($video, 'Le traitement de la vidéo a échoué.');
        }

        Log::error('TranscodeVideoJob failed', [
            'video_id' => $this->videoId,
            'exception' => $exception?->getMessage(),
        ]);
    }

    /**
     * @return array{duration: float, width: int, height: int}|null
     *         width/height sont les dimensions D'AFFICHAGE (rotation appliquée,
     *         comme le fait ffmpeg à l'encodage).
     */
    private function probe(string $source): ?array
    {
        $process = new Process([
            $this->ffprobeBin(), '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height:stream_side_data=rotation:format=duration',
            '-of', 'json', $source,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $data = json_decode($process->getOutput(), true);
        $stream = $data['streams'][0] ?? null;
        $duration = (float) ($data['format']['duration'] ?? 0);

        if (!$stream || empty($stream['width']) || empty($stream['height']) || $duration <= 0) {
            return null;
        }

        $width = (int) $stream['width'];
        $height = (int) $stream['height'];

        $rotation = 0;
        foreach ($stream['side_data_list'] ?? [] as $sideData) {
            if (isset($sideData['rotation'])) {
                $rotation = (int) $sideData['rotation'];
            }
        }
        if (abs($rotation) % 180 === 90) {
            [$width, $height] = [$height, $width];
        }

        return ['duration' => $duration, 'width' => $width, 'height' => $height];
    }

    private function scaledLargeSide(array $probe, int $targetSmallSide): int
    {
        $large = max($probe['width'], $probe['height']);
        $small = min($probe['width'], $probe['height']);

        // Arrondi au pair le plus proche, comme le fait `scale=...:-2`.
        return (int) (2 * round($large * $targetSmallSide / $small / 2));
    }

    private function masterPlaylist(array $renditions): string
    {
        $lines = ['#EXTM3U', '#EXT-X-VERSION:3'];
        foreach ($renditions as $rendition) {
            $lines[] = sprintf(
                '#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%dx%d',
                $rendition['bandwidth'],
                $rendition['width'],
                $rendition['height'],
            );
            $lines[] = $rendition['playlist'];
        }

        return implode("\n", $lines) . "\n";
    }

    private function extractPoster(string $source, string $outputDir, int $smallSide, bool $portrait): void
    {
        $scale = $portrait ? "scale=$smallSide:-2" : "scale=-2:$smallSide";

        // -ss 1 pour éviter une première frame noire ; -ss 0 si la vidéo est
        // trop courte.
        foreach (['1', '0'] as $seek) {
            $process = new Process([
                $this->ffmpegBin(), '-y', '-ss', $seek, '-i', $source,
                '-frames:v', '1', '-vf', $scale, '-q:v', '3',
                "$outputDir/poster.jpg",
            ]);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful() && is_file("$outputDir/poster.jpg")) {
                return;
            }
        }

        Log::warning('Extraction du poster échouée', ['video_id' => $this->videoId]);
    }

    /**
     * MP4 téléchargeable pour la galerie du téléphone : simple remux
     * (`-c copy`) de la meilleure rendition HLS, aucun ré-encodage.
     */
    private function buildDownloadMp4(string $outputDir): bool
    {
        $playlist = "$outputDir/v0/index.m3u8";
        if (!is_file($playlist)) {
            return false;
        }

        $process = new Process([
            $this->ffmpegBin(), '-y', '-i', $playlist,
            '-c', 'copy', '-movflags', '+faststart',
            "$outputDir/download.mp4",
        ]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful() || !is_file("$outputDir/download.mp4")) {
            Log::warning('Remux download.mp4 échoué', [
                'video_id' => $this->videoId,
                'stderr' => self::tailOf($process->getErrorOutput()),
            ]);
            @unlink("$outputDir/download.mp4");
            return false;
        }

        return true;
    }

    private function markFailed(Video $video, string $reason): void
    {
        $video->update([
            'status' => Video::STATUS_FAILED,
            'rejected_reason' => $reason,
        ]);

        // Échec terminal : l'original ne sera pas retenté, on libère le disque.
        Storage::disk('local')->deleteDirectory('videos-src/' . $video->id);
    }

    private function ffmpegBin(): string
    {
        // Un FFMPEG_BIN pointant un chemin inexistant (ex. chemin du poste de
        // dev copié dans le .env du serveur) donnerait un exec silencieux en
        // exit 127 : on retombe sur le PATH.
        $configured = (string) env('FFMPEG_BIN', 'ffmpeg');
        if (str_contains($configured, '/') && !is_file($configured)) {
            return 'ffmpeg';
        }

        return $configured;
    }

    private function ffprobeBin(): string
    {
        $configured = (string) env('FFPROBE_BIN', '');
        if ($configured !== '') {
            return $configured;
        }

        // Même dossier que ffmpeg, sinon le PATH.
        $ffmpeg = $this->ffmpegBin();
        $candidate = str_replace('ffmpeg', 'ffprobe', $ffmpeg);

        return $candidate !== $ffmpeg && is_file($candidate) ? $candidate : 'ffprobe';
    }

    private static function tailOf(string $stderr): string
    {
        $lines = preg_split('/\r?\n/', trim($stderr));

        return implode(' | ', array_slice($lines, -3));
    }
}
