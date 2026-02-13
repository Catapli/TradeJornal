<?php

// app/Actions/Accounts/CalculateAccountStatistics.php
namespace App\Actions\Accounts;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CalculateAccountStatistics
{
    public function execute(Account $account, bool $forceRefresh = false): array
    {
        $cacheKey = "account_stats_{$account->id}";

        // Caché de 5 minutos
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Query ÚNICA consolidada (en vez de 6 separadas)
        $stats = DB::table('trades as t')
            ->leftJoin('trade_assets as ta', 't.trade_asset_id', '=', 'ta.id')
            ->where('t.account_id', $account->id)
            ->whereNotNull('t.exit_time')
            ->selectRaw("
                COUNT(*) as total_trades,
                SUM(CASE WHEN t.pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                AVG(t.duration_minutes) as avg_duration_minutes,
                MAX(t.pnl) as max_win,
                MIN(t.pnl) as max_loss,
                AVG(CASE WHEN t.pnl > 0 THEN t.pnl END) as avg_win,
                AVG(CASE WHEN t.pnl < 0 THEN ABS(t.pnl) END) as avg_loss_abs,
                SUM(CASE WHEN t.pnl > 0 THEN t.pnl ELSE 0 END) as gross_profit,
                SUM(CASE WHEN t.pnl < 0 THEN ABS(t.pnl) ELSE 0 END) as gross_loss,
                COUNT(DISTINCT DATE(t.entry_time)) as trading_days,
                MIN(t.entry_time) as first_trade_date
            ")
            ->first();

        // Top Asset (query separada porque requiere GROUP BY)
        $topAsset = DB::table('trades as t')
            ->join('trade_assets as ta', 't.trade_asset_id', '=', 'ta.id')
            ->where('t.account_id', $account->id)
            ->whereNotNull('t.exit_time')
            ->selectRaw('ta.symbol, COUNT(*) as trade_count')
            ->groupBy('ta.id', 'ta.symbol')
            ->orderByDesc('trade_count')
            ->first();

        // Formatear resultados
        $result = [
            'totalTrades' => (int) $stats->total_trades,
            'winRate' => $stats->total_trades > 0
                ? round(($stats->winning_trades / $stats->total_trades) * 100, 1)
                : 0,
            'avgDurationMinutes' => round($stats->avg_duration_minutes ?? 0),
            'avgDurationFormatted' => $this->formatDuration($stats->avg_duration_minutes ?? 0),
            'maxWin' => $stats->max_win ?? 0,
            'maxLoss' => abs($stats->max_loss ?? 0),
            'avgWinTrade' => round($stats->avg_win ?? 0, 2),
            'avgLossTrade' => round($stats->avg_loss_abs ?? 0, 2),
            'arr' => ($stats->avg_loss_abs ?? 0) > 0
                ? round(($stats->avg_win ?? 0) / $stats->avg_loss_abs, 2)
                : 0,
            'topAsset' => $topAsset?->symbol ?? 'N/A',
            'tradingDays' => (int) ($stats->trading_days ?? 0),
            'grossProfit' => round($stats->gross_profit ?? 0, 2),
            'grossLoss' => round($stats->gross_loss ?? 0, 2),
            'profitFactor' => ($stats->gross_loss ?? 0) > 0
                ? round(($stats->gross_profit ?? 0) / $stats->gross_loss, 4)
                : 0,
            'firstTradeDate' => $stats->first_trade_date,
            'accountAgeDays' => $account->funded_date
                ? $account->funded_date->diffInDays(now())
                : 0,
            'accountAgeFormatted' => $this->formatAge(
                $account->funded_date ? $account->funded_date->diffInDays(now()) : 0
            ),
        ];

        // Cachear 5 minutos
        Cache::put($cacheKey, $result, now()->addMinutes(5));

        return $result;
    }

    private function formatDuration($minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? sprintf('%dh %02dm', $hours, $mins) : $mins . 'm';
    }

    private function formatAge($days): string
    {
        $days = (int) floor($days);

        if ($days >= 365) {
            $years = floor($days / 365);
            $remainingDays = $days % 365;
            return $years . 'a ' . $remainingDays . 'd';
        }

        if ($days >= 30) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            return $months . 'm ' . $remainingDays . 'd';
        }

        return $days . ' días';
    }
}
