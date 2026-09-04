<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class FileHealthController extends Controller
{
    public function render()
    {
        $audioBase = $this->getAudioBaseUrl();
        $fileBase = $this->getFileBaseUrl();

        return view('pages.file-health', [
            'audioBase' => $audioBase,
            'fileBase' => $fileBase,
        ]);
    }

    public function check(Request $request)
    {
        $type = $request->query('type');
        $cursor = max((int) $request->query('cursor', 0), 0);
        $limit = (int) $request->query('limit', 30);
        $limit = min(max($limit, 1), 100);
        @set_time_limit(120);

        if ($type === 'audios') {
            $query = Audio::query()->select(['id', 'title', 'pathToFile']);
            $baseUrl = $this->getAudioBaseUrl();
        } elseif ($type === 'files') {
            $query = File::query()->select(['id', 'title', 'pathToFile']);
            $baseUrl = $this->getFileBaseUrl();
        } else {
            return response()->json(['message' => 'Type invalide.'], 422);
        }

        if ($baseUrl === '') {
            return response()->json(['message' => 'Base URL manquante.'], 422);
        }

        $records = $query
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $results = [];
        $checked = 0;
        $missing = 0;
        $nextCursor = $cursor;

        // Les sondes HTTP sont lancées en parallèle (pool) : un lot de 100
        // fichiers ne doit pas dépasser le temps d'exécution PHP.
        $urls = [];
        foreach ($records as $record) {
            $checked++;
            $nextCursor = $record->id;

            if (! $record->pathToFile) {
                $missing++;
                $results[] = [
                    'id' => $record->id,
                    'title' => $record->title,
                    'url' => null,
                    'status' => null,
                    'reason' => 'Fichier manquant dans la base',
                ];

                continue;
            }

            $urls[$record->id] = rtrim($baseUrl, '/').'/'.ltrim($record->pathToFile, '/');
        }

        $probes = $this->probeMany($urls, $type === 'files');

        foreach ($records as $record) {
            if (! isset($urls[$record->id])) {
                continue;
            }
            $probe = $probes[$record->id];
            if (! $probe['ok']) {
                $missing++;
                $results[] = [
                    'id' => $record->id,
                    'title' => $record->title,
                    'url' => $urls[$record->id],
                    'status' => $probe['status'],
                    'reason' => $probe['reason'],
                ];
            }
        }

        return response()->json([
            'items' => $results,
            'checked' => $checked,
            'missing' => $missing,
            'nextCursor' => $nextCursor,
            'done' => $records->count() < $limit,
        ]);
    }

    public function duplicates(Request $request)
    {
        $type = $request->query('type');
        $mode = $request->query('mode', 'exact');
        $limit = (int) $request->query('limit', 50);
        $offset = max((int) $request->query('offset', 0), 0);
        $limit = min(max($limit, 1), 200);

        if (! in_array($mode, ['exact', 'similar'], true)) {
            return response()->json(['message' => 'Mode invalide.'], 422);
        }

        if ($type === 'audios') {
            $query = Audio::query()->select(['id', 'title', 'pathToFile']);
            $baseUrl = $this->getAudioBaseUrl();
        } elseif ($type === 'files') {
            $query = File::query()->select(['id', 'title', 'pathToFile']);
            $baseUrl = $this->getFileBaseUrl();
        } else {
            return response()->json(['message' => 'Type invalide.'], 422);
        }

        $groups = [];
        $maxItemsPerGroup = 12;

        $query->orderBy('id')->chunkById(500, function ($records) use (&$groups, $mode, $baseUrl, $maxItemsPerGroup) {
            foreach ($records as $record) {
                $key = $this->buildFingerprint((string) $record->title, $mode);
                if ($key === '') {
                    continue;
                }

                if (! isset($groups[$key])) {
                    $groups[$key] = [
                        'count' => 0,
                        'items' => [],
                    ];
                }

                $groups[$key]['count']++;

                if (count($groups[$key]['items']) < $maxItemsPerGroup) {
                    $groups[$key]['items'][] = [
                        'id' => $record->id,
                        'title' => $record->title,
                        'url' => $this->buildItemUrl($baseUrl, $record->pathToFile),
                    ];
                }
            }
        });

        $duplicates = [];
        foreach ($groups as $key => $data) {
            if ($data['count'] > 1) {
                $duplicates[] = [
                    'key' => $key,
                    'count' => $data['count'],
                    'items' => $data['items'],
                ];
            }
        }

        usort($duplicates, function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['key'], $b['key']);
            }

            return $b['count'] <=> $a['count'];
        });

        $total = count($duplicates);
        $groupsSlice = array_slice($duplicates, $offset, $limit);

        return response()->json([
            'groups' => $groupsSlice,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    public function shortAudios(Request $request)
    {
        $cursor = max((int) $request->query('cursor', 0), 0);
        $limit = (int) $request->query('limit', 20);
        $limit = min(max($limit, 1), 50);
        $maxDuration = (int) $request->query('maxDuration', 60);
        $maxDuration = min(max($maxDuration, 10), 600);

        $baseUrl = $this->getAudioBaseUrl();
        if ($baseUrl === '') {
            return response()->json(['message' => 'Base URL manquante.'], 422);
        }

        $ffprobe = $this->resolveFfprobePath();
        if ($ffprobe === '') {
            return response()->json(['message' => 'FFPROBE_BIN non configuré (ffprobe introuvable).'], 422);
        }

        $maxProbeBytes = (int) config('services.xassaid.max_probe_bytes', 8 * 1024 * 1024);

        $records = Audio::query()
            ->select(['id', 'title', 'pathToFile'])
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $results = [];
        $checked = 0;
        $short = 0;
        $skippedLarge = 0;
        $unknown = 0;
        $nextCursor = $cursor;

        foreach ($records as $record) {
            $checked++;
            $nextCursor = $record->id;

            if (! $record->pathToFile) {
                $unknown++;

                continue;
            }

            $url = rtrim($baseUrl, '/').'/'.ltrim($record->pathToFile, '/');
            $contentLength = $this->fetchContentLength($url);

            if ($maxProbeBytes > 0 && $contentLength !== null && $contentLength > $maxProbeBytes) {
                $skippedLarge++;

                continue;
            }

            $duration = $this->probeDuration($ffprobe, $url);
            if ($duration === null) {
                $unknown++;

                continue;
            }

            if ($duration < $maxDuration) {
                $short++;
                $results[] = [
                    'id' => $record->id,
                    'title' => $record->title,
                    'url' => $url,
                    'duration' => $duration,
                    'durationLabel' => $this->formatDuration($duration),
                ];
            }
        }

        return response()->json([
            'items' => $results,
            'checked' => $checked,
            'short' => $short,
            'skippedLarge' => $skippedLarge,
            'unknown' => $unknown,
            'nextCursor' => $nextCursor,
            'done' => $records->count() < $limit,
        ]);
    }

    /**
     * Les médias sont servis par le serveur de fichiers (XASSAID_FILES_URI,
     * ex. https://files.xassaid.com) sous /audios/ pour les MP3 et /files/
     * pour les PDF. On lit la valeur via config() pour rester compatible
     * avec le cache de configuration en production.
     */
    private function getAudioBaseUrl(): string
    {
        return $this->ensureSuffix((string) config('services.xassaid.files_uri', ''), '/audios');
    }

    private function getFileBaseUrl(): string
    {
        return $this->ensureSuffix((string) config('services.xassaid.files_uri', ''), '/files');
    }

    /**
     * @return array{ok: bool, status: ?int, reason: string}
     */
    private function probeUrl(string $url): array
    {
        $response = null;
        try {
            $response = Http::timeout(8)->connectTimeout(4)->head($url);
        } catch (\Throwable $e) {
            $response = null;
        }

        if ($response) {
            $status = $response->status();
            if ($this->isOkStatus($status)) {
                return ['ok' => true, 'status' => $status, 'reason' => 'OK'];
            }

            if (! in_array($status, [400, 403, 405, 501], true)) {
                return ['ok' => false, 'status' => $status, 'reason' => 'Réponse HTTP'];
            }
        }

        try {
            $response = Http::timeout(8)->connectTimeout(4)->withHeaders([
                'Range' => 'bytes=0-0',
            ])->get($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => null, 'reason' => 'Erreur réseau'];
        }

        $status = $response->status();
        if ($this->isOkStatus($status) || $status === 206 || $status === 416) {
            return ['ok' => true, 'status' => $status, 'reason' => 'OK'];
        }

        return ['ok' => false, 'status' => $status, 'reason' => 'Réponse HTTP'];
    }

    /**
     * Sonde un lot d'URL en parallèle.
     *
     * Audios : requête HEAD (repli sur probeUrl() si le serveur la refuse).
     * PDF : lecture des 2 derniers Ko par requête Range — un 206 prouve que
     * le fichier existe et son contenu doit se terminer par « %%EOF », sinon
     * l'upload a été interrompu et le document est illisible même s'il
     * répond 200.
     *
     * @param  array<int, string>  $urls  id => url
     * @return array<int, array{ok: bool, status: ?int, reason: string}>
     */
    private function probeMany(array $urls, bool $pdf): array
    {
        if ($urls === []) {
            return [];
        }

        // 10 connexions à la fois : au-delà, le serveur de fichiers refuse
        // une partie des requêtes.
        $responses = [];
        foreach (array_chunk($urls, 10, true) as $chunk) {
            $responses += Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($chunk, $pdf) {
                foreach ($chunk as $id => $url) {
                    $req = $pool->as((string) $id)->timeout(10)->connectTimeout(4);
                    if ($pdf) {
                        $req->withHeaders(['Range' => 'bytes=-2048'])->get($url);
                    } else {
                        $req->head($url);
                    }
                }
            });
        }

        $out = [];
        foreach ($urls as $id => $url) {
            $response = $responses[(string) $id] ?? null;
            if (! $response instanceof \Illuminate\Http\Client\Response) {
                // Seconde tentative isolée avant de conclure à une erreur réseau.
                try {
                    $response = $pdf
                        ? Http::timeout(10)->connectTimeout(4)->withHeaders(['Range' => 'bytes=-2048'])->get($url)
                        : Http::timeout(10)->connectTimeout(4)->head($url);
                } catch (\Throwable $e) {
                    $out[$id] = ['ok' => false, 'status' => null, 'reason' => 'Erreur réseau'];

                    continue;
                }
            }
            $status = $response->status();

            if ($pdf) {
                if ($status === 206) {
                    $out[$id] = str_contains($response->body(), '%%EOF')
                        ? ['ok' => true, 'status' => $status, 'reason' => 'OK']
                        : ['ok' => false, 'status' => $status, 'reason' => 'PDF tronqué (fin de fichier absente)'];
                } elseif ($status === 200) {
                    // Le serveur ignore les Range : le corps entier est là.
                    $out[$id] = str_contains($response->body(), '%%EOF')
                        ? ['ok' => true, 'status' => $status, 'reason' => 'OK']
                        : ['ok' => false, 'status' => $status, 'reason' => 'PDF tronqué (fin de fichier absente)'];
                } elseif ($status === 416) {
                    $out[$id] = ['ok' => true, 'status' => $status, 'reason' => 'OK'];
                } else {
                    $out[$id] = ['ok' => false, 'status' => $status, 'reason' => 'Réponse HTTP'];
                }

                continue;
            }

            if ($this->isOkStatus($status)) {
                $out[$id] = ['ok' => true, 'status' => $status, 'reason' => 'OK'];
            } elseif (in_array($status, [400, 403, 405, 501], true)) {
                $out[$id] = $this->probeUrl($url);
            } else {
                $out[$id] = ['ok' => false, 'status' => $status, 'reason' => 'Réponse HTTP'];
            }
        }

        return $out;
    }

    private function isOkStatus(int $status): bool
    {
        return $status >= 200 && $status < 400;
    }

    private function ensureSuffix(string $base, string $suffix): string
    {
        $base = rtrim($base, '/');
        if ($base === '') {
            return '';
        }

        $suffix = '/'.ltrim($suffix, '/');
        if (str_ends_with($base, $suffix)) {
            return $base;
        }

        return $base.$suffix;
    }

    private function buildFingerprint(string $title, string $mode): string
    {
        $normalized = $this->normalizeTitle($title);
        if ($normalized === '') {
            return '';
        }

        if ($mode === 'similar') {
            $tokens = array_filter(explode(' ', $normalized), function ($token) {
                return strlen($token) > 2;
            });

            if (count($tokens) === 0) {
                return $normalized;
            }

            sort($tokens);

            return implode(' ', $tokens);
        }

        return $normalized;
    }

    private function normalizeTitle(string $title): string
    {
        $title = trim($title);
        $title = strtolower($title);

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
            if ($converted !== false) {
                $title = $converted;
            }
        }

        $title = preg_replace('/[^a-z0-9]+/', ' ', $title);
        $title = preg_replace('/\\s+/', ' ', $title);

        return trim($title);
    }

    private function buildItemUrl(string $baseUrl, ?string $path): ?string
    {
        if (! $path || $baseUrl === '') {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function resolveFfprobePath(): string
    {
        $ffprobe = (string) config('services.xassaid.ffprobe_bin', '');
        if ($ffprobe !== '') {
            return is_file($ffprobe) ? $ffprobe : '';
        }

        $ffmpeg = (string) config('services.xassaid.ffmpeg_bin', '');
        if ($ffmpeg !== '') {
            $candidate = str_replace('ffmpeg', 'ffprobe', $ffmpeg);

            return is_file($candidate) ? $candidate : '';
        }

        if ($this->isCommandAvailable('ffprobe')) {
            return 'ffprobe';
        }

        return '';
    }

    private function probeDuration(string $ffprobe, string $url): ?float
    {
        $process = new Process([
            $ffprobe,
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $url,
        ]);

        $process->setTimeout(15);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());
        if ($output === '' || ! is_numeric($output)) {
            return null;
        }

        return (float) $output;
    }

    private function fetchContentLength(string $url): ?int
    {
        try {
            $response = Http::timeout(6)->connectTimeout(3)->head($url);
        } catch (\Throwable $e) {
            return null;
        }

        $length = $response->header('Content-Length');
        if ($length === null || $length === '') {
            $length = $response->header('content-length');
        }

        if ($length === null || $length === '' || ! is_numeric($length)) {
            return null;
        }

        return (int) $length;
    }

    private function formatDuration(float $seconds): string
    {
        $total = (int) round($seconds);
        $minutes = (int) floor($total / 60);
        $remain = $total % 60;

        return sprintf('%d:%02d', $minutes, $remain);
    }

    private function isCommandAvailable(string $command): bool
    {
        $process = new Process([$command, '-version']);
        $process->setTimeout(5);
        $process->run();

        return $process->isSuccessful();
    }
}
