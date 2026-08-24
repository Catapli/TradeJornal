<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    /**
     * Define the model's default state.
     *
     * Antes se elegían `account_id` y `trade_asset_id` de una lista fija de ids
     * ([1,2,3,4] y [1,2]), lo que solo funcionaba con la base de datos ya sembrada.
     * Ahora las relaciones se crean al vuelo, así que la factory sirve en tests.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $direction = fake()->randomElement(['long', 'short']);
        $entryPrice = fake()->randomFloat(5, 1.05000, 1.20000);

        // Winrate 65%
        $isWin = fake()->boolean(65);
        $size = fake()->randomFloat(2, 0.01, 2.00);
        $riskAmount = fake()->randomFloat(2, 20, 200);
        $riskPips = $riskAmount / $size / 10000;

        $rrTarget = fake()->randomFloat(2, 1.0, 5.0);
        $exitPrice = $direction === 'long'
            ? $entryPrice + ($isWin ? $riskPips * $rrTarget : -$riskPips * fake()->randomFloat(2, 0.3, 1.2))
            : $entryPrice - ($isWin ? $riskPips * $rrTarget : -$riskPips * fake()->randomFloat(2, 0.3, 1.2));

        $pnl = ($exitPrice - $entryPrice) * $size * 10000 * ($direction === 'long' ? 1 : -1);

        $entryTime = fake()->dateTimeBetween('-90 days', '-1 day');
        $durationMinutes = fake()->numberBetween(15, 4320);

        return [
            'account_id' => Account::factory(),
            'trade_asset_id' => TradeAsset::factory(),
            'strategy_id' => null,
            'ticket' => 'T' . fake()->unique()->numberBetween(100000, 999999),
            'direction' => $direction,
            'entry_price' => $entryPrice,
            'exit_price' => $exitPrice,
            'size' => $size,
            'pnl' => round($pnl, 2),
            'duration_minutes' => $durationMinutes,
            'entry_time' => $entryTime,
            'exit_time' => (clone $entryTime)->modify("+{$durationMinutes} minutes"),
            'notes' => fake()->randomElement([null, 'Breakout', 'RSI div', 'News']),
        ];
    }

    /** Trade ganador con un PnL exacto. */
    public function win(float $pnl): static
    {
        return $this->state(fn() => ['pnl' => $pnl]);
    }

    /** Trade perdedor. Se pasa el importe en positivo y se guarda en negativo. */
    public function loss(float $pnl): static
    {
        return $this->state(fn() => ['pnl' => -abs($pnl)]);
    }

    /** Fija la fecha de entrada (y recalcula la de salida en consecuencia). */
    public function enteredAt(string $date): static
    {
        return $this->state(function (array $attributes) use ($date) {
            $entry = new \DateTimeImmutable($date);

            return [
                'entry_time' => $entry,
                'exit_time' => $entry->modify('+' . ($attributes['duration_minutes'] ?? 60) . ' minutes'),
            ];
        });
    }
}
