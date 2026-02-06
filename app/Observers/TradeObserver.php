<?php

namespace App\Observers;

use App\Models\Trade;
use App\Jobs\RecalculateStrategyStatsJob;
use App\Services\PropFirmService;

class TradeObserver
{
    public function created(Trade $trade): void
    {
        $this->updateStrategy($trade);

        // 2. NUEVA LÓGICA: Validar Reglas Prop Firm
        // (Detectar si entró en noticia prohibida al momento de crearse)
        (new PropFirmService())->validate($trade);
    }

    public function updated(Trade $trade): void
    {
        // Solo recalcular si cambió strategy_id o pnl
        if ($trade->wasChanged(['strategy_id', 'pnl'])) {
            $this->updateStrategy($trade);

            // Si cambió de estrategia, actualizar la anterior también
            if ($trade->wasChanged('strategy_id') && $trade->getOriginal('strategy_id')) {
                $oldStrategy = \App\Models\Strategy::find($trade->getOriginal('strategy_id'));
                if ($oldStrategy) {
                    RecalculateStrategyStatsJob::dispatch($oldStrategy);
                }
            }
        }

        // 2. NUEVA LÓGICA: Validar Reglas Prop Firm
        // Solo validamos si cambiaron tiempos (cierre de operación) o precios
        // para evitar ejecuciones innecesarias si solo cambiaste una nota.
        if ($trade->wasChanged(['exit_time', 'entry_time', 'pnl'])) {
            (new PropFirmService())->validate($trade);
        }
    }

    public function deleted(Trade $trade): void
    {
        $this->updateStrategy($trade);
    }

    private function updateStrategy(Trade $trade): void
    {
        if (!$trade->strategy_id) return;

        $strategy = $trade->strategy;
        if (!$strategy) return;

        // Dispatch async (no bloquea al usuario)
        RecalculateStrategyStatsJob::dispatch($strategy);
    }
}
