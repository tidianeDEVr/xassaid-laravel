<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Import back-office : télécharge une vidéo depuis un lien (YouTube Shorts,
 * Instagram Reels...) via yt-dlp, puis enchaîne sur le transcodage HLS avec
 * publication directe.
 */
class ImportVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(
        public int $videoId,
        public string $sourceUrl,
    ) {
    }

    public function handle(): void
    {
        $video = Video::find($this->videoId);
        if (!$video || $video->status !== Video::STATUS_PROCESSING) {
            return;
        }

        $directory = Storage::disk('local')->path('videos-src/' . $video->id);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $process = new Process([
            $this->ytDlpBin(),
            '--no-playlist',
            '--no-simulate',
            '--max-filesize', '500M',
            // Meilleure piste vidéo+audio, sortie mp4 (fusion via ffmpeg).
            '-f', 'bv*[ext=mp4]+ba[ext=m4a]/b[ext=mp4]/b',
            '--merge-output-format', 'mp4',
            '--print-to-file', 'title', "$directory/title.txt",
            '-o', "$directory/original.%(ext)s",
            $this->sourceUrl,
        ]);
        $process->setTimeout($this->timeout - 60);
        $process->run();

        $original = "$directory/original.mp4";
        if (!$process->isSuccessful() || !is_file($original)) {
            Log::error('Import yt-dlp échoué', [
                'video_id' => $video->id,
                'url' => $this->sourceUrl,
                'stderr' => implode(' | ', array_slice(
                    preg_split('/\r?\n/', trim($process->getErrorOutput())),
                    -3,
                )),
            ]);
            $video->update([
                'status' => Video::STATUS_FAILED,
                'rejected_reason' => 'Téléchargement impossible depuis le lien.',
            ]);
            Storage::disk('local')->deleteDirectory('videos-src/' . $video->id);
            return;
        }

        // La description provisoire (le lien) est remplacée par le titre.
        $title = is_file("$directory/title.txt")
            ? trim((string) file_get_contents("$directory/title.txt"))
            : '';
        @unlink("$directory/title.txt");

        $video->update([
            'original_path' => 'videos-src/' . $video->id . '/original.mp4',
            'description' => $title !== '' ? mb_substr($title, 0, 2000) : $video->description,
        ]);

        TranscodeVideoJob::dispatch($video->id, publishDirectly: true);
    }

    public function failed(?\Throwable $exception): void
    {
        Video::find($this->videoId)?->update([
            'status' => Video::STATUS_FAILED,
            'rejected_reason' => 'Téléchargement impossible depuis le lien.',
        ]);

        Log::error('ImportVideoJob failed', [
            'video_id' => $this->videoId,
            'url' => $this->sourceUrl,
            'exception' => $exception?->getMessage(),
        ]);
    }

    private function ytDlpBin(): string
    {
        return env('YTDLP_BIN', 'yt-dlp');
    }
}
