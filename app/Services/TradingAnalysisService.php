<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TradingAnalysisService
{
    /**
     * 1. Curva de Capital (Normalizada día a día)
     */
    public function calculateEquityCurve(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        $trades = $trades->sortBy('exit_time');

        // Agrupar PnL por día
        $dailyPnL = $trades->groupBy(fn($t) => $t->exit_time->format('Y-m-d'))
            ->map(fn($dayTrades) => $dayTrades->sum('pnl'));

        $startDate = $trades->first()->exit_time->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $period = CarbonPeriod::create($startDate, $endDate);

        $curve = [];
        $runningBalance = 0;

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            if (isset($dailyPnL[$dateKey])) {
                $runningBalance += $dailyPnL[$dateKey];
            }
            $curve[] = [
                'x' => $date->timestamp * 1000,
                'y' => round($runningBalance, 2)
            ];
        }

        return $curve;
    }

    /**
     * 2. Métricas de Salud (SQN, Winrate, etc.)
     */
    public function calculateSystemHealth(Collection $trades)
    {
        $count = $trades->count();
        if ($count < 5) return null;

        $pnls = $trades->pluck('pnl');
        $wins = $pnls->filter(fn($p) => $p > 0);
        $losses = $pnls->filter(fn($p) => $p <= 0);

        $winRate = $count > 0 ? ($wins->count() / $count) * 100 : 0;
        $expectancy = $pnls->avg() ?? 0;

        // Desviación Estándar para SQN
        $mean = $pnls->avg();
        $variance = $pnls->map(fn($val) => pow($val - $mean, 2))->avg();
        $stdDev = sqrt($variance);

        $sqn = ($stdDev > 0) ? ($expectancy / $stdDev) * sqrt($count) : 0;

        return [
            'sqn' => round($sqn, 2),
            'win_rate' => round($winRate, 2),
            'total_trades' => $count,
            'expectancy' => round($expectancy, 2),
            'profit_factor' => ($losses->sum() != 0) ? round(abs($wins->sum() / $losses->sum()), 2) : 0
        ];
    }

    /**
     * 3. Motor de Simulación (Filtros What-If)
     */
    public function applyScenarios(Collection $trades, array $scenarios)
    {
        $simulated = $trades;

        if (!empty($scenarios['no_fridays'])) {
            $simulated = $simulated->reject(fn($t) => $t->entry_time->dayOfWeek === 5);
        }

        if (!empty($scenarios['only_longs'])) {
            $simulated = $simulated->filter(fn($t) => in_array($t->direction, ['long', 'buy', 'BUY']));
        }

        if (!empty($scenarios['remove_worst'])) {
            $worstIds = $simulated->sortBy('pnl')->take(5)->pluck('id');
            $simulated = $simulated->whereNotIn('id', $worstIds);
        }

        if (!empty($scenarios['max_daily_trades']) && is_numeric($scenarios['max_daily_trades'])) {
            $limit = (int) $scenarios['max_daily_trades'];
            $allowedIds = $simulated->groupBy(fn($t) => $t->entry_time->format('Y-m-d'))
                ->flatMap(fn($dayTrades) => $dayTrades->sortBy('entry_time')->take($limit))
                ->pluck('id');
            $simulated = $simulated->whereIn('id', $allowedIds);
        }

        return $simulated->values();
    }

    // --- NUEVOS REPORTES AVANZADOS ---

    private function getTradingSession(Carbon $time)
    {
        $hour = $time->hour;
        if ($hour >= 0 && $hour < 8) return 'Asia';
        if ($hour >= 8 && $hour < 13) return 'Londres';
        if ($hour >= 13 && $hour < 22) return 'Nueva York';
        return 'Cierre';
    }

    /**
     * 4. Análisis por Hora
     */
    public function analyzeByHour(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        $data = [];
        for ($i = 0; $i < 24; $i++) $data[$i] = 0;

        foreach ($trades as $trade) {
            $h = $trade->entry_time->hour;
            $data[$h] += $trade->pnl;
        }

        return collect($data)->map(fn($pnl, $hour) => [
            'hour' => sprintf('%02d:00', $hour),
            'pnl' => round($pnl, 2)
        ])->values()->toArray();
    }

    /**
     * 5. Análisis por Sesión
     */
    public function analyzeBySession(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        $sessions = ['Asia' => 0, 'Londres' => 0, 'Nueva York' => 0, 'Cierre' => 0];

        foreach ($trades as $trade) {
            $sess = $this->getTradingSession($trade->entry_time);
            if (isset($sessions[$sess])) $sessions[$sess] += $trade->pnl;
        }

        return collect($sessions)->map(fn($pnl, $name) => [
            'session' => $name,
            'pnl' => round($pnl, 2)
        ])->values()->toArray();
    }

    /**
     * 6. Scatter Plot (Duración vs PnL) - NUEVO
     */
    public function analyzeDurationScatter(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        return $trades->map(function ($t) {
            return [
                'x' => $t->duration_minutes,
                'y' => (float) $t->pnl,
                'ticket' => $t->ticket // Para el tooltip
            ];
        })->values()->toArray();
    }

    /**
     * 7. Histograma de Distribución (Mejorado) - NUEVO
     */
    public function analyzeDistribution(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        $min = $trades->min('pnl');
        $max = $trades->max('pnl');

        if ($min == $max) return [];

        $step = ($max - $min) / 15; // 15 barras para mejor resolución
        $categories = [];
        $data = [];

        for ($i = 0; $i < 15; $i++) {
            $low = $min + ($i * $step);
            $high = $low + $step;

            $categories[] = number_format($low, 0) . ' a ' . number_format($high, 0);

            $count = $trades->filter(function ($t) use ($low, $high) {
                return $t->pnl >= $low && $t->pnl < $high;
            })->count();

            $data[] = $count;
        }

        return ['categories' => $categories, 'data' => $data];
    }
}
