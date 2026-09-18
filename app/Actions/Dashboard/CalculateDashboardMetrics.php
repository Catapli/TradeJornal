<?php

namespace App\Actions\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Todo el cálculo numérico del dashboard, fuera del componente Livewire.
 *
 * Eran 398 líneas repartidas en ocho métodos privados dentro de DashboardPage
 * (1.257 líneas), lo que obligaba a arrancar Livewire entero para probar una media
 * ponderada. Aquí es una función de filtros → array, igual que las Actions que ya
 * usan cuentas y backtesting.
 *
 * Cada bloque cae a su propio valor por defecto si falla: un KPI roto no debe
 * dejar el dashboard entero en blanco.
 */
class CalculateDashboardMetrics
{
    /** Trades recientes que se miran para deducir la racha actual. */
    private const STREAK_SAMPLE = 50;

    public function execute(DashboardTradeQuery $filters): array
    {
        $kpis = $this->kpis($filters);

        $daily = $this->dailyPnlByExitDate($filters);
        $evolution = $this->evolution($daily);

        // El max drawdown sale de recorrer la curva de equity, no de una agregación.
        $kpis['extraKpis']['max_drawdown'] = $evolution['max_drawdown'];

        return [
            ...$kpis,
            'comparison' => $this->comparison($filters, $kpis),
            'assetBreakdown' => $this->assetBreakdown($filters),
            'dailyWinLossData' => $this->dailyWinLoss($filters),
            'evolutionChartData' => $evolution['chart'],
            'dailyPnLChartData' => $this->dailyBars($daily),
            'heatmapData' => $this->heatmap($filters),
        ];
    }

    /** Win rate, PnL, medias, profit factor, expectancy y racha en una sola query. */
    private function kpis(DashboardTradeQuery $filters): array
    {
        try {
            $stats = $filters->filtered()->selectRaw('
                COUNT(*) as total_trades,
                SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                COALESCE(SUM(pnl), 0) as total_pnl,
                COALESCE(SUM(pnl_percentage), 0) as total_pnl_perc,
                AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
                AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss,
                COALESCE(SUM(CASE WHEN pnl > 0 THEN pnl ELSE 0 END), 0) as gross_profit,
                COALESCE(SUM(CASE WHEN pnl < 0 THEN pnl ELSE 0 END), 0) as gross_loss,
                MAX(pnl) as best_trade,
                MIN(pnl) as worst_trade
            ')->first();

            $total = (int) ($stats->total_trades ?? 0);
            $wins = (int) ($stats->winning_trades ?? 0);
            $losses = $total - $wins;

            $avgWin = $stats->avg_win ? round($stats->avg_win, 2) : 0;
            $avgLoss = $stats->avg_loss ? round($stats->avg_loss, 2) : 0;
            $rrRatio = ($avgLoss != 0) ? abs($avgWin / $avgLoss) : 0;

            $grossLossAbs = abs((float) $stats->gross_loss);
            $profitFactor = $grossLossAbs > 0
                ? round((float) $stats->gross_profit / $grossLossAbs, 2)
                : ((float) $stats->gross_profit > 0 ? null : 0); // null = ∞ (sin pérdidas)

            $expectancy = $total > 0
                ? round((($wins / $total) * $avgWin) + (($losses / $total) * $avgLoss), 2)
                : 0;

            return [
                'winRateChartData' => [
                    'series' => [$wins, $losses],
                    'rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
                    'count_wins' => $wins,
                    'count_losses' => $losses,
                ],
                'pnlTotal' => $stats->total_pnl,
                'pnlTotal_perc' => $stats->total_pnl_perc,
                'avgPnLChartData' => [
                    'avg_win' => $avgWin,
                    'avg_loss' => $avgLoss,
                    'rr_ratio' => round($rrRatio, 2),
                ],
                'extraKpis' => [
                    'profit_factor' => $profitFactor,
                    'expectancy' => $expectancy,
                    'streak' => $this->currentStreak($filters),
                    'best_trade' => $stats->best_trade !== null ? round((float) $stats->best_trade, 2) : null,
                    'worst_trade' => $stats->worst_trade !== null ? round((float) $stats->worst_trade, 2) : null,
                ],
            ];
        } catch (Throwable $e) {
            $this->fail('kpis', $e);

            return [
                'winRateChartData' => ['series' => [0, 0], 'rate' => 0, 'count_wins' => 0, 'count_losses' => 0],
                'pnlTotal' => 0,
                'pnlTotal_perc' => 0,
                'avgPnLChartData' => ['avg_win' => 0, 'avg_loss' => 0, 'rr_ratio' => 0],
                'extraKpis' => [
                    'profit_factor' => 0,
                    'expectancy' => 0,
                    'streak' => ['type' => null, 'count' => 0],
                    'best_trade' => null,
                    'worst_trade' => null,
                    'max_drawdown' => 0,
                ],
            ];
        }
    }

    /**
     * Racha de wins/losses consecutivos desde el trade más reciente hacia atrás.
     * Un break-even la corta: no es ni una cosa ni la otra.
     */
    private function currentStreak(DashboardTradeQuery $filters): array
    {
        $pnls = $filters->filtered()
            ->orderByDesc('exit_time')
            ->limit(self::STREAK_SAMPLE)
            ->pluck('pnl');

        $type = null;
        $count = 0;

        foreach ($pnls as $pnl) {
            $sign = $pnl > 0 ? 'win' : ($pnl < 0 ? 'loss' : null);
            if ($sign === null) {
                break;
            }

            if ($type === null) {
                $type = $sign;
                $count = 1;
            } elseif ($sign === $type) {
                $count++;
            } else {
                break;
            }
        }

        return ['type' => $type, 'count' => $count];
    }

    /** PnL y win rate del periodo equivalente anterior. Solo con rango activo. */
    private function comparison(DashboardTradeQuery $filters, array $kpis): ?array
    {
        try {
            if (!$filters->hasDateRange()) {
                return null;
            }

            $from = Carbon::parse($filters->dateFrom())->startOfDay();
            $to = Carbon::parse($filters->dateTo())->endOfDay();
            $days = $from->diffInDays($to) + 1;

            $prevTo = $from->copy()->subDay()->endOfDay();
            $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

            $prev = $filters->base()
                ->whereBetween('exit_time', [$prevFrom, $prevTo])
                ->selectRaw('
                    COUNT(*) as total_trades,
                    SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                    COALESCE(SUM(pnl), 0) as total_pnl
                ')->first();

            $prevTotal = (int) ($prev->total_trades ?? 0);

            if ($prevTotal === 0) {
                return null; // sin datos previos no hay comparativa honesta
            }

            $prevPnl = (float) $prev->total_pnl;
            $prevWr = round(((int) $prev->winning_trades / $prevTotal) * 100, 2);

            return [
                'prev_label' => $prevFrom->format('d/m') . ' → ' . $prevTo->format('d/m'),
                'pnl_prev' => round($prevPnl, 2),
                'pnl_diff' => round((float) $kpis['pnlTotal'] - $prevPnl, 2),
                'wr_prev' => $prevWr,
                'wr_diff' => round(($kpis['winRateChartData']['rate'] ?? 0) - $prevWr, 2),
            ];
        } catch (Throwable $e) {
            $this->fail('comparison', $e);

            return null;
        }
    }

    /** Rendimiento por símbolo, de mejor a peor PnL. */
    private function assetBreakdown(DashboardTradeQuery $filters): array
    {
        try {
            $rows = $filters->filtered()
                ->join('trade_assets', 'trade_assets.id', '=', 'trades.trade_asset_id')
                ->selectRaw('
                    trade_assets.name as asset,
                    COUNT(*) as trades_count,
                    SUM(CASE WHEN trades.pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                    COALESCE(SUM(trades.pnl), 0) as total_pnl
                ')
                ->groupBy('trade_assets.name')
                ->orderByDesc('total_pnl')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $format = function ($r) {
                $trades = (int) $r->trades_count;
                $wins = (int) $r->winning_trades;
                $pnl = round((float) $r->total_pnl, 2);

                return [
                    'asset' => $r->asset,
                    'trades' => $trades,
                    'wins' => $wins,
                    'losses' => $trades - $wins,
                    'win_rate' => $trades > 0 ? round(($wins / $trades) * 100, 2) : 0,
                    'pnl' => $pnl,
                    'avg_pnl' => $trades > 0 ? round($pnl / $trades, 2) : 0,
                ];
            };

            return [
                'count' => $rows->count(),
                'all' => $rows->map($format)->values()->toArray(),
            ];
        } catch (Throwable $e) {
            $this->fail('assetBreakdown', $e);

            return [];
        }
    }

    /** Días en verde contra días en rojo. */
    private function dailyWinLoss(DashboardTradeQuery $filters): array
    {
        try {
            $dailyStats = $filters->filtered()
                ->selectRaw('DATE(entry_time) as trade_date, SUM(pnl) as daily_pnl')
                ->whereNotNull('entry_time')
                ->groupByRaw('DATE(entry_time)')
                ->get();

            $winDays = $dailyStats->where('daily_pnl', '>', 0)->count();
            $lossDays = $dailyStats->where('daily_pnl', '<', 0)->count();
            $totalDays = $winDays + $lossDays;

            return [
                'series' => [(int) $winDays, (int) $lossDays],
                'rate' => $totalDays > 0 ? round(($winDays / $totalDays) * 100, 2) : 0,
                'count_wins' => $winDays,
                'count_losses' => $lossDays,
            ];
        } catch (Throwable $e) {
            $this->fail('dailyWinLoss', $e);

            return ['series' => [0, 0], 'rate' => 0, 'count_wins' => 0, 'count_losses' => 0];
        }
    }

    /**
     * PnL agrupado por día de cierre, en SQL.
     * Alimenta a la vez la curva de evolución y las barras diarias.
     */
    private function dailyPnlByExitDate(DashboardTradeQuery $filters)
    {
        try {
            return $filters->filtered()
                ->selectRaw('DATE(exit_time) as date, SUM(pnl) as daily_pnl')
                ->groupByRaw('DATE(exit_time)')
                ->orderBy('date', 'asc')
                ->get();
        } catch (Throwable $e) {
            $this->fail('dailyPnlByExitDate', $e);

            return collect();
        }
    }

    /** Curva de capital acumulada y el mayor pico→valle que contiene. */
    private function evolution($dailyPnl): array
    {
        try {
            $labels = [__('labels.start_without_flag')];
            $data = [0];

            $runningTotal = 0;
            $peak = 0;
            $maxDrawdown = 0;

            foreach ($dailyPnl as $day) {
                $runningTotal += $day->daily_pnl;
                $labels[] = $day->date;
                $data[] = round($runningTotal, 2);

                if ($runningTotal > $peak) {
                    $peak = $runningTotal;
                }

                $drawdown = $peak - $runningTotal;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }
            }

            return [
                'chart' => [
                    'categories' => $labels,
                    'data' => $data,
                    'is_positive' => $runningTotal >= 0,
                ],
                'max_drawdown' => round($maxDrawdown, 2),
            ];
        } catch (Throwable $e) {
            $this->fail('evolution', $e);

            return [
                'chart' => ['categories' => [], 'data' => [], 'is_positive' => true],
                'max_drawdown' => 0,
            ];
        }
    }

    private function dailyBars($dailyPnl): array
    {
        try {
            $categories = [];
            $data = [];

            foreach ($dailyPnl as $day) {
                $categories[] = Carbon::parse($day->date)->translatedFormat('d M');
                $data[] = round($day->daily_pnl, 2);
            }

            return ['categories' => $categories, 'data' => $data];
        } catch (Throwable $e) {
            $this->fail('dailyBars', $e);

            return ['categories' => [], 'data' => []];
        }
    }

    /** PnL por día de la semana y hora de entrada (solo laborables). */
    private function heatmap(DashboardTradeQuery $filters): array
    {
        try {
            $rawStats = $filters->filtered()->selectRaw('
                (CAST(EXTRACT(ISODOW FROM entry_time) AS INTEGER) - 1) as day_index,
                CAST(EXTRACT(HOUR FROM entry_time) AS INTEGER) as hour,
                SUM(pnl) as total_pnl
            ')
                ->whereNotNull('entry_time')
                ->whereRaw('EXTRACT(ISODOW FROM entry_time) <= 5')
                ->groupByRaw('(CAST(EXTRACT(ISODOW FROM entry_time) AS INTEGER) - 1), CAST(EXTRACT(HOUR FROM entry_time) AS INTEGER)')
                ->get();

            $days = [
                __('labels.monday'),
                __('labels.tuesday'),
                __('labels.wednesday'),
                __('labels.thursday'),
                __('labels.friday'),
            ];

            // Indexado por día-hora: lookup O(1) en vez de recorrer la colección 120 veces.
            $statsByCell = $rawStats->keyBy(fn ($s) => $s->day_index . '-' . $s->hour);

            $chartData = [];
            foreach ($days as $index => $dayName) {
                $hourlyData = [];
                for ($h = 0; $h < 24; $h++) {
                    $stat = $statsByCell->get($index . '-' . $h);
                    $hourlyData[] = [
                        'x' => sprintf('%02d:00', $h),
                        'y' => $stat ? round($stat->total_pnl, 2) : 0,
                    ];
                }

                $chartData[] = ['name' => $dayName, 'data' => $hourlyData];
            }

            return array_reverse($chartData);
        } catch (Throwable $e) {
            $this->fail('heatmap', $e);

            return [];
        }
    }

    private function fail(string $block, Throwable $e): void
    {
        Log::error("CalculateDashboardMetrics::{$block} falló: {$e->getMessage()}", [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
