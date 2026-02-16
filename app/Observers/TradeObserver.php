<?php

namespace App\Observers;

use App\Models\Trade;
use App\Jobs\RecalculateStrategyStatsJob;
use App\Services\PropFirmService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TradeObserver
{
    public function created(Trade $trade): void
    {
        $this->updateStrategy($trade);
        (new PropFirmService())->validate($trade);
    }

    public function updated(Trade $trade): void
    {
        if ($trade->wasChanged(['strategy_id', 'pnl'])) {
            $this->updateStrategy($trade);

            if ($trade->wasChanged('strategy_id') && $trade->getOriginal('strategy_id')) {
                $oldStrategy = \App\Models\Strategy::find($trade->getOriginal('strategy_id'));
                if ($oldStrategy) {
                    RecalculateStrategyStatsJob::dispatch($oldStrategy);
                }
            }
        }

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
        Log::info("Actualizando Estrategia");
        if (!$trade->strategy_id) return;

        $strategy = $trade->strategy;
        if (!$strategy) return;

        RecalculateStrategyStatsJob::dispatch($strategy);
    }
}
