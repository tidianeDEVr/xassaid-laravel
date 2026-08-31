<?php

namespace App\Jobs;

use App\Models\Video;
use App\Support\YtDlp;
use App\Support\YtDlpException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    ) {}

    public function handle(): void
    {
        $video = Video::find($this->videoId);
        if (! $video || $video->status !== Video::STATUS_PROCESSING) {
            return;
        }

        $directory = Storage::disk('local')->path('videos-src/'.$video->id);

        try {
            $result = YtDlp::download($this->sourceUrl, $directory, $this->timeout - 60);
        } catch (YtDlpException $e) {
            Log::error('Import yt-dlp échoué', [
                'video_id' => $video->id,
                'url' => $this->sourceUrl,
                'stderr' => $e->stderrTail(),
            ]);
            $video->update([
                'status' => Video::STATUS_FAILED,
                'rejected_reason' => $e->getMessage(),
            ]);
            Storage::disk('local')->deleteDirectory('videos-src/'.$video->id);

            return;
        }

        // La description provisoire (le lien) est remplacée par le titre.
        $video->update([
            'original_path' => 'videos-src/'.$video->id.'/'.basename($result['path']),
            'description' => $result['title'] !== ''
                ? mb_substr($result['title'], 0, 2000)
                : $video->description,
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
}
