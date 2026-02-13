<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TradingAnalysisService
{
    // ⚡ OPTIMIZACIÓN: Caché de sesiones para evitar recalcular en cada iteración
    private array $sessionCache = [];

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
     * 3. Motor de Simulación MEJORADO
     */
    public function applyScenarios(Collection $trades, array $scenarios)
    {
        // Clonamos la colección
        $simulated = $trades->map(function ($trade) {
            return clone $trade;
        });

        // --- FILTROS DE EXCLUSIÓN ---

        if (!empty($scenarios['no_fridays'])) {
            $simulated = $simulated->reject(fn($t) => $t->entry_time->dayOfWeek === 5);
        }

        if (!empty($scenarios['only_longs'])) {
            $simulated = $simulated->filter(fn($t) => in_array(strtolower($t->direction), ['long', 'buy']));
        }

        if (!empty($scenarios['only_shorts'])) {
            $simulated = $simulated->filter(fn($t) => in_array(strtolower($t->direction), ['short', 'sell']));
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

        // --- RE-INGENIERÍA DE SALIDAS (Fixed SL/TP) ---

        if (!empty($scenarios['fixed_sl']) || !empty($scenarios['fixed_tp'])) {
            $simulated = $simulated->map(function ($t) use ($scenarios) {
                if ($t->mae_price === null || $t->mfe_price === null) return $t;

                $pipSize = $t->entry_price > 50 ? 0.01 : 0.0001;
                if (str_contains(strtolower($t->ticket ?? ''), 'xau') || str_contains(strtolower($t->ticket ?? ''), 'gold')) {
                    $pipSize = 0.1;
                }

                $slPips = (float) ($scenarios['fixed_sl'] ?? 999999);
                $tpPips = (float) ($scenarios['fixed_tp'] ?? 999999);

                $slDist = $slPips * $pipSize;
                $tpDist = $tpPips * $pipSize;

                $isLong = in_array(strtolower($t->direction), ['long', 'buy']);

                $simSlPrice = $isLong ? $t->entry_price - $slDist : $t->entry_price + $slDist;
                $simTpPrice = $isLong ? $t->entry_price + $tpDist : $t->entry_price - $tpDist;

                $originalDist = abs($t->exit_price - $t->entry_price);
                $valuePerPoint = $originalDist > 0 ? abs($t->pnl) / $originalDist : 0;

                $hitSL = false;
                if ($scenarios['fixed_sl']) {
                    if ($isLong) {
                        $hitSL = $t->mae_price <= $simSlPrice;
                    } else {
                        $hitSL = $t->mae_price >= $simSlPrice;
                    }
                }

                $hitTP = false;
                if ($scenarios['fixed_tp']) {
                    if ($isLong) {
                        $hitTP = $t->mfe_price >= $simTpPrice;
                    } else {
                        $hitTP = $t->mfe_price <= $simTpPrice;
                    }
                }

                if ($hitSL) {
                    $t->pnl = -1 * ($slDist * $valuePerPoint);
                    $t->exit_price = $simSlPrice;
                    $t->notes .= " [Sim: Hit Fixed SL]";
                } elseif ($hitTP) {
                    $t->pnl = $tpDist * $valuePerPoint;
                    $t->exit_price = $simTpPrice;
                    $t->notes .= " [Sim: Hit Fixed TP]";
                }

                return $t;
            });
        }

        return $simulated->values();
    }

    // ⚡ OPTIMIZACIÓN: Método privado con caché
    private function getTradingSession(Carbon $time)
    {
        $cacheKey = $time->hour;

        if (isset($this->sessionCache[$cacheKey])) {
            return $this->sessionCache[$cacheKey];
        }

        $hour = $time->hour;
        if ($hour >= 0 && $hour < 8) $session = 'Asia';
        elseif ($hour >= 8 && $hour < 13) $session = 'Londres';
        elseif ($hour >= 13 && $hour < 22) $session = 'Nueva York';
        else $session = 'Cierre';

        $this->sessionCache[$cacheKey] = $session;

        return $session;
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
     * 6. Scatter Plot (Duración vs PnL)
     */
    public function analyzeDurationScatter(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        return $trades->map(function ($t) {
            return [
                'x' => $t->duration_minutes,
                'y' => (float) $t->pnl,
                'ticket' => $t->ticket
            ];
        })->values()->toArray();
    }

    /**
     * 7. Histograma de Distribución
     */
    public function analyzeDistribution(Collection $trades)
    {
        if ($trades->isEmpty()) return [];

        $min = $trades->min('pnl');
        $max = $trades->max('pnl');

        if ($min == $max) return [];

        $step = ($max - $min) / 15;
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

    /**
     * 8. Análisis de Eficiencia (MAE vs PnL vs MFE)
     */
    public function analyzeTradeEfficiency(Collection $trades)
    {
        $dataset = $trades->filter(function ($t) {
            return $t->exit_time && $t->mae_price !== null && $t->mfe_price !== null && $t->exit_price != $t->entry_price;
        })->sortByDesc('exit_time')->take(15)->reverse();

        if ($dataset->isEmpty()) return [];

        $tickets = [];
        $maeData = [];
        $pnlData = [];
        $mfeData = [];

        foreach ($dataset as $trade) {
            $tickets[] = '#' . $trade->ticket;

            $priceDistance = abs($trade->entry_price - $trade->exit_price);
            $valuePerPoint = $priceDistance > 0 ? abs($trade->pnl) / $priceDistance : 0;

            if ($trade->direction === 'long' || $trade->direction === 'buy') {
                $maeDiff = $trade->mae_price - $trade->entry_price;
                $mfeDiff = $trade->mfe_price - $trade->entry_price;
            } else {
                $maeDiff = $trade->entry_price - $trade->mae_price;
                $mfeDiff = $trade->entry_price - $trade->mfe_price;
            }

            $maeValue = min(0, $maeDiff * $valuePerPoint);
            $mfeValue = max(0, $mfeDiff * $valuePerPoint);

            $maeData[] = round($maeValue, 2);
            $pnlData[] = round($trade->pnl, 2);
            $mfeData[] = round($mfeValue, 2);
        }

        return [
            'categories' => array_values($tickets),
            'series' => [
                ['name' => 'Max Drawdown (MAE)', 'data' => array_values($maeData)],
                ['name' => 'Realized P&L', 'data' => array_values($pnlData)],
                ['name' => 'Max Potential (MFE)', 'data' => array_values($mfeData)],
            ]
        ];
    }

    /**
     * 9. Radar Chart Data (Trader Profile)
     */
    public function analyzeTraderProfile(Collection $trades)
    {
        $count = $trades->count();
        if ($count < 5) return null;

        $wins = $trades->where('pnl', '>', 0);
        $losses = $trades->where('pnl', '<=', 0);
        $winrate = ($wins->count() / $count) * 100;

        $grossProfit = $wins->sum('pnl');
        $grossLoss = abs($losses->sum('pnl'));
        $pf = $grossLoss > 0 ? $grossProfit / $grossLoss : ($grossProfit > 0 ? 3 : 0);
        $pfScore = min(100, ($pf / 3) * 100);

        $avgWin = $wins->avg('pnl') ?? 0;
        $avgLoss = abs($losses->avg('pnl') ?? 1);
        $payoff = $avgLoss > 0 ? $avgWin / $avgLoss : 0;
        $payoffScore = min(100, ($payoff / 2.5) * 100);

        $sqnData = $this->calculateSystemHealth($trades);
        $sqn = $sqnData['sqn'] ?? 0;
        $consistencyScore = min(100, ($sqn / 3.0) * 100);
        if ($consistencyScore <= 0) {
            $consistencyScore = 0;
        }

        $activityScore = min(100, ($count / 50) * 100);

        return [
            'Winrate' => round($winrate),
            'Rentabilidad' => round($pfScore),
            'Ratio R:R' => round($payoffScore),
            'Consistencia' => round($consistencyScore),
            'Experiencia' => round($activityScore)
        ];
    }

    /**
     * 10. Risk Analysis (Risk of Ruin & Streaks)
     * ⚡ OPTIMIZACIÓN: FIX MATEMÁTICO - Eliminado el "/ 2" incorrecto
     */
    public function analyzeRiskOfRuin(Collection $trades, float $currentBalance)
    {
        $count = $trades->count();
        if ($count < 10) return null;

        $wins = $trades->where('pnl', '>', 0);
        $losses = $trades->where('pnl', '<=', 0);

        $winRate = $wins->count() / $count;
        $lossRate = 1 - $winRate;

        $avgWin = $wins->avg('pnl') ?? 0;
        $avgLoss = abs($losses->avg('pnl') ?? 1);
        $payoffRatio = $avgLoss > 0 ? $avgWin / $avgLoss : 0;

        $streakProb = [
            '3' => pow($lossRate, 3) * 100,
            '5' => pow($lossRate, 5) * 100,
            '8' => pow($lossRate, 8) * 100,
            '10' => pow($lossRate, 10) * 100,
        ];

        $avgRiskPerTrade = $avgLoss;
        if ($avgRiskPerTrade <= 0) $avgRiskPerTrade = $currentBalance * 0.01;

        $units = $currentBalance / $avgRiskPerTrade;

        $edge = ($winRate * $payoffRatio) - $lossRate;

        if ($edge <= 0) {
            $riskOfRuin = 100;
        } else {
            // ⚡ FIX CRÍTICO: Eliminado el "/ 2" que no tenía sentido matemático
            // Ahora usamos capping inteligente para evitar exponentes gigantes
            $safeUnits = min($units, 100); // Limitar a 100 unidades máximo para evitar overflow
            $riskOfRuin = pow((1 - $edge) / (1 + $edge), $safeUnits) * 100;
        }

        return [
            'win_rate' => round($winRate * 100, 1),
            'payoff' => round($payoffRatio, 2),
            'risk_of_ruin' => min(100, max(0, round($riskOfRuin, 2))), // Asegurar rango 0-100
            'streak_prob' => array_map(fn($v) => round($v, 1), $streakProb),
            'edge' => round($edge, 3)
        ];
    }

    /**
     * 11. Análisis de Errores (Mistakes Ranking)
     */
    public function analyzeMistakes(Collection $trades)
    {
        // ⚡ OPTIMIZACIÓN: Ya no hacemos loadMissing() porque viene con eager loading

        $stats = [];

        foreach ($trades as $trade) {
            foreach ($trade->mistakes as $mistake) {
                $name = $mistake->name;

                if (!isset($stats[$name])) {
                    $stats[$name] = [
                        'name' => $name,
                        'count' => 0,
                        'total_loss' => 0,
                        'color' => $mistake->color ?? '#EF4444'
                    ];
                }

                $stats[$name]['count']++;
                $stats[$name]['total_loss'] += $trade->pnl;
            }
        }

        return collect($stats)
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }
}
