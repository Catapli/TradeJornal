<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RapidAPICalendarService
{
    private const DATE_FORMAT = 'Y.m.d H:i:s';
    private const DATE_FORMAT_SHORT = 'Y.m.d H:i'; // Algunos eventos vienen sin segundos

    private const IMPACT_MAP = [
        'High'   => 'high',
        'Medium' => 'medium',
        'Low'    => 'low',
        'None'   => 'low',
    ];

    private const SOURCES = ['mql5', 'fxstreet'];

    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.jblanked.api_key');
        $this->baseUrl = config('services.jblanked.base_url');
    }

    // ─── API Pública ──────────────────────────────────────────────────────────

    /**
     * Fetch desde UNA fuente y rango concreto.
     * Devuelve Collection de arrays ya normalizados al schema de TradeForge.
     */
    public function fetchFromSource(string $source, string $range, ?string $from = null, ?string $to = null): Collection
    {
        $url      = $this->buildUrl($source, $range, $from, $to);
        $raw      = $this->get($url);

        return collect($raw)
            ->filter(fn(array $event) => $this->isValid($event))
            ->map(fn(array $event)    => $this->normalize($event))
            ->values();
    }

    /**
     * Fetch desde MQL5 y FxStreet, merge inteligente:
     * MQL5 es fuente primaria. FxStreet solo rellena campos null.
     * Devuelve Collection ordenada por date+time.
     */
    public function fetchMerged(string $range, ?string $from = null, ?string $to = null): Collection
    {
        // 1. MQL5 primero — fuente primaria
        $mql5Events = $this->fetchFromSource('mql5', $range, $from, $to);

        // 2. Pequeña pausa para respetar rate limit (1 req/s) [web:30]
        usleep(1_100_000); // 1.1 segundos

        // 3. FxStreet — fuente secundaria
        $fxstreetEvents = $this->fetchFromSource('fxstreet', $range, $from, $to);

        // 4. Merge: indexamos MQL5 por clave única y enriquecemos con FxStreet
        $merged = $mql5Events->keyBy(fn(array $e) => $this->uniqueKey($e));

        foreach ($fxstreetEvents as $fxEvent) {
            $key = $this->uniqueKey($fxEvent);

            if (! $merged->has($key)) {
                // Evento exclusivo de FxStreet — añadir directamente
                $merged->put($key, $fxEvent);
                continue;
            }

            // Evento en ambas fuentes — FxStreet solo rellena nulls
            $existing = $merged->get($key);
            $merged->put($key, $this->mergeEvent($existing, $fxEvent));
        }

        return $merged
            ->values()
            ->sortBy(fn(array $e) => $e['date'] . ' ' . $e['time'])
            ->values();
    }

    // ─── Helpers Internos ─────────────────────────────────────────────────────

    private function buildUrl(string $source, string $range, ?string $from, ?string $to): string
    {
        $base = "{$this->baseUrl}/{$source}/calendar";

        return match ($range) {
            'today' => "{$base}/today/",
            'week'  => "{$base}/week/",
            'month' => "{$base}/month/",
            'range' => "{$base}/range/?from={$from}&to={$to}",
            default => "{$base}/week/",
        };
    }

    private function get(string $url): array
    {
        if (! $this->apiKey) {
            throw new \RuntimeException('JBLANKED_API_KEY no configurada en .env');
        }

        $response = Http::withHeaders([
            'Authorization' => "Api-Key {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])
            ->timeout(20)
            ->retry(3, 1500)
            ->get($url);

        if ($response->failed()) {
            Log::error('JBlanked API error', [
                'status' => $response->status(),
                'url'    => $url,
                'body'   => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();

        // JBlanked devuelve array plano directamente [web:30]
        return is_array($data) ? $data : [];
    }

    private function isValid(array $event): bool
    {
        return isset($event['Name'], $event['Currency'], $event['Date'], $event['Impact'])
            && in_array($event['Impact'], ['High', 'Medium', 'Low', 'None'], true)
            && in_array(strtoupper($event['Currency']), ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD'], true);
    }

    private function normalize(array $event): array
    {
        $parsed = $this->parseDate($event['Date']);

        return [
            'date'     => $parsed->toDateString(),
            'time'     => $parsed->toTimeString(),
            'currency' => strtoupper($event['Currency']),
            'event'    => $this->sanitizeName($event['Name']),
            'impact'   => self::IMPACT_MAP[$event['Impact']] ?? 'low',
            'actual'   => $this->castValue($event['Actual']   ?? null),
            'forecast' => $this->castValue($event['Forecast'] ?? null),
            'previous' => $this->castValue($event['Previous'] ?? null),
        ];
    }

    /**
     * MQL5 gana en datos ya existentes.
     * FxStreet rellena solo campos null del evento base.
     */
    private function mergeEvent(array $primary, array $secondary): array
    {
        return [
            ...$primary,
            'actual'   => $primary['actual']   ?? $secondary['actual'],
            'forecast' => $primary['forecast'] ?? $secondary['forecast'],
            'previous' => $primary['previous'] ?? $secondary['previous'],
        ];
    }

    private function uniqueKey(array $event): string
    {
        // Normalizamos el nombre para evitar diferencias de espacios entre fuentes
        $name = strtolower(preg_replace('/\s+/', ' ', trim($event['event'])));
        return "{$event['date']}|{$event['time']}|{$event['currency']}|{$name}";
    }

    private function parseDate(string $date): Carbon
    {
        // Formato principal: "2024.02.08 15:30:00" [web:30]
        // Formato corto: "2024.02.08 15:30" (sin segundos)
        try {
            return Carbon::createFromFormat(self::DATE_FORMAT, $date)
                ?? Carbon::createFromFormat(self::DATE_FORMAT_SHORT, $date);
        } catch (\Exception) {
            return Carbon::parse($date); // Fallback a parse genérico
        }
    }

    private function sanitizeName(?string $name): string
    {
        if (! $name) return 'Unknown Event';
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * JBlanked devuelve 0.0 cuando no hay dato, no null.
     * Guardamos null si el valor es 0.0 para no contaminar la BBDD [web:30].
     */
    private function castValue(mixed $value): ?string
    {
        if (is_null($value) || $value === '' || $value === 0.0 || $value === 0) {
            return null;
        }

        return (string) $value;
    }
}
