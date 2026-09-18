<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
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
     *
     * $system va como mensaje de rol `system`: los modelos pequeños respetan bastante
     * mejor las reglas duras (formato, "no recalcules") ahí que dentro del prompt.
     */
    public function complete(
        string $prompt,
        float $temperature = 0.4,
        int $maxTokens = 1024,
        ?string $cacheKey = null,
        ?string $system = null,
    ): AiResponse {
        // Antes lo comprobaba cada componente por su cuenta y tres se lo saltaban:
        // sin clave el usuario veía un 401 genérico en lugar del aviso real.
        if (!$this->isConfigured()) {
            return AiResponse::notConfigured();
        }

        $system ??= __('ai.system');
        $fullCacheKey = $cacheKey !== null ? $this->buildCacheKey($cacheKey, $system . $prompt) : null;
        $effort = config('services.groq.reasoning_effort');

        if ($fullCacheKey !== null && ($cached = Cache::get($fullCacheKey)) !== null) {
            return AiResponse::fromCache($cached);
        }

        try {
            $response = Http::when(app()->isLocal(), fn (PendingRequest $http) => $http->withoutVerifying())
                ->connectTimeout(5)
                ->timeout(20)
                // 2 intentos, no 3: con 3x20s + esperas el peor caso pasaba de 60s y
                // max_execution_time mataba el proceso antes de agotar los reintentos.
                ->retry(2, 1500, function (\Throwable $exception) {
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
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    // Los modelos de razonamiento gastan max_tokens pensando antes de responder.
                    // Solo se envia si esta configurado: los modelos sin razonamiento lo rechazan.
                    ...($effort ? ['reasoning_effort' => $effort] : []),
                ]);
        } catch (ConnectionException $e) {
            Log::error('Groq connection error: ' . $e->getMessage());

            return AiResponse::connectionError($e->getMessage());
        }

        $this->logUsage($cacheKey, $response->json('usage'));

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

    /**
     * Traza el consumo de tokens: sin esto no hay forma de saber cuánto cuesta
     * cada tipo de análisis ni qué usuario dispara el gasto.
     */
    private function logUsage(?string $cacheKey, mixed $usage): void
    {
        if (!is_array($usage)) {
            return;
        }

        Log::info('Groq usage', [
            'kind' => $cacheKey === null ? 'uncached' : strtok($cacheKey, ':'),
            'user_id' => Auth::id(),
            'model' => config('services.groq.model'),
            'prompt' => $usage['prompt_tokens'] ?? null,
            'completion' => $usage['completion_tokens'] ?? null,
            'total' => $usage['total_tokens'] ?? null,
        ]);
    }
}
