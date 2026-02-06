<?php

namespace App\Actions\Strategy;

use App\Models\Strategy;
use Illuminate\Support\Facades\DB;

class RecalculateStrategyStats
{
    public function execute(Strategy $strategy): void
    {
        // 1. Stats agregadas (SQL puro, 1 query)
        $stats = DB::table('trades')
            ->where('strategy_id', $strategy->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END) as losses,
                SUM(pnl) as total_pnl,
                SUM(CASE WHEN pnl > 0 THEN pnl ELSE 0 END) as gross_profit,
                ABS(SUM(CASE WHEN pnl < 0 THEN pnl ELSE 0 END)) as gross_loss,
                AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
                AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss,
                AVG(CASE WHEN mae_price IS NOT NULL THEN 
                    ABS((mae_price - entry_price) / entry_price * 100)
                END) as avg_mae_pct,
                AVG(CASE WHEN mfe_price IS NOT NULL THEN 
                    ABS((mfe_price - entry_price) / entry_price * 100)
                END) as avg_mfe_pct
            ')
            ->first();

        if (!$stats || $stats->total == 0) {
            // Reset si no hay trades
            $this->resetStats($strategy);
            return;
        }

        // 2. Profit Factor
        $profitFactor = $stats->gross_loss > 0
            ? round($stats->gross_profit / $stats->gross_loss, 2)
            : null;

        // 3. Expectancy ($ promedio por trade)
        $expectancy = round($stats->total_pnl / $stats->total, 2);

        // 4. R:R promedio real (avg win / avg loss en valor absoluto)
        $avgRR = ($stats->avg_loss && $stats->avg_loss != 0)
            ? round(abs($stats->avg_win / $stats->avg_loss), 2)
            : null;

        // 5. Max Drawdown (requiere secuencia)
        $maxDrawdown = $this->calculateMaxDrawdown($strategy->id);

        // 6. Sharpe Ratio simplificado (retorno / volatilidad)
        $sharpe = $this->calculateSharpeRatio($strategy->id, $stats->total_pnl, $stats->total);

        // 7. Distribución temporal
        $byDayOfWeek = $this->getStatsByDayOfWeek($strategy->id);
        $byHour = $this->getStatsByHour($strategy->id);

        // 8. Win/Loss streaks
        $streaks = $this->calculateStreaks($strategy->id);

        // 9. Update
        $strategy->update([
            'stats_total_trades' => $stats->total,
            'stats_winning_trades' => $stats->wins,
            'stats_losing_trades' => $stats->losses,
            'stats_total_pnl' => $stats->total_pnl,
            'stats_gross_profit' => $stats->gross_profit,
            'stats_gross_loss' => $stats->gross_loss,
            'stats_profit_factor' => $profitFactor,
            'stats_avg_win' => $stats->avg_win,
            'stats_avg_loss' => $stats->avg_loss,
            'stats_expectancy' => $expectancy,
            'stats_avg_rr' => $avgRR,
            'stats_max_drawdown_pct' => $maxDrawdown,
            'stats_sharpe_ratio' => $sharpe,
            'stats_avg_mae_pct' => $stats->avg_mae_pct ? round($stats->avg_mae_pct, 2) : null,
            'stats_avg_mfe_pct' => $stats->avg_mfe_pct ? round($stats->avg_mfe_pct, 2) : null,
            'stats_by_day_of_week' => $byDayOfWeek,
            'stats_by_hour' => $byHour,
            'stats_best_win_streak' => $streaks['best_win_streak'],
            'stats_worst_loss_streak' => $streaks['worst_loss_streak'],
            'stats_last_calculated_at' => now(),
        ]);
    }

    private function resetStats(Strategy $strategy): void
    {
        $strategy->update([
            'stats_total_trades' => 0,
            'stats_winning_trades' => 0,
            'stats_losing_trades' => 0,
            'stats_total_pnl' => 0,
            'stats_gross_profit' => 0,
            'stats_gross_loss' => 0,
            'stats_profit_factor' => null,
            'stats_avg_win' => null,
            'stats_avg_loss' => null,
            'stats_expectancy' => null,
            'stats_avg_rr' => null,
            'stats_max_drawdown_pct' => null,
            'stats_sharpe_ratio' => null,
            'stats_avg_mae_pct' => null,
            'stats_avg_mfe_pct' => null,
            'stats_by_day_of_week' => null,
            'stats_by_hour' => null,
            'stats_best_win_streak' => 0,
            'stats_worst_loss_streak' => 0,
            'stats_last_calculated_at' => now(),
        ]);
    }

    // En calculateMaxDrawdown:

    private function calculateMaxDrawdown(int $strategyId): ?float
    {
        // Obtenemos solo los porcentajes cronológicamente
        $trades = DB::table('trades')
            ->where('strategy_id', $strategyId)
            ->whereNotNull('pnl_percentage') // Asegurar que existe el dato
            ->orderBy('exit_time')
            ->pluck('pnl_percentage');

        if ($trades->isEmpty()) return 0.0;

        // Equity base normalizado (empezamos en 1.0 = 100%)
        $equity = 1.0;
        $peak = 1.0;
        $maxDrawdown = 0.0;

        foreach ($trades as $pct) {
            // Convertir el porcentaje de BD (ej: 1.5 para 1.5%) a decimal (0.015)
            // OJO: Verifica si en tu BD "1.50" significa 1.5% o 150%. 
            // Si 1.50 es 1.5%, divide entre 100. Si es 0.015, úsalo directo.
            // Asumo por tu ejemplo anterior (-0.05 pnl en 0.01 lot) que pnl_percentage podría venir ya en decimal o %
            // AJUSTAR AQUÍ SEGÚN TU DATO REAL:
            $decimalReturn = $pct / 100;

            // Interés compuesto: Equity * (1 + retorno)
            // Si prefieres interés simple (fixed risk): Equity += $decimalReturn
            $equity = $equity * (1 + $decimalReturn);

            // Actualizar pico
            if ($equity > $peak) {
                $peak = $equity;
            }

            // Calcular DD
            $dd = 0;
            if ($peak > 0) {
                $dd = (($peak - $equity) / $peak) * 100;
            }

            $maxDrawdown = max($maxDrawdown, $dd);
        }

        return round($maxDrawdown, 2);
    }



    private function calculateSharpeRatio(int $strategyId, float $totalPnl, int $totalTrades): ?float
    {
        if ($totalTrades < 2) return null;

        // Desviación estándar de PnL
        $trades = DB::table('trades')
            ->where('strategy_id', $strategyId)
            ->pluck('pnl');

        $mean = $totalPnl / $totalTrades;
        $variance = $trades->map(fn($pnl) => pow($pnl - $mean, 2))->sum() / $totalTrades;
        $stdDev = sqrt($variance);

        if ($stdDev == 0) return null;

        // Sharpe simplificado (sin risk-free rate)
        return round($mean / $stdDev, 2);
    }

    private function getStatsByDayOfWeek(int $strategyId): array
    {
        $rows = DB::table('trades')
            ->where('strategy_id', $strategyId)
            ->whereNotNull('exit_time')
            ->selectRaw('
            extract(isodow from exit_time) as dow,
            count(*) as total,
            sum(case when pnl > 0 then 1 else 0 end) as wins,
            sum(pnl) as pnl
        ')
            ->groupBy('dow')
            ->get()
            ->keyBy(fn($r) => (int) $r->dow);

        // Normalizamos a L..D
        $labels = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        $result = [];
        foreach ($labels as $dow => $label) {
            $r = $rows->get($dow);

            $total = $r?->total ?? 0;
            $wins  = $r?->wins ?? 0;
            $pnl   = (float) ($r?->pnl ?? 0);

            $result[$label] = [
                'total' => (int) $total,
                'wins' => (int) $wins,
                'pnl' => round($pnl, 2),
                'winrate' => $total > 0 ? round(($wins / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function getStatsByHour(int $strategyId): array
    {
        $rows = DB::table('trades')
            ->where('strategy_id', $strategyId)
            ->whereNotNull('exit_time')
            ->selectRaw('
            extract(hour from exit_time) as hour,
            count(*) as total,
            sum(pnl) as pnl
        ')
            ->groupBy('hour')
            ->get()
            ->keyBy(fn($r) => (int) $r->hour);

        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $r = $rows->get($h);

            $result[sprintf('%02d', $h)] = [
                'total' => (int) ($r?->total ?? 0),
                'pnl' => round((float) ($r?->pnl ?? 0), 2),
            ];
        }

        return $result;
    }

    private function calculateStreaks(int $strategyId): array
    {
        $trades = DB::table('trades')
            ->where('strategy_id', $strategyId)
            ->orderBy('exit_time')
            ->pluck('pnl');

        $bestWinStreak = 0;
        $worstLossStreak = 0;
        $currentWinStreak = 0;
        $currentLossStreak = 0;

        foreach ($trades as $pnl) {
            if ($pnl > 0) {
                $currentWinStreak++;
                $currentLossStreak = 0;
                $bestWinStreak = max($bestWinStreak, $currentWinStreak);
            } elseif ($pnl < 0) {
                $currentLossStreak++;
                $currentWinStreak = 0;
                $worstLossStreak = max($worstLossStreak, $currentLossStreak);
            }
        }

        return [
            'best_win_streak' => $bestWinStreak,
            'worst_loss_streak' => $worstLossStreak,
        ];
    }
}
