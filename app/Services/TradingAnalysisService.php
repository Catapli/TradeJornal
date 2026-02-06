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
     * 3. Motor de Simulación MEJORADO
     */
    public function applyScenarios(Collection $trades, array $scenarios)
    {
        // A. Clonamos la colección para no modificar los objetos originales en memoria
        // Usamos map para crear copias de los objetos relevantes si vamos a modificar sus propiedades PnL
        $simulated = $trades->map(function ($trade) {
            return clone $trade;
        });

        // --- FILTROS DE EXCLUSIÓN (Lo que ya tenías) ---

        if (!empty($scenarios['no_fridays'])) {
            $simulated = $simulated->reject(fn($t) => $t->entry_time->dayOfWeek === 5);
        }

        if (!empty($scenarios['only_longs'])) {
            $simulated = $simulated->filter(fn($t) => in_array(strtolower($t->direction), ['long', 'buy']));
        }

        if (!empty($scenarios['only_shorts'])) { // NUEVO
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
        // Aquí modificamos el PnL del trade basado en MAE/MFE

        if (!empty($scenarios['fixed_sl']) || !empty($scenarios['fixed_tp'])) {
            $simulated = $simulated->map(function ($t) use ($scenarios) {
                // Solo podemos simular si tenemos datos de MAE/MFE
                if ($t->mae_price === null || $t->mfe_price === null) return $t;

                // 1. Detectar Pip Size (Heurística simple si no está en DB)
                // JPY suele tener precio > 50 (ej: 145.00), otros < 5 (ej: 1.0800)
                // Lo ideal es tener $t->asset->pip_size, pero usaremos esto por ahora:
                $pipSize = $t->entry_price > 50 ? 0.01 : 0.0001;
                if (str_contains(strtolower($t->ticket), 'xau') || str_contains(strtolower($t->ticket), 'gold')) {
                    $pipSize = 0.1; // Ajuste para ORO si fuera necesario
                }

                // 2. Calcular Precios Objetivo
                $slPips = (float) ($scenarios['fixed_sl'] ?? 999999);
                $tpPips = (float) ($scenarios['fixed_tp'] ?? 999999);

                $slDist = $slPips * $pipSize;
                $tpDist = $tpPips * $pipSize;

                $isLong = in_array(strtolower($t->direction), ['long', 'buy']);

                $simSlPrice = $isLong ? $t->entry_price - $slDist : $t->entry_price + $slDist;
                $simTpPrice = $isLong ? $t->entry_price + $tpDist : $t->entry_price - $tpDist;

                // 3. Calcular Valor por Pip (Para recalcular PnL)
                // PnL Original / Distancia Original = Valor del movimiento
                $originalDist = abs($t->exit_price - $t->entry_price);
                $valuePerPoint = $originalDist > 0 ? abs($t->pnl) / $originalDist : 0;

                // 4. Lógica de Simulación (Conservadora)
                // ¿Tocó el SL?
                $hitSL = false;
                if ($scenarios['fixed_sl']) {
                    if ($isLong) {
                        $hitSL = $t->mae_price <= $simSlPrice;
                    } else {
                        $hitSL = $t->mae_price >= $simSlPrice; // En short, si sube toca SL
                    }
                }

                // ¿Tocó el TP?
                $hitTP = false;
                if ($scenarios['fixed_tp']) {
                    if ($isLong) {
                        $hitTP = $t->mfe_price >= $simTpPrice;
                    } else {
                        $hitTP = $t->mfe_price <= $simTpPrice;
                    }
                }

                // 5. Resolución del Resultado
                if ($hitSL) {
                    // Asumimos que SL ocurre primero para ser conservadores (Worst Case)
                    // A menos que sea un TP muy corto y un SL muy largo, pero por seguridad: LOSS.
                    $t->pnl = -1 * ($slDist * $valuePerPoint);
                    $t->exit_price = $simSlPrice;
                    $t->notes .= " [Sim: Hit Fixed SL]";
                } elseif ($hitTP) {
                    // Si no tocó SL (o SL no definido) y tocó TP: WIN.
                    $t->pnl = $tpDist * $valuePerPoint;
                    $t->exit_price = $simTpPrice;
                    $t->notes .= " [Sim: Hit Fixed TP]";
                } else {
                    // No tocó ni SL ni TP simulados.
                    // Opción A: Mantener resultado original.
                    // Opción B: Cerrar al precio de cierre original (Simulando que acabó el tiempo).
                    // Dejamos el original, pero si el original excedía estos límites (ej: panic close),
                    // ya estarían cubiertos arriba.
                }

                return $t;
            });
        }

        // Re-indexar para evitar huecos en las claves
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

    /**
     * 8. Análisis de Eficiencia (MAE vs PnL vs MFE)
     * Convierte los precios MAE/MFE a valor monetario ($)
     */
    public function analyzeTradeEfficiency(Collection $trades)
    {
        // Filtramos trades cerrados, con datos MAE/MFE y tomamos los últimos 15
        $dataset = $trades->filter(function ($t) {
            return $t->exit_time && $t->mae_price !== null && $t->mfe_price !== null && $t->exit_price != $t->entry_price;
        })->sortByDesc('exit_time')->take(15)->reverse(); // Orden cronológico para el gráfico

        if ($dataset->isEmpty()) return [];

        $tickets = [];
        $maeData = [];
        $pnlData = [];
        $mfeData = [];

        foreach ($dataset as $trade) {
            $tickets[] = '#' . $trade->ticket;

            // 1. Calcular cuánto vale cada punto de movimiento en dinero para este trade
            // Fórmula: Valor = |PnL| / |Diferencia Precio Entrada-Salida|
            $priceDistance = abs($trade->entry_price - $trade->exit_price);

            // Evitar división por cero si salió breakeven exacto
            $valuePerPoint = $priceDistance > 0 ? abs($trade->pnl) / $priceDistance : 0;

            // 2. Calcular distancias MAE/MFE
            if ($trade->direction === 'long' || $trade->direction === 'buy') {
                $maeDiff = $trade->mae_price - $trade->entry_price; // Debería ser negativo
                $mfeDiff = $trade->mfe_price - $trade->entry_price; // Debería ser positivo
            } else {
                // En short, si el precio sube (mae > entry), es negativo para nosotros
                $maeDiff = $trade->entry_price - $trade->mae_price;
                $mfeDiff = $trade->entry_price - $trade->mfe_price;
            }

            // 3. Convertir a Dinero
            // Forzamos MAE a ser negativo (o 0) y MFE positivo (o 0) por seguridad visual
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
     * Retorna métricas normalizadas (0-100) para el gráfico de araña.
     */
    public function analyzeTraderProfile(Collection $trades)
    {
        $count = $trades->count();
        if ($count < 5) return null; // Necesitamos datos mínimos

        // A. Winrate (Ya es 0-100)
        $wins = $trades->where('pnl', '>', 0);
        $losses = $trades->where('pnl', '<=', 0);
        $winrate = ($wins->count() / $count) * 100;

        // B. Profit Factor (0 a 3.0 -> escalado a 100)
        $grossProfit = $wins->sum('pnl');
        $grossLoss = abs($losses->sum('pnl'));
        $pf = $grossLoss > 0 ? $grossProfit / $grossLoss : ($grossProfit > 0 ? 3 : 0);
        $pfScore = min(100, ($pf / 3) * 100); // 3.0 PF es la nota máxima

        // C. Payoff Ratio (Avg Win / Avg Loss) - (1:3 -> escalado a 100)
        $avgWin = $wins->avg('pnl') ?? 0;
        $avgLoss = abs($losses->avg('pnl') ?? 1); // Evitar div by zero
        $payoff = $avgLoss > 0 ? $avgWin / $avgLoss : 0;
        $payoffScore = min(100, ($payoff / 2.5) * 100); // Ratio 1:2.5 es nota máxima

        // D. Consistency (SQN) - (Escala: SQN 3.0 = 100)
        // Reutilizamos lógica de SQN o calculamos simplificada
        $sqnData = $this->calculateSystemHealth($trades);
        $sqn = $sqnData['sqn'] ?? 0;
        $consistencyScore = min(100, ($sqn / 3.0) * 100); // SQN 3.0 es excelente
        if ($consistencyScore <= 0) {
            $consistencyScore = 0;
        }

        // E. Discipline / Activity (Volumen de muestra)
        // 50 trades = 100% de confianza estadística (simple)
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
     * Calcula probabilidades de rachas y riesgo de quiebra.
     */
    public function analyzeRiskOfRuin(Collection $trades, float $currentBalance)
    {
        $count = $trades->count();
        if ($count < 10) return null; // Necesitamos muestra mínima

        // 1. Calcular métricas base
        $wins = $trades->where('pnl', '>', 0);
        $losses = $trades->where('pnl', '<=', 0); // Breakeven cuenta como "no win" para seguridad

        $winRate = $wins->count() / $count;
        $lossRate = 1 - $winRate;

        // 2. Calcular Payoff Ratio (Avg Win / Avg Loss)
        $avgWin = $wins->avg('pnl') ?? 0;
        $avgLoss = abs($losses->avg('pnl') ?? 1); // Evitar div by zero
        $payoffRatio = $avgLoss > 0 ? $avgWin / $avgLoss : 0;

        // 3. Probabilidad de Rachas de Pérdidas (en los próximos 100 trades)
        // Fórmula simplificada: LossRate ^ N
        // Esto responde: "Qué probabilidad hay de que los próximos N trades sean pérdidas?"
        $streakProb = [
            '3' => pow($lossRate, 3) * 100,
            '5' => pow($lossRate, 5) * 100,
            '8' => pow($lossRate, 8) * 100,
            '10' => pow($lossRate, 10) * 100,
        ];

        // 4. Riesgo de Ruina (Risk of Ruin) - Modelo de Perry Kaufman simplificado
        // Asumimos un riesgo fijo del 1% o calculamos el real promedio
        $avgRiskPerTrade = $avgLoss; // Asumimos que la pérdida media es el riesgo por trade
        if ($avgRiskPerTrade <= 0) $avgRiskPerTrade = $currentBalance * 0.01; // Fallback al 1%

        $units = $currentBalance / $avgRiskPerTrade; // Cuantas "balas" tenemos

        // Edge (Ventaja) = (WinRate * Payoff) - LossRate
        // Si el Edge es negativo, la ruina es 100% segura a largo plazo
        $edge = ($winRate * $payoffRatio) - $lossRate;

        if ($edge <= 0) {
            $riskOfRuin = 100;
        } else {
            // Fórmula: ((1 - Edge) / (1 + Edge)) ^ Units
            // Usamos una versión suavizada para no romper con exponentes altos
            $riskOfRuin = pow((1 - $edge) / (1 + $edge), $units / 2) * 100;
            // Dividimos units/2 para ser conservadores en cuentas grandes
        }

        return [
            'win_rate' => round($winRate * 100, 1),
            'payoff' => round($payoffRatio, 2),
            'risk_of_ruin' => min(100, round($riskOfRuin, 2)),
            'streak_prob' => array_map(fn($v) => round($v, 1), $streakProb),
            'edge' => round($edge, 3)
        ];
    }

    /**
     * 11. Análisis de Errores (Mistakes Ranking)
     */
    public function analyzeMistakes(Collection $trades)
    {
        // Aseguramos cargar la relación si no está cargada (aunque mejor hacerlo en la query principal)
        $trades->loadMissing('mistakes');

        $stats = [];

        foreach ($trades as $trade) {
            foreach ($trade->mistakes as $mistake) {
                $name = $mistake->name;

                if (!isset($stats[$name])) {
                    $stats[$name] = [
                        'name' => $name,
                        'count' => 0,
                        'total_loss' => 0,
                        'color' => $mistake->color ?? '#EF4444' // Fallback Rojo
                    ];
                }

                $stats[$name]['count']++;
                // Sumamos el PnL negativo (coste del error). Si el trade fue positivo a pesar del error, sumamos 0 o el PnL (depende de la lógica, aquí sumamos PnL para ver impacto real)
                $stats[$name]['total_loss'] += $trade->pnl;
            }
        }

        // Ordenamos por frecuencia (los más repetidos arriba)
        return collect($stats)
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }
}
