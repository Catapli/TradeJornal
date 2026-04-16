<?php

namespace App\Console\Commands;

use App\Models\EconomicEvent;
use App\Services\JBlankedCalendarService;
use App\Services\RapidAPICalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncEconomicCalendar extends Command
{
    protected $signature = 'calendar:sync
                                {--range=week    : Rango (today, week, month, range)}
                                {--from=         : Fecha inicio YYYY-MM-DD (solo con --range=range)}
                                {--to=           : Fecha fin   YYYY-MM-DD (solo con --range=range)}
                                {--source=merged : Fuente (mql5, fxstreet, merged)}
                                {--dry-run       : Muestra eventos sin guardar en BBDD}';

    protected $description = 'Sincroniza el calendario económico desde JBlanked (MQL5 + FxStreet)';

    public function __construct(private readonly RapidAPICalendarService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $startTime = microtime(true);
        $range     = $this->option('range');
        $source    = $this->option('source');
        $from      = $this->option('from');
        $to        = $this->option('to');
        $isDryRun  = $this->option('dry-run');

        $this->info("🔄 Sincronizando calendario económico — Fuente: {$source} | Rango: {$range}");

        if ($range === 'range' && (! $from || ! $to)) {
            $this->error('❌ --from y --to son obligatorios con --range=range');
            return self::FAILURE;
        }

        try {
            // 1. Fetch eventos según fuente
            $events = match ($source) {
                'mql5'     => $this->service->fetchFromSource('mql5', $range, $from, $to),
                'fxstreet' => $this->service->fetchFromSource('fxstreet', $range, $from, $to),
                default    => $this->service->fetchMerged($range, $from, $to),
            };

            $this->info("📦 Eventos recibidos: {$events->count()}");

            if ($events->isEmpty()) {
                $this->warn('⚠️  No se recibieron eventos. Verifica la API key.');
                return self::SUCCESS;
            }

            // 2. Dry run — solo mostrar tabla sin guardar
            if ($isDryRun) {
                $this->table(
                    ['Date', 'Time', 'Currency', 'Impact', 'Event', 'Actual', 'Forecast', 'Previous'],
                    $events->map(fn($e) => [
                        $e['date'],
                        $e['time'],
                        $e['currency'],
                        $e['impact'],
                        substr($e['event'], 0, 40),
                        $e['actual'] ?? '-',
                        $e['forecast'] ?? '-',
                        $e['previous'] ?? '-',
                    ])->toArray()
                );
                $this->info('🔍 Dry run completado — nada guardado.');
                return self::SUCCESS;
            }

            // 3. Persistir con upsert
            $stats = $this->persist($events->toArray());

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ Completado en {$duration}ms:");
            $this->info("   • Insertados/Actualizados: {$stats['upserted']}");
            $this->info("   • Skipped (inválidos):     {$stats['skipped']}");

            Log::info('calendar:sync completado', [
                ...$stats,
                'source'      => $source,
                'range'       => $range,
                'duration_ms' => $duration,
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            Log::error('calendar:sync falló', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return self::FAILURE;
        }
    }

    // ─── Persistencia ─────────────────────────────────────────────────────────

    private function persist(array $events): array
    {
        $valid   = [];
        $skipped = 0;

        foreach ($events as $event) {
            if (empty($event['date']) || empty($event['event'])) {
                $skipped++;
                continue;
            }
            $valid[] = [
                ...$event,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($valid)) {
            return ['upserted' => 0, 'skipped' => $skipped];
        }

        // Upsert respetando unique_event_idx.
        // Para actual/forecast/previous: COALESCE protege datos MQL5 si FxStreet
        // llegara a upsertarse por separado en el futuro.
        EconomicEvent::upsert(
            $valid,
            uniqueBy: ['date', 'time', 'currency', 'event'],
            update: ['impact', 'actual', 'forecast', 'previous', 'updated_at'],
        );

        return ['upserted' => count($valid), 'skipped' => $skipped];
    }
}
