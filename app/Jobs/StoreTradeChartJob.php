<?php

namespace App\Jobs;

use App\Models\Trade;
use App\Services\StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sube a R2 el JSON de velas de un trade.
 *
 * Antes esto se hacía dentro del bucle de `Mt5SyncController::sync()`: una llamada de
 * red a Cloudflare por trade, en serie y dentro del request. Con una sincronización
 * inicial de cientos de trades eso son cientos de round trips seguidos — el candidato
 * número uno a que el `.exe` se coma un timeout y reintente el lote entero.
 */
class StoreTradeChartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $tradeId,
        public int $userId,
        public string $ticket,
        public array $chartData,
    ) {}

    public function handle(StorageService $storage): void
    {
        $trade = Trade::find($this->tradeId);

        // El trade pudo borrarse entre el sync y la ejecución del job (p. ej. un
        // /mt5-reset). No es un error: simplemente ya no hay dónde colgar el gráfico.
        if (!$trade) {
            return;
        }

        $path = $storage->tradeChartPath($this->userId, $this->ticket);

        $trade->update([
            'chart_data_path' => $storage->putJson($path, $this->chartData),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("No se pudo subir el gráfico del trade {$this->tradeId}: {$e->getMessage()}");
    }
}
