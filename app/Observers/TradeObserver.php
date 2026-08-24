<?php

namespace App\Observers;

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Actions\Accounts\GenerateBalanceChartData;
use App\Jobs\RecalculateStrategyStatsJob;
use App\Models\Strategy;
use App\Models\Trade;
use Illuminate\Support\Facades\Log;

class TradeObserver
{
    /**
     * Columnas de las que dependen las estadísticas y el gráfico de la cuenta.
     * Tocar cualquiera de ellas invalida la caché de 5 minutos de ambos.
     */
    private const STATS_COLUMNS = [
        'account_id',
        'trade_asset_id',
        'pnl',
        'entry_time',
        'exit_time',
        'duration_minutes',
    ];

    public function created(Trade $trade): void
    {
        $this->updateStrategy($trade);
        $this->clearAccountCache($trade->account_id);
    }

    public function updated(Trade $trade): void
    {
        if ($trade->wasChanged(['strategy_id', 'pnl'])) {
            $this->updateStrategy($trade);

            if ($trade->wasChanged('strategy_id') && $trade->getOriginal('strategy_id')) {
                $oldStrategy = Strategy::find($trade->getOriginal('strategy_id'));
                if ($oldStrategy) {
                    RecalculateStrategyStatsJob::dispatch($oldStrategy);
                }
            }
        }

        if ($trade->wasChanged(self::STATS_COLUMNS)) {
            $this->clearAccountCache($trade->account_id);

            // Si el trade se ha movido de cuenta, la de origen también queda desfasada.
            if ($trade->wasChanged('account_id')) {
                $this->clearAccountCache($trade->getOriginal('account_id'));
            }
        }
    }

    public function deleted(Trade $trade): void
    {
        $this->updateStrategy($trade);
        $this->clearAccountCache($trade->account_id);
    }

    /**
     * Encola el recálculo de la estrategia a la que pertenece el trade.
     *
     * Se resuelve por id en vez de con `$trade->strategy`: la relación pudo cargarse
     * antes del cambio (el hook `created` ya la toca), y entonces al mover un trade
     * de estrategia se recalculaba dos veces la vieja y nunca la nueva.
     */
    private function updateStrategy(Trade $trade): void
    {
        if (!$trade->strategy_id) return;

        $strategy = Strategy::find($trade->strategy_id);
        if (!$strategy) return;

        RecalculateStrategyStatsJob::dispatch($strategy);
    }

    /**
     * Invalida las estadísticas y el gráfico de balance de la cuenta.
     *
     * Sin esto, un sync del .exe podía dejar hasta 5 minutos de datos viejos en el
     * dashboard: la caché solo se limpiaba a mano al editar la cuenta.
     */
    private function clearAccountCache(?int $accountId): void
    {
        if (!$accountId) return;

        try {
            CalculateAccountStatistics::clearCache($accountId);
            GenerateBalanceChartData::clearCache($accountId);
        } catch (\Throwable $e) {
            // Nunca romper el guardado de un trade por un fallo del almacén de caché.
            Log::error("Error al invalidar la caché de la cuenta {$accountId}: " . $e->getMessage());
        }
    }
}
