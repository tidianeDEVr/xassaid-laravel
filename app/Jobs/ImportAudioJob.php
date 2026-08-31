<?php

namespace App\Jobs;

use App\Models\Audio;
use App\Models\AudioImport;
use App\Support\YtDlp;
use App\Support\YtDlpException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Import back-office d'un audio par lien : yt-dlp télécharge la meilleure
 * piste audio et la convertit en MP3 (même bitrate que l'upload manuel),
 * puis le fichier est envoyé sur le serveur de fichiers et l'Audio créé
 * dans la catégorie choisie. Le titre reprend celui de la vidéo source.
 */
class ImportAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 1200;

    public function __construct(
        public int $importId,
    ) {}

    public function handle(): void
    {
        $import = AudioImport::find($this->importId);
        if (! $import || $import->status !== AudioImport::STATUS_PROCESSING) {
            return;
        }

        $directory = Storage::disk('local')->path('audio-imports/'.$import->id);

        try {
            $result = YtDlp::download(
                $import->source_url,
                $directory,
                $this->timeout - 60,
                audioOnly: true,
                audioBitrateKbps: (int) config('services.xassaid.audio_bitrate', 96),
            );
        } catch (YtDlpException $e) {
            Log::error('Import audio yt-dlp échoué', [
                'audio_import_id' => $import->id,
                'url' => $import->source_url,
                'stderr' => $e->stderrTail(),
            ]);
            $this->markFailed($import, $e->getMessage());

            return;
        }

        $title = $result['title'] !== '' ? mb_substr($result['title'], 0, 255) : 'Audio importé';
        // Même convention de nommage que l'upload manuel (AudioController) ;
        // Str::slug translittère les accents des titres YouTube.
        $baseName = Str::slug($title);
        $name = ($baseName !== '' ? $baseName.'-' : '').time();
        $uploadFilename = $name.'.mp3';

        try {
            $endpoint = rtrim((string) config('services.xassaid.files_uri'), '/').'/upload.php';
            $response = Http::timeout(1000)
                ->attach('file', fopen($result['path'], 'r'), $uploadFilename)
                ->post($endpoint, [
                    'key' => config('services.xassaid.upload_key'),
                    'filename' => $name,
                ]);
        } catch (\Throwable $e) {
            Log::error('Upload de l\'audio importé impossible', [
                'audio_import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($import, 'Serveur de fichiers injoignable.');

            return;
        }

        if (! $response->successful() || $response->json('status') !== 'success') {
            Log::error('Upload de l\'audio importé refusé', [
                'audio_import_id' => $import->id,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            $this->markFailed($import, 'Upload refusé par le serveur de fichiers (HTTP '.$response->status().').');

            return;
        }

        Audio::create([
            'title' => $title,
            'slug' => $this->generateSlug($title, $name),
            'pathToFile' => $uploadFilename,
            'category_id' => $import->category_id,
        ]);

        // L'Audio définitif remplace la ligne d'import.
        $import->delete();
        Storage::disk('local')->deleteDirectory('audio-imports/'.$import->id);
    }

    public function failed(?\Throwable $exception): void
    {
        if ($import = AudioImport::find($this->importId)) {
            $this->markFailed($import, 'Le traitement de l\'audio a échoué.');
        }

        Log::error('ImportAudioJob failed', [
            'audio_import_id' => $this->importId,
            'exception' => $exception?->getMessage(),
        ]);
    }

    private function markFailed(AudioImport $import, string $reason): void
    {
        $import->update([
            'status' => AudioImport::STATUS_FAILED,
            'error' => mb_substr($reason, 0, 500),
        ]);
        Storage::disk('local')->deleteDirectory('audio-imports/'.$import->id);
    }

    /** Même logique que Controller::generateSlug, avec repli sur le nom horodaté. */
    private function generateSlug(string $title, string $fallback): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');

        return $slug !== '' ? $slug : $fallback;
    }
}
