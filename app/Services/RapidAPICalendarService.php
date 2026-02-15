<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RapidAPICalendarService
{
    private string $apiKey;
    private string $apiHost;
    private string $baseUrl;

    // Mapeo de currencies a country codes de RapidAPI
    private array $countryMap = [
        'USD' => 'US',
        'EUR' => 'DE',
        'GBP' => 'GB',
        'JPY' => 'JP',
        'AUD' => 'AU',
        'CAD' => 'CA',
        'CHF' => 'CH',
        'NZD' => 'NZ',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.rapidapi.key');
        $this->apiHost = config('services.rapidapi.host');
        $this->baseUrl = config('services.rapidapi.base_url');
    }

    /**
     * Fetch eventos desde RapidAPI Ultimate Economic Calendar
     * 
     * @param Carbon $from
     * @param Carbon $to
     * @param array $currencies Ejemplo: ['USD', 'EUR', 'GBP']
     * @return Collection
     * @throws \Exception
     */
    public function fetchEvents(Carbon $from, Carbon $to, array $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD']): Collection
    {
        if (!$this->apiKey) {
            throw new \Exception('RAPIDAPI_KEY no configurada en .env');
        }

        // Convertir currencies a country codes (USD -> US, EUR -> DE, etc)
        $countries = collect($currencies)
            ->map(fn($currency) => $this->countryMap[$currency] ?? null)
            ->filter()
            ->unique()
            ->join(',');

        if (empty($countries)) {
            throw new \Exception('No se pudieron mapear currencies a country codes');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'x-rapidapi-host' => $this->apiHost,
                'x-rapidapi-key' => $this->apiKey,
            ])
            ->get("{$this->baseUrl}/economic-events/tradingview", [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'countries' => $countries, // "US,DE,GB,JP,AU,CA,CH,NZ"
            ]);

        if ($response->failed()) {
            throw new \Exception(
                "RapidAPI HTTP {$response->status()}: {$response->body()}"
            );
        }

        $data = $response->json();

        // Detectar estructura de respuesta (puede variar)
        $events = $this->extractEventsFromResponse($data);

        if (!is_array($events)) {
            throw new \Exception('Respuesta inválida: no se encontró array de eventos');
        }

        return collect($events)->map(function ($event) {
            return $this->transformEvent($event);
        });
    }

    /**
     * Extrae array de eventos según estructura de respuesta
     */
    private function extractEventsFromResponse(array $data): array
    {
        // Caso 1: Array directo de eventos
        if (isset($data[0]) && is_array($data[0])) {
            return $data;
        }

        // Caso 2: Wrapper con "data"
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        // Caso 3: Wrapper con "events"
        if (isset($data['events']) && is_array($data['events'])) {
            return $data['events'];
        }

        // Caso 4: Wrapper con "result"
        if (isset($data['result']) && is_array($data['result'])) {
            return $data['result'];
        }

        // Si no matchea ninguno, retornar data tal cual
        return $data;
    }

    /**
     * Transforma evento de RapidAPI a formato TradeForge
     */
    private function transformEvent(array $event): array
    {
        return [
            'raw' => $event, // Guardamos raw para debugging

            // Country/Currency
            'country_code' => $event['country'] ?? $event['countryCode'] ?? $event['country_code'] ?? null,

            // DateTime
            'datetime' => $event['date'] ?? $event['dateTime'] ?? $event['timestamp'] ?? $event['time'] ?? null,

            // Event name
            'event_name' => $event['title'] ?? $event['event'] ?? $event['name'] ?? $event['indicator'] ?? null,

            // Impact/Importance
            'impact' => $event['impact'] ?? $event['importance'] ?? $event['volatility'] ?? $event['priority'] ?? null,

            // Data values
            'actual' => $event['actual'] ?? null,
            'previous' => $event['previous'] ?? $event['prev'] ?? $event['prior'] ?? null,
            'forecast' => $event['forecast'] ?? $event['consensus'] ?? $event['expected'] ?? $event['estimate'] ?? null,

            // Metadata
            'unit' => $event['unit'] ?? null,
            'source' => $event['source'] ?? 'tradingview',
        ];
    }

    /**
     * Mapea código de país a divisa
     */
    public function mapCountryToCurrency(string $countryCode): ?string
    {
        $map = [
            'US' => 'USD',
            'DE' => 'EUR',
            'EU' => 'EUR',
            'GB' => 'GBP',
            'UK' => 'GBP',
            'JP' => 'JPY',
            'AU' => 'AUD',
            'CA' => 'CAD',
            'CH' => 'CHF',
            'NZ' => 'NZD',
        ];

        return $map[strtoupper($countryCode)] ?? null;
    }

    /**
     * Mapea impact/importance de RapidAPI a enum de TradeForge
     */
    public function mapImpactToEnum(?string $impact): string
    {
        if (!$impact) {
            return 'low';
        }

        $normalized = strtolower(trim($impact));

        // RapidAPI puede usar: "High", "Medium", "Low" o "3", "2", "1"
        return match ($normalized) {
            'high', '3', 'important', 'critical' => 'high',
            'medium', '2', 'moderate' => 'medium',
            default => 'low',
        };
    }
}
