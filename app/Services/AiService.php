<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private const CACHE_TTL_DAYS = 7;

    public function isConfigured(): bool
    {
        return !empty(config('services.groq.key'));
    }

    /**
     * Ejecuta una completion contra Groq.
     *
     * Si se pasa $cacheKey, una misma entrada (clave + hash del prompt) reutiliza
     * la respuesta cacheada durante 7 días sin llamar a la API. El componente debe
     * comprobar $response->fromCache para no consumir crédito en ese caso.
     */
    public function complete(
        string $prompt,
        float $temperature = 0.4,
        int $maxTokens = 1024,
        ?string $cacheKey = null,
    ): AiResponse {
        $fullCacheKey = $cacheKey !== null ? $this->buildCacheKey($cacheKey, $prompt) : null;

        if ($fullCacheKey !== null && ($cached = Cache::get($fullCacheKey)) !== null) {
            return AiResponse::fromCache($cached);
        }

        try {
            $response = Http::when(app()->isLocal(), fn (PendingRequest $http) => $http->withoutVerifying())
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(3, 3000, function (\Throwable $exception) {
                    if ($exception instanceof RequestException) {
                        return $exception->response->status() === 429 || $exception->response->serverError();
                    }

                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->withToken(config('services.groq.key'))
                ->post(config('services.groq.url'), [
                    'model' => config('services.groq.model'),
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::error('Groq connection error: ' . $e->getMessage());

            return AiResponse::connectionError($e->getMessage());
        }

        if (!$response->successful()) {
            $status = $response->status();
            $message = $response->json('error.message') ?? 'Error desconocido';
            Log::warning("Groq error {$status}: {$message}");

            return AiResponse::httpError($status, $message);
        }

        $finishReason = $response->json('choices.0.finish_reason');
        $content = $response->json('choices.0.message.content');

        if ($finishReason === 'length') {
            Log::warning('Groq: respuesta cortada por max_tokens.');

            return AiResponse::truncated();
        }

        if (!is_string($content) || trim($content) === '') {
            Log::warning('Groq: respuesta sin contenido.', ['finish_reason' => $finishReason]);

            return AiResponse::httpError($response->status(), 'Respuesta vacía');
        }

        $content = trim($content);

        if ($fullCacheKey !== null) {
            Cache::put($fullCacheKey, $content, now()->addDays(self::CACHE_TTL_DAYS));
        }

        return AiResponse::success($content);
    }

    private function buildCacheKey(string $key, string $prompt): string
    {
        return 'ai:' . $key . ':' . md5($prompt);
    }
}
