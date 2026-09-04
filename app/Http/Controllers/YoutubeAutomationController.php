<?php

namespace App\Http\Controllers;

use App\Support\YoutubeAutomation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Suivi des publications YouTube : la page et un relais JSON vers le service
 * xassaid-automation (le port du service reste privé, l'auth est celle du
 * backoffice).
 */
class YoutubeAutomationController extends Controller
{
    public function __construct(private readonly YoutubeAutomation $service)
    {
    }

    public function render()
    {
        return view('pages/youtube', [
            'configured' => $this->service->configured(),
            'serviceUrl' => $this->service->baseUrl(),
        ]);
    }

    public function status(): JsonResponse
    {
        return $this->relay($this->service->get('/api/status'));
    }

    public function categories(): JsonResponse
    {
        return $this->relay($this->service->get('/api/categories'));
    }

    public function audios(Request $request): JsonResponse
    {
        return $this->relay($this->service->get('/api/audios', $request->only([
            'status', 'category', 'q', 'limit', 'offset',
        ])));
    }

    public function events(Request $request): JsonResponse
    {
        return $this->relay($this->service->get('/api/events', $request->only(['limit'])));
    }

    /** pause | resume | sync */
    public function action(string $action): JsonResponse
    {
        return $this->relay($this->service->post('/api/' . $action));
    }

    /** retry | skip | priority */
    public function audioAction(int $audio, string $action): JsonResponse
    {
        return $this->relay($this->service->post("/api/audios/{$audio}/{$action}"));
    }

    private function relay(array $result): JsonResponse
    {
        return response()->json($result['data'], $result['ok'] ? 200 : $result['status']);
    }
}
