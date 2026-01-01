<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
}
