<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Strategy;
use Illuminate\Support\Facades\Auth;

class TradingRulesService
{
    public function checkDashboardStatus($selectedAccountIds)
    {
        // 1. Determinar qué cuentas mirar
        $query = Account::where('user_id', Auth::id())->where('status', '!=', 'burned');

        if ($selectedAccountIds !== ['all']) {
            $query->whereIn('id', $selectedAccountIds);
        }

        // Traemos cuentas con sus planes y trades de HOY
        $accounts = $query->with(['tradingPlan', 'trades' => function ($q) {
            $q->whereDate('exit_time', Carbon::today());
        }])->get();

        if ($accounts->isEmpty()) return null;

        // 2. Acumuladores Globales
        $totalCurrentPnL = 0;
        $totalTargetMoney = 0;
        $totalLimitMoney = 0; // Será negativo
        $totalTrades = 0;
        $maxTradesLimit = 0;

        $hasPlans = false;

        foreach ($accounts as $acc) {
            $plan = $acc->tradingPlan;
            $pnl = $acc->trades->sum('pnl');
            $tradesCount = $acc->trades->count();

            $totalCurrentPnL += $pnl;
            $totalTrades += $tradesCount;

            if ($plan && $plan->is_active) {
                $hasPlans = true;
                $balance = $acc->current_balance > 0 ? $acc->current_balance : 1;

                // Sumar Meta ($)
                if ($plan->daily_profit_target_percent) {
                    $totalTargetMoney += $balance * ($plan->daily_profit_target_percent / 100);
                }

                // Sumar Límite ($) (Negativo)
                if ($plan->max_daily_loss_percent) {
                    $totalLimitMoney += -abs($balance * ($plan->max_daily_loss_percent / 100));
                }

                // Sumar Límite Trades
                if ($plan->max_daily_trades) {
                    $maxTradesLimit += $plan->max_daily_trades;
                }
            }
        }

        if (!$hasPlans) return null;

        // 3. Evaluar Estado Global
        $status = 'active';
        if ($totalLimitMoney != 0 && $totalCurrentPnL <= $totalLimitMoney) $status = 'failed';
        if ($totalTargetMoney != 0 && $totalCurrentPnL >= $totalTargetMoney) $status = 'passed';

        return [
            'pnl' => [
                'current' => $totalCurrentPnL,
                'target' => $totalTargetMoney,
                'limit' => $totalLimitMoney,
                'status' => $status,
                'progress' => ($totalTargetMoney > 0) ? min(100, max(0, ($totalCurrentPnL / $totalTargetMoney) * 100)) : 0
            ],
            'trades' => [
                'current' => $totalTrades,
                'limit' => $maxTradesLimit > 0 ? $maxTradesLimit : null,
            ]
        ];
    }

    private function getPnlStatus($pnl, $limitMoney, $targetMoney)
    {
        if ($limitMoney !== null && $pnl <= $limitMoney) return 'failed';
        if ($targetMoney !== null && $pnl >= $targetMoney) return 'passed';
        return 'active';
    }

    private function isMarketOpen($plan)
    {
        if (!$plan->start_time || !$plan->end_time) return true;
        $now = now()->format('H:i:s');
        return $now >= $plan->start_time && $now <= $plan->end_time;
    }
}
