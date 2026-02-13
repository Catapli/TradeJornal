<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GenerateBalanceChartData
{
    /**
     * Genera los datos del gráfico de balance con agrupación en SQL
     * 
     * @param Account $account
     * @param string $timeframe '1h', '24h', '7d', 'all'
     * @return array
     */
    public function execute(Account $account, string $timeframe = 'all'): array
    {
        $cacheKey = "balance_chart_{$account->id}_{$timeframe}";

        // Caché de 3 minutos para el gráfico
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // ========================================
        // 1. FECHA DE CORTE SEGÚN TIMEFRAME
        // ========================================
        $cutoffDate = $this->getCutoffDate($timeframe);

        // ========================================
        // 2. BALANCE INICIAL
        // ========================================
        if ($cutoffDate) {
            $priorPnl = DB::table('trades')
                ->where('account_id', $account->id)
                ->where('exit_time', '<', $cutoffDate)
                ->sum('pnl');

            $startBalance = $account->initial_balance + $priorPnl;
            $startLabel = $cutoffDate->format('H:i');
        } else {
            $startBalance = $account->initial_balance;
            $startLabel = 'Inicio';
        }

        // ========================================
        // 3. FORMATO DE AGRUPACIÓN (CRÍTICO)
        // ========================================
        // Usamos DATE_TRUNC o TO_CHAR según el timeframe

        $groupFormat = match ($timeframe) {
            '1h' => "TO_CHAR(exit_time, 'HH24:MI')",      // Agrupa por minuto (ej: "14:30")
            '24h' => "TO_CHAR(exit_time, 'HH24:00')",     // Agrupa por hora (ej: "14:00")
            '7d' => "TO_CHAR(exit_time, 'DD/MM HH24:00')", // Agrupa por día+hora (ej: "08/02 14:00")
            default => "TO_CHAR(exit_time, 'DD Mon')",     // Agrupa por día (ej: "08 Feb")
        };

        // También necesitamos un campo para ordenar correctamente
        $orderField = match ($timeframe) {
            '1h' => "DATE_TRUNC('minute', exit_time)",
            '24h' => "DATE_TRUNC('hour', exit_time)",
            '7d' => "DATE_TRUNC('hour', exit_time)",
            default => "DATE_TRUNC('day', exit_time)",
        };

        // ========================================
        // 4. QUERY AGRUPADA (CORREGIDA)
        // ========================================
        $groupedData = DB::table('trades')
            ->selectRaw("
            {$groupFormat} as time_label,
            {$orderField} as order_time,
            SUM(pnl) as interval_pnl,
            SUM(CASE 
                WHEN mae_price IS NOT NULL AND ABS(exit_price - entry_price) > 0 
                THEN -1 * (ABS(entry_price - mae_price) * ABS(pnl) / ABS(exit_price - entry_price))
                ELSE 0 
            END) as total_floating_loss,
            SUM(CASE 
                WHEN mfe_price IS NOT NULL AND ABS(exit_price - entry_price) > 0 
                THEN (ABS(entry_price - mfe_price) * ABS(pnl) / ABS(exit_price - entry_price))
                ELSE 0 
            END) as total_floating_profit
        ")
            ->where('account_id', $account->id)
            ->when($cutoffDate, fn($q) => $q->where('exit_time', '>=', $cutoffDate))
            ->whereNotNull('exit_time')
            ->groupByRaw("{$groupFormat}, {$orderField}") // ✅ Ahora agrupa correctamente
            ->orderBy('order_time', 'asc')
            ->get();

        // ========================================
        // 5. CONSTRUIR ARRAYS PARA APEXCHARTS
        // ========================================
        $labels = [$startLabel];
        $balanceData = [round($startBalance, 2)];
        $minEquityData = [round($startBalance, 2)];
        $maxEquityData = [round($startBalance, 2)];

        $runningBalance = $startBalance;

        if ($groupedData->isNotEmpty()) {
            foreach ($groupedData as $point) {
                // Balance Real (Línea Verde)
                $runningBalance += $point->interval_pnl;

                // Equity Mínima con MAE (Línea Roja - Riesgo)
                // Usamos el mínimo de todos los trades del intervalo
                $minEquity = $runningBalance + ($point->total_floating_loss ?? 0);

                // Equity Máxima con MFE (Línea Azul - Potencial)
                // Usamos el máximo de todos los trades del intervalo
                $maxEquity = $runningBalance + ($point->total_floating_profit ?? 0);

                // Agregar puntos
                $labels[] = $point->time_label;
                $balanceData[] = round($runningBalance, 2);
                $minEquityData[] = round(min($minEquity, $runningBalance), 2);
                $maxEquityData[] = round(max($maxEquity, $runningBalance), 2);
            }
        } else {
            // Línea plana si no hay trades
            $labels[] = now()->format('H:i');
            $balanceData[] = round($startBalance, 2);
            $minEquityData[] = round($startBalance, 2);
            $maxEquityData[] = round($startBalance, 2);
        }

        // ========================================
        // 6. ESTRUCTURA FINAL PARA APEXCHARTS
        // ========================================
        $result = [
            'categories' => $labels,
            'series' => [
                [
                    'name' => __('labels.max_potencial'),
                    'data' => $maxEquityData
                ],
                [
                    'name' => __('labels.balance_real'),
                    'data' => $balanceData
                ],
                [
                    'name' => __('labels.min_risk'),
                    'data' => $minEquityData
                ]
            ]
        ];

        // Cachear 3 minutos
        Cache::put($cacheKey, $result, now()->addMinutes(3));

        return $result;
    }


    /**
     * Calcula la fecha de corte según el timeframe
     */
    private function getCutoffDate(string $timeframe): ?Carbon
    {
        return match ($timeframe) {
            '1h' => now()->subMinutes(60),
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            default => null,
        };
    }
}
