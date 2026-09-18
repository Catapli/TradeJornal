<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resultado cerrado por día de una cuenta (Fase 6 · P3).
 *
 * ⚠️ **Aparcado junto con `SimulateChallenge`, su único consumidor.** El motivo
 * está escrito allí: se retiró la pantalla, no el cálculo.
 *
 * Se agrupa por `exit_time` y no por `entry_time`: el PnL se realiza al cerrar,
 * que es el mismo criterio que usan el drawdown diario y los días rentables de
 * `Account::getObjectivesProgressAttribute()`. Contar por entrada haría que la
 * simulación y el semáforo de la cuenta dijeran cosas distintas del mismo día.
 *
 * Aquí solo hay **resultado cerrado**. El equity intradía no se guarda en ningún
 * sitio: `account_daily_metrics` existe desde enero, está vacía y nadie la
 * escribe. Quien consuma esto tiene que decirlo en pantalla en vez de dar a
 * entender que mide el flotante.
 */
class BuildDailyResults
{
    /**
     * Un registro por día operado, en orden cronológico.
     *
     * @return Collection<int, array{date: string, pnl: float, trades: int}>
     */
    public function execute(Account $account): Collection
    {
        // Sin filtro de posiciones abiertas: `trades.exit_time` es NOT NULL, así que
        // aquí solo hay operaciones cerradas por definición del esquema.
        return DB::table('trades')
            ->where('account_id', $account->id)
            ->selectRaw('DATE(exit_time) as day, SUM(pnl) as pnl, COUNT(*) as trades')
            ->groupByRaw('DATE(exit_time)')
            ->orderByRaw('DATE(exit_time)')
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->day,
                'pnl' => (float) $row->pnl,
                'trades' => (int) $row->trades,
            ]);
    }
}
