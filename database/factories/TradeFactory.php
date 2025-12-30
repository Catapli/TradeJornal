<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Trade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountIds = [1, 2, 3, 4];
        $assetIds = [1, 2];

        $direction = fake()->randomElement(['long', 'short']);
        $entryPrice = fake()->randomFloat(5, 1.05000, 1.20000);

        // Winrate 65%
        $isWin = fake()->boolean(65);
        $size = fake()->randomFloat(2, 0.01, 2.00);
        $riskAmount = fake()->randomFloat(2, 20, 200);
        $riskPips = $riskAmount / $size / 10000;

        // ← FIX: RR máximo 1:5 (5.00)
        $rrTarget = fake()->randomFloat(2, 1.0, 5.0);
        $exitPrice = $direction === 'long'
            ? $entryPrice + ($isWin ? $riskPips * $rrTarget : -$riskPips * fake()->randomFloat(2, 0.3, 1.2))
            : $entryPrice - ($isWin ? $riskPips * $rrTarget : -$riskPips * fake()->randomFloat(2, 0.3, 1.2));

        $pnl = ($exitPrice - $entryPrice) * $size * 10000 * ($direction === 'long' ? 1 : -1);
        $pnlPct = min(500, abs($pnl / $riskAmount * 100)); // ← MAX 500%

        return [
            'account_id' => fake()->randomElement($accountIds),
            'trade_asset_id' => fake()->randomElement($assetIds),
            'strategy_id' => 1,
            'ticket' => 'T' . fake()->unique()->numberBetween(100000, 999999),
            'direction' => $direction,
            'entry_price' => $entryPrice,
            'exit_price' => round($exitPrice, 5), // ← ROUND 5 decimales
            'size' => $size,
            'pnl' => round($pnl, 2),
            'pnl_pct' => round($pnlPct, 2), // ← MAX 500.00
            'rr_ratio' => round(abs($pnl / $riskAmount), 2), // ← MAX 5.00
            'risk_amount' => round($riskAmount, 2),
            'reward_amount' => round(abs($pnl), 2),
            'duration_minutes' => fake()->numberBetween(15, 4320),
            'status' => $isWin ? 'closed' : fake()->randomElement(['closed', 'breakeven']),
            'entry_time' => fake()->dateTimeBetween('-90 days', 'now'),
            'exit_time' => fake()->dateTimeBetween('-90 days', 'now'),
            'notes' => fake()->randomElement([null, 'Breakout', 'RSI div', 'News']),
        ];
    }
}
