<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EconomicEvent;
use App\Services\RapidAPICalendarService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncEconomicCalendar extends Command
{
    protected $signature = 'calendar:sync {--days=30 : Días hacia el futuro a sincronizar}';
    protected $description = 'Sincroniza el calendario económico desde RapidAPI (Ultimate Economic Calendar)';

    private array $majors = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD'];
    private RapidAPICalendarService $calendarService;

    public function __construct(RapidAPICalendarService $calendarService)
    {
        parent::__construct();
        $this->calendarService = $calendarService;
    }

    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('🔄 Iniciando sincronización con RapidAPI Ultimate Economic Calendar...');

        try {
            $days = (int) $this->option('days');
            $from = now()->startOfDay();
            $to = now()->addDays($days)->endOfDay();

            $this->info("📅 Rango: {$from->format('Y-m-d')} → {$to->format('Y-m-d')} ({$days} días)");
            $this->info("🌍 Currencies: " . implode(', ', $this->majors));

            // 🔥 Fetch eventos vía Service
            $events = $this->calendarService->fetchEvents($from, $to, $this->majors);
            $this->info("📦 Recibidos: {$events->count()} eventos de RapidAPI");

            if ($events->isEmpty()) {
                $this->warn('⚠️  No se recibieron eventos. Verifica la API key y los parámetros.');
                return Command::SUCCESS;
            }

            // 🔥 Procesar y guardar
            $stats = $this->processAndSaveEvents($events);

            // 🔥 Métricas de performance
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ Sincronización completada:");
            $this->info("   • Nuevos: {$stats['created']}");
            $this->info("   • Actualizados: {$stats['updated']}");
            $this->info("   • Omitidos: {$stats['skipped']}");
            $this->info("   • Duración: {$duration}ms");

            Log::info("Cron Calendario RapidAPI ejecutado", array_merge($stats, [
                'duration_ms' => $duration,
                'avg_per_event_ms' => $events->count() > 0 ? round($duration / $events->count(), 2) : 0,
            ]));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Cron Calendario RapidAPI falló', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Procesa y guarda eventos (incremental con updateOrCreate)
     */
    private function processAndSaveEvents($events): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $bar = $this->output->createProgressBar($events->count());
        $bar->start();

        foreach ($events as $item) {
            try {
                // 1️⃣ Extraer y validar divisa
                $currency = $this->calendarService->mapCountryToCurrency(
                    $item['country_code'] ?? ''
                );

                if (!$currency || !in_array($currency, $this->majors)) {
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                // 2️⃣ Parsear fecha/hora
                $datetime = $this->parseDateTime($item['datetime']);
                if (!$datetime) {
                    Log::warning('Fecha inválida', ['item' => $item]);
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                // 3️⃣ Preparar datos según schema de TradeForge
                $eventData = [
                    'date' => $datetime->format('Y-m-d'),
                    'time' => $datetime->format('H:i:s'),
                    'currency' => $currency,
                    'event' => $this->sanitizeEventName($item['event_name']),
                ];

                $updateData = [
                    'impact' => $this->calendarService->mapImpactToEnum($item['impact']),
                    'forecast' => $this->sanitizeNumeric($item['forecast']),
                    'previous' => $this->sanitizeNumeric($item['previous']),
                    'actual' => $this->sanitizeNumeric($item['actual']),
                ];

                // 4️⃣ updateOrCreate (respeta unique index)
                $event = EconomicEvent::updateOrCreate($eventData, $updateData);

                if ($event->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            } catch (\Exception $e) {
                Log::warning('Error procesando evento individual', [
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
                $stats['skipped']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        return $stats;
    }

    /**
     * Parsea datetime (múltiples formatos soportados)
     */
    private function parseDateTime(?string $datetime): ?Carbon
    {
        if (!$datetime) {
            return null;
        }

        try {
            // Si es timestamp numérico
            if (is_numeric($datetime)) {
                return Carbon::createFromTimestamp((int) $datetime);
            }

            // Si es string ISO o formato común
            return Carbon::parse($datetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitiza nombre del evento
     */
    private function sanitizeEventName(?string $name): string
    {
        if (!$name) {
            return 'Unknown Event';
        }

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Sanitiza valores numéricos
     */
    private function sanitizeNumeric(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Quitar símbolos: %, K, M, B, commas, espacios
        $cleaned = preg_replace('/[%KMB,\s]/', '', $value);

        // Permitir negativos y decimales
        $cleaned = preg_replace('/[^0-9.\-]/', '', $cleaned);

        return is_numeric($cleaned) ? $cleaned : null;
    }
}
