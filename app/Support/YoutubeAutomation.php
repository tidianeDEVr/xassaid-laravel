<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Client HTTP du service « xassaid-automation » (génération et publication
 * des vidéos YouTube). Le service tourne à côté du backoffice ; le
 * backoffice ne fait que relayer son API JSON (voir YoutubeAutomationController).
 */
class YoutubeAutomation
{
    public function configured(): bool
    {
        return (bool) config('services.youtube_automation.url');
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.youtube_automation.url'), '/');
    }

    /** @return array{ok: bool, status: int, data: mixed} */
    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query);
    }

    /** @return array{ok: bool, status: int, data: mixed} */
    public function post(string $path): array
    {
        return $this->send('post', $path);
    }

    private function send(string $method, string $path, array $query = []): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'status' => 503, 'data' => [
                'error' => 'Service de publication non configuré (YOUTUBE_AUTOMATION_URL).',
            ]];
        }
        try {
            $response = $method === 'get'
                ? $this->client()->get($path, $query)
                : $this->client()->post($path);
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 502, 'data' => [
                'error' => 'Service de publication injoignable : ' . $e->getMessage(),
            ]];
        }
        $data = $response->json();
        if ($data === null) {
            $data = ['error' => 'Réponse invalide du service (' . $response->status() . ')'];
        }
        return ['ok' => $response->successful(), 'status' => $response->status(), 'data' => $data];
    }

    private function client(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl())->acceptJson()->timeout(15)->connectTimeout(5);
        $token = (string) config('services.youtube_automation.token');
        return $token !== '' ? $client->withToken($token) : $client;
    }
}
