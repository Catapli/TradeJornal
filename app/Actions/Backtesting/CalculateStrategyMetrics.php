<?php

namespace App\Actions\Backtesting;

use App\Models\BacktestStrategy;
use App\Services\StorageService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalculateStrategyMetrics
{

    public function __construct(protected StorageService $storage) {}

    public function execute(BacktestStrategy $strategy): array
    {
        $trades = $strategy->trades()
            ->orderBy('trade_date')
            ->orderBy('id')
            ->get();

        if ($trades->isEmpty()) return $this->empty();

        $winners = $trades->filter(fn($t) => (float) $t->pnl_r > 0);
        $losers  = $trades->filter(fn($t) => (float) $t->pnl_r < 0);

        return [
            // ── KPIs ──────────────────────────────────────────────
            'total_trades'           => $trades->count(),
            'win_rate'               => $this->winRate($trades),
            'profit_factor'          => $this->profitFactor($winners, $losers),
            'avg_win'                => $this->avgWin($winners),
            'avg_loss'               => $this->avgLoss($losers),
            'biggest_win'            => $this->biggestWin($winners),
            'biggest_loss'           => $this->biggestLoss($losers),
            'arr'                    => 0,
            'avg_r'                  => $this->avgR($trades),
            'expectancy'             => $this->expectancy($trades, $winners, $losers),
            'max_drawdown'           => $this->maxDrawdown($trades),
            'sqn'                    => $this->sqn($trades),
            'max_consecutive_losses' => $this->maxConsecutiveLosses($trades),
            'max_consecutive_wins'   => $this->maxConsecutiveWins($trades),
            'total_pnl'              => round($trades->sum('pnl_r'), 2),
            'r_mode'                 => true,

            // ── Series para gráficos ───────────────────────────────
            'equity_curve'           => $this->equityCurve($trades),
            'r_distribution'         => $this->rDistribution($trades),
            'pnl_by_session'         => $this->pnlBySession($trades),
            'winrate_by_session'     => $this->pnlBySession($trades),
            'pnl_by_weekday'         => $this->dailyWinrate($trades),
            'rules_impact'           => $this->rulesImpact($trades),
            'rating_impact'          => $this->ratingImpact($trades),
            'rolling_winrate'        => $this->rollingWinrate($trades, 10),
            'calendar_data'          => $this->calendarData($trades),
            'daily_winrate'          => $this->dailyWinrate($trades),
            'trader_efficiency'      => $this->traderEfficiency($trades),
            'confluence_analysis' => $this->confluenceAnalysis($trades),
            'trades_list' => $trades->map(fn($t) => [
                'id'            => $t->id,
                'trade_date'    => $t->trade_date->format('Y-m-d'),
                'direction'     => $t->direction,
                'entry_price'   => (float) $t->entry_price,
                'exit_price'    => (float) $t->exit_price,
                'stop_loss'     => (float) $t->stop_loss,
                'pnl_r'         => (float) $t->pnl_r,
                'session'       => $t->session,
                'setup_rating'  => $t->setup_rating,
                'followed_rules' => (bool) $t->followed_rules,
                'notes'         => $t->notes,
                'screenshot'     => $t->screenshot        // ← añade esta línea
                    ? $this->storage->temporaryUrl($t->screenshot)
                    : null,
            ])->values()->toArray(),
        ];
    }

    // ── Clasificadores ────────────────────────────────────────────

    private function isWin($trade): bool
    {
        return (float) $trade->pnl_r > 0;
    }
    private function isLoss($trade): bool
    {
        return (float) $trade->pnl_r < 0;
    }

    // ── KPIs ──────────────────────────────────────────────────────

    private function winRate(Collection $trades): float
    {
        if ($trades->isEmpty()) return 0;
        return round($trades->filter(fn($t) => $this->isWin($t))->count() / $trades->count() * 100, 1);
    }

    private function profitFactor(Collection $winners, Collection $losers): string|float
    {
        $grossProfit = $winners->sum(fn($t) => abs((float) $t->pnl_r));
        $grossLoss   = $losers->sum(fn($t)  => abs((float) $t->pnl_r));

        if ($grossLoss == 0) return $grossProfit > 0 ? '∞' : '0';

        return round($grossProfit / $grossLoss, 2);
    }

    private function avgWin(Collection $winners): float
    {
        return $winners->isEmpty() ? 0 : round($winners->avg('pnl_r'), 2);
    }

    private function avgLoss(Collection $losers): float
    {
        return $losers->isEmpty() ? 0 : round($losers->avg('pnl_r'), 2);
    }

    private function biggestWin(Collection $winners): float
    {
        return $winners->isEmpty() ? 0 : round($winners->max('pnl_r'), 2);
    }

    private function biggestLoss(Collection $losers): float
    {
        return $losers->isEmpty() ? 0 : round($losers->min('pnl_r'), 2);
    }

    private function avgR(Collection $trades): float
    {
        if ($trades->isEmpty()) return 0;
        return round($trades->avg('pnl_r'), 3);
    }

    private function expectancy(Collection $trades, Collection $winners, Collection $losers): float
    {
        if ($trades->isEmpty()) return 0;
        $wr     = $winners->count() / $trades->count();
        $lr     = $losers->count()  / $trades->count();
        $avgWin = $winners->isEmpty() ? 0 : $winners->avg('pnl_r');
        $avgLos = $losers->isEmpty()  ? 0 : abs($losers->avg('pnl_r'));
        return round(($wr * $avgWin) - ($lr * $avgLos), 3);
    }

    private function maxDrawdown(Collection $trades): array
    {
        $equity = 0.0;
        $peak   = 0.0;
        $maxDD  = 0.0;

        foreach ($trades as $trade) {
            $equity += (float) $trade->pnl_r;
            if ($equity > $peak) $peak = $equity;
            $dd = $peak - $equity;
            if ($dd > $maxDD) $maxDD = $dd;
        }

        $pct = $peak != 0 ? round(($maxDD / abs($peak)) * 100, 1) : 0;
        return ['amount' => round($maxDD, 2), 'percent' => $pct];
    }

    private function confluenceAnalysis(Collection $trades): array
    {
        $map = [];

        foreach ($trades as $trade) {
            $confluences = $trade->confluences ?? [];
            if (empty($confluences)) continue;

            foreach ($confluences as $c) {
                if (!isset($map[$c])) {
                    $map[$c] = ['count' => 0, 'wins' => 0, 'total_r' => 0.0];
                }
                $map[$c]['count']++;
                $map[$c]['total_r'] += (float) $trade->pnl_r;
                if ($this->isWin($trade)) $map[$c]['wins']++;
            }
        }

        $result = [];
        foreach ($map as $name => $data) {
            $result[] = [
                'name'    => $name,
                'count'   => $data['count'],
                'wins'    => $data['wins'],
                'wr'      => round($data['wins'] / $data['count'] * 100, 1),
                'avg_r'   => round($data['total_r'] / $data['count'], 2),
                'total_r' => round($data['total_r'], 2),
            ];
        }

        usort($result, fn($a, $b) => $b['avg_r'] <=> $a['avg_r']);

        return $result;
    }

    private function sqn(Collection $trades): float
    {
        $n = $trades->count();
        if ($n < 2) return 0;
        $avgR   = $trades->avg('pnl_r');
        $stdDev = $this->stdDev($trades->pluck('pnl_r')->toArray());
        if ($stdDev == 0) return 0;
        return round(($avgR / $stdDev) * sqrt($n), 2);
    }

    private function maxConsecutiveLosses(Collection $trades): int
    {
        return $this->maxStreak($trades, fn($t) => $this->isLoss($t));
    }

    private function maxConsecutiveWins(Collection $trades): int
    {
        return $this->maxStreak($trades, fn($t) => $this->isWin($t));
    }

    // ── Series para gráficos ──────────────────────────────────────

    private function equityCurve(Collection $trades): array
    {
        $equity = 0.0;
        $peak   = 0.0;
        $curve  = [];
        $dds    = [];

        foreach ($trades as $i => $trade) {
            $equity += (float) $trade->pnl_r;
            if ($equity > $peak) $peak = $equity;
            $curve[] = round($equity, 2);
            $dds[]   = round($peak - $equity, 2);
        }

        return [
            'equity'   => $curve,
            'drawdown' => $dds,
            'labels'   => $trades->map(
                fn($t, $i) =>
                '#' . ($i + 1) . ' ' . $t->trade_date->format('d/m')
            )->values()->toArray(),
            'unit'     => 'R',
        ];
    }

    private function rDistribution(Collection $trades): array
    {
        $buckets = [
            '< -2R'    => 0,
            '-2R a -1R' => 0,
            '-1R a 0R' => 0,
            '0R a +1R' => 0,
            '+1R a +2R' => 0,
            '+2R a +3R' => 0,
            '> +3R'    => 0,
        ];

        foreach ($trades as $trade) {
            $r = (float) $trade->pnl_r;
            if ($r < -2)             $buckets['< -2R']++;
            elseif ($r >= -2 && $r < -1) $buckets['-2R a -1R']++;
            elseif ($r >= -1 && $r < 0)  $buckets['-1R a 0R']++;
            elseif ($r >= 0  && $r < 1)  $buckets['0R a +1R']++;
            elseif ($r >= 1  && $r < 2)  $buckets['+1R a +2R']++;
            elseif ($r >= 2  && $r < 3)  $buckets['+2R a +3R']++;
            else                           $buckets['> +3R']++;
        }

        return [
            'labels' => array_keys($buckets),
            'values' => array_values($buckets),
            'colors' => ['#ef4444', '#f97316', '#fbbf24', '#94a3b8', '#34d399', '#10b981', '#059669'],
        ];
    }

    private function pnlBySession(Collection $trades): array
    {
        $sessions = ['london' => 'London', 'new_york' => 'New York', 'asia' => 'Asia', 'other' => 'Otra'];
        $labels   = [];
        $pnl      = [];
        $wr       = [];
        $counts   = [];

        foreach ($sessions as $key => $label) {
            $group = $trades->filter(fn($t) => $t->session === $key);
            if ($group->isEmpty()) continue;

            $total    = $group->count();
            $labels[] = $label;
            $pnl[]    = round($group->sum('pnl_r'), 2);
            $wr[]     = round($group->filter(fn($t) => $this->isWin($t))->count() / $total * 100, 1);
            $counts[] = $total;
        }

        return compact('labels', 'pnl', 'wr', 'counts');
    }

    private function dailyWinrate(Collection $trades): array
    {
        $days   = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie'];
        $result = [];

        foreach ($days as $dow => $label) {
            $group = $trades->filter(fn($t) => $t->trade_date->dayOfWeekIso === $dow);
            $total = $group->count();
            $wins  = $group->filter(fn($t) => $this->isWin($t))->count();

            $result[] = [
                'day'   => $label,
                'total' => $total,
                'wins'  => $wins,
                'wr'    => $total > 0 ? round($wins / $total * 100, 1) : 0,
                'pnl'   => round($group->sum('pnl_r'), 2),
            ];
        }

        return $result;
    }

    private function rulesImpact(Collection $trades): array
    {
        $followed    = $trades->filter(fn($t) => $t->followed_rules);
        $notFollowed = $trades->filter(fn($t) => !$t->followed_rules);

        $calc = function (Collection $group): array {
            if ($group->isEmpty()) return ['count' => 0, 'wr' => 0, 'avg_r' => 0, 'total_pnl' => 0];
            $wins = $group->filter(fn($t) => $this->isWin($t))->count();
            return [
                'count'     => $group->count(),
                'wr'        => round($wins / $group->count() * 100, 1),
                'avg_r'     => round($group->avg('pnl_r'), 2),
                'total_pnl' => round($group->sum('pnl_r'), 2),
            ];
        };

        return [
            'followed'     => $calc($followed),
            'not_followed' => $calc($notFollowed),
        ];
    }

    private function ratingImpact(Collection $trades): array
    {
        $result = [];

        foreach ([1, 2, 3, 4, 5] as $rating) {
            $group    = $trades->filter(fn($t) => $t->setup_rating === $rating);
            $result[] = [
                'rating' => $rating,
                'count'  => $group->count(),
                'wr'     => $group->isEmpty() ? 0 : round($group->filter(fn($t) => $this->isWin($t))->count() / $group->count() * 100, 1),
                'avg_r'  => $group->isEmpty() ? 0 : round($group->avg('pnl_r'), 2),
            ];
        }

        return $result;
    }

    private function rollingWinrate(Collection $trades, int $window = 10): array
    {
        $values = $trades->values();
        $result = [];

        for ($i = $window - 1; $i < $values->count(); $i++) {
            $slice    = $values->slice($i - $window + 1, $window);
            $result[] = round($slice->filter(fn($t) => $this->isWin($t))->count() / $window * 100, 1);
        }

        return $result;
    }

    private function calendarData(Collection $trades): array
    {
        return $trades
            ->groupBy(fn($t) => $t->trade_date->format('Y-m-d'))
            ->map(function ($dayTrades) {
                $wins  = $dayTrades->filter(fn($t) => $this->isWin($t))->count();
                $total = $dayTrades->count();

                return [
                    // ── Resumen del día (para pintar el calendario) ──
                    'total'   => $total,
                    'wins'    => $wins,
                    'losses'  => $dayTrades->filter(fn($t) => $this->isLoss($t))->count(),
                    'winrate' => $total > 0 ? round($wins / $total * 100, 1) : 0,
                    'pnl'     => round($dayTrades->sum('pnl_r'), 2),

                    // ── Trades del día (para el listado al hacer click) ──
                    'trades'  => $dayTrades->map(fn($t) => [
                        'id'             => $t->id,
                        'trade_date'     => $t->trade_date->format('d/m/Y'),
                        'direction'      => $t->direction,
                        'entry_price'    => (float) $t->entry_price,
                        'exit_price'     => (float) $t->exit_price,
                        'stop_loss'      => $t->stop_loss ? (float) $t->stop_loss : null,
                        'pnl_r'          => $t->pnl_r !== null ? (float) $t->pnl_r : null,
                        'session'        => $t->session,
                        'setup_rating'   => $t->setup_rating,
                        'followed_rules' => (bool) $t->followed_rules,
                        'confluences'    => $t->confluences ?? [],
                        'notes'          => $t->notes,
                        'screenshot' => $t->screenshot
                            ? $this->storage->temporaryUrl($t->screenshot)
                            : null,
                    ])->values()->toArray(),
                ];
            })
            ->toArray();
    }

    private function traderEfficiency(Collection $trades): array
    {
        $rulesFollowedPct = $trades->isEmpty() ? 0 : round(
            $trades->filter(fn($t) => $t->followed_rules)->count() / $trades->count() * 100,
            1
        );

        $highQualityPct = $trades->isEmpty() ? 0 : round(
            $trades->filter(fn($t) => $t->setup_rating >= 4)->count() / $trades->count() * 100,
            1
        );

        $consistency = $trades->count() >= 2
            ? round($this->stdDev($trades->pluck('pnl_r')->toArray()), 2)
            : 0;

        $avgRR = $trades->isEmpty() ? 0 : round(
            $trades->filter(fn($t) => $this->isWin($t))->avg('pnl_r') ?? 0,
            2
        );

        $wr    = $trades->isEmpty() ? 0 : $trades->filter(fn($t) => $this->isWin($t))->count() / $trades->count();
        $avgR  = $this->avgR($trades);
        $score = $this->efficiencyScore($wr, $avgR, $rulesFollowedPct, $highQualityPct);

        return [
            'score'               => $score,
            'avg_rr'              => $avgRR,
            'rules_followed_pct'  => $rulesFollowedPct,
            'high_quality_pct'    => $highQualityPct,
            'consistency'         => $consistency,
        ];
    }

    private function efficiencyScore(float $wr, float $avgR, float $rulesFollowedPct, float $highQualityPct): int
    {
        $score  = 0;
        $score += min(40, $wr * 40);
        $score += min(30, max(0, $avgR / 3 * 30));
        $score += min(15, $rulesFollowedPct / 100 * 15);
        $score += min(15, $highQualityPct / 100 * 15);
        return (int) round($score);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function stdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;
        $mean = array_sum($values) / $n;
        $sum  = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values));
        return sqrt($sum / ($n - 1));
    }

    private function maxStreak(Collection $trades, callable $condition): int
    {
        $max = 0;
        $current = 0;
        foreach ($trades as $trade) {
            if ($condition($trade)) {
                $current++;
                $max = max($max, $current);
            } else $current = 0;
        }
        return $max;
    }

    private function empty(): array
    {
        return [
            'total_trades'           => 0,
            'win_rate'               => 0,
            'profit_factor'          => 0,
            'avg_win'                => 0,
            'avg_loss'               => 0,
            'biggest_win'            => 0,
            'biggest_loss'           => 0,
            'arr'                    => 0,
            'avg_r'                  => 0,
            'expectancy'             => 0,
            'max_drawdown'           => ['amount' => 0, 'percent' => 0],
            'sqn'                    => 0,
            'max_consecutive_losses' => 0,
            'max_consecutive_wins'   => 0,
            'total_pnl'              => 0,
            'r_mode'                 => true,
            'equity_curve'           => ['equity' => [], 'drawdown' => [], 'labels' => [], 'unit' => 'R'],
            'r_distribution'         => ['labels' => [], 'values' => [], 'colors' => []],
            'pnl_by_session'         => ['labels' => [], 'pnl' => [], 'wr' => [], 'counts' => []],
            'winrate_by_session'     => ['labels' => [], 'pnl' => [], 'wr' => [], 'counts' => []],
            'pnl_by_weekday'         => [],
            'rules_impact'           => [
                'followed'     => ['count' => 0, 'wr' => 0, 'avg_r' => 0, 'total_pnl' => 0],
                'not_followed' => ['count' => 0, 'wr' => 0, 'avg_r' => 0, 'total_pnl' => 0],
            ],
            'rating_impact'          => [],
            'rolling_winrate'        => [],
            'calendar_data'          => [],
            'daily_winrate'          => [],
            'trader_efficiency'      => [
                'score'              => 0,
                'avg_rr'             => 0,
                'rules_followed_pct' => 0,
                'high_quality_pct'   => 0,
                'consistency'        => 0,
            ],
            'trades_list' => [],
            'confluence_analysis' => [],
        ];
    }
}
