<?php

namespace App\Http\Middleware;

use App\Services\Ai\AiClient;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'csrf_token' => csrf_token(),
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
            'ai' => $user ? $this->aiStatus() : null,
            'notifications' => [
                'unread_count' => $user?->unreadNotifications()->count() ?? 0,
                'recent' => $user
                    ? $user->notifications()
                        ->latest()
                        ->limit(8)
                        ->get()
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'type' => class_basename($notification->type),
                            'data' => $notification->data,
                            'read_at' => $notification->read_at?->toIso8601String(),
                            'created_at' => $notification->created_at?->toIso8601String(),
                        ])
                        ->values()
                        ->all()
                    : [],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function aiStatus(): array
    {
        try {
            return app(AiClient::class)->health(useCache: true);
        } catch (\Throwable) {
            return [
                'enabled' => (bool) config('ai.enabled'),
                'provider' => (string) config('ai.provider'),
                'reachable' => false,
                'mode' => 'heuristic',
                'chat_model' => (string) config('ai.model'),
                'embedding_model' => (string) config('ai.embedding_model'),
                'chat_model_ready' => null,
                'embedding_model_ready' => null,
                'message' => 'Statut IA indisponible.',
                'hint' => 'php artisan ai:health --fresh',
                'latency_ms' => null,
            ];
        }
    }
}
