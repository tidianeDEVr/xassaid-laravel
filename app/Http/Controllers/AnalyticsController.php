<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class AnalyticsController extends Controller
{
    public function render()
    {
        $audioBase = $this->getAudioBaseUrl();
        $fileBase = $this->getFileBaseUrl();

        return view('pages.analytics', [
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

        foreach ($records as $record) {
            $checked++;
            $nextCursor = $record->id;

            if (!$record->pathToFile) {
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

            $url = rtrim($baseUrl, '/') . '/' . ltrim($record->pathToFile, '/');
            $probe = $this->probeUrl($url);

            if (!$probe['ok']) {
                $missing++;
                $results[] = [
                    'id' => $record->id,
                    'title' => $record->title,
                    'url' => $url,
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

        if (!in_array($mode, ['exact', 'similar'], true)) {
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

                if (!isset($groups[$key])) {
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

        $maxProbeBytes = (int) env('ANALYTICS_MAX_PROBE_BYTES', 8 * 1024 * 1024);

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

            if (!$record->pathToFile) {
                $unknown++;
                continue;
            }

            $url = rtrim($baseUrl, '/') . '/' . ltrim($record->pathToFile, '/');
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

    private function getAudioBaseUrl(): string
    {
        $base = (string) env('XASSAID_AUDIO_PUBLIC_URL', '');
        if ($base === '') {
            $base = (string) env('XASSAID_FILES_PUBLIC_URL', env('XASSAID_FILES_URI', ''));
            $base = $this->ensureSuffix($base, '/audios');
        }

        return rtrim($base, '/');
    }

    private function getFileBaseUrl(): string
    {
        $base = (string) env('XASSAID_FILE_PUBLIC_URL', '');
        if ($base === '') {
            $base = (string) env('XASSAID_FILES_PUBLIC_URL', env('XASSAID_FILES_URI', ''));
            $base = $this->ensureSuffix($base, '/files');
        }

        return rtrim($base, '/');
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

            if (!in_array($status, [400, 403, 405, 501], true)) {
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

        $suffix = '/' . ltrim($suffix, '/');
        if (str_ends_with($base, $suffix)) {
            return $base;
        }

        return $base . $suffix;
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
        if (!$path || $baseUrl === '') {
            return null;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function resolveFfprobePath(): string
    {
        $ffprobe = (string) env('FFPROBE_BIN', '');
        if ($ffprobe !== '') {
            return is_file($ffprobe) ? $ffprobe : '';
        }

        $ffmpeg = (string) env('FFMPEG_BIN', '');
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

        if (!$process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());
        if ($output === '' || !is_numeric($output)) {
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

        if ($length === null || $length === '' || !is_numeric($length)) {
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
