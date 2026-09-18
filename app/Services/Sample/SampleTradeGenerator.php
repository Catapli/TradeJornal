<?php

declare(strict_types=1);

namespace App\Services\Sample;

use App\Models\Account;
use App\Models\TradeAsset;
use Carbon\CarbonImmutable;

/**
 * Genera operaciones de ejemplo verosímiles para una cuenta.
 *
 * Lo usan dos sitios: el `DemoSeeder` de la demo pública y la cuenta de ejemplo
 * que puede crearse cualquier usuario desde la aplicación. Es determinista: con
 * la misma semilla salen exactamente las mismas operaciones, así que las
 * capturas de la landing y lo que ve el visitante no se despegan.
 */
class SampleTradeGenerator
{
    /** Catálogo de símbolos con su precio base y decimales. */
    private const SYMBOLS = [
        'EURUSD' => [1.08500, 5, 'Euro / Dólar', 'Forex'],
        'GBPUSD' => [1.27200, 5, 'Libra / Dólar', 'Forex'],
        'XAUUSD' => [2380.00, 2, 'Oro', 'Commodities'],
        'US30' => [39200.00, 2, 'Dow Jones 30', 'Indices'],
        'NAS100' => [18400.00, 2, 'Nasdaq 100', 'Indices'],
        'BTCUSDT' => [64500.00, 2, 'Bitcoin', 'Crypto'],
    ];

    private const NOTES = [
        'Entrada limpia sobre el rango, sin dudar.',
        'Me precipité: la vela ya venía extendida.',
        'Cerré antes de tiempo por miedo a devolverlo.',
        'Respeté el plan de principio a fin.',
        'Segunda operación del día después de perder. No debí entrar.',
    ];

    private const MOODS = ['confident', 'neutral', 'anxious', 'frustrated', 'focused'];

    /** Crea los símbolos del catálogo que falten y devuelve símbolo => id. */
    public function ensureAssets(): array
    {
        $assets = [];

        foreach (self::SYMBOLS as $symbol => [, , $name, $category]) {
            $assets[$symbol] = TradeAsset::firstOrCreate(
                ['symbol' => $symbol],
                ['name' => $name, 'category' => $category]
            )->id;
        }

        return $assets;
    }

    /**
     * Filas listas para `Trade::insert()`, ya normalizadas al objetivo del perfil.
     *
     * @param  array<string, int>  $assetIds  símbolo => trade_asset_id
     * @param  array<int, int>  $strategyIds
     * @return array{0: array<int, array<string, mixed>>, 1: float} filas y P&L acumulado
     */
    public function generate(Account $account, array $assetIds, array $strategyIds, SampleProfile $profile, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $symbols = array_keys($assetIds);
        $initialBalance = (float) $account->initial_balance ?: 1.0;

        $rows = [];
        $running = 0.0;

        for ($offset = $profile->days; $offset >= $profile->until; $offset--) {
            $day = $today->subDays($offset);

            if ($day->isWeekend() || mt_rand(1, 100) > $profile->density) {
                continue;
            }

            foreach (range(1, mt_rand(1, 3)) as $ignored) {
                $isWin = mt_rand(1, 100) <= $profile->winrate;
                $risk = $profile->risk * (mt_rand(70, 130) / 100);

                // Una pérdida ronda 1R: si de media fuera 0,75R la esperanza se
                // dispara y la curva deja de parecerse a una cuenta real.
                $pnl = round($isWin
                    ? $risk * $profile->rr * (mt_rand(50, 140) / 100)
                    : -$risk * (mt_rand(70, 120) / 100), 2);

                $running += $pnl;

                $symbol = $symbols[array_rand($symbols)];
                [$base, $decimals] = self::SYMBOLS[$symbol];

                $direction = mt_rand(0, 1) === 1 ? 'long' : 'short';
                $entryTime = $day->setTime(mt_rand(8, 17), mt_rand(0, 59));
                $duration = mt_rand(6, 260);

                $tick = $base * 0.0006;
                $entryPrice = round($base + $tick * mt_rand(-400, 400), $decimals);
                $move = $tick * (mt_rand(8, 60) / 10) * ($isWin ? 1 : -1);
                $exitPrice = round($entryPrice + ($direction === 'long' ? $move : -$move), $decimals);

                // Todo perdedor tuvo algo de recorrido a favor y todo ganador
                // estuvo en rojo antes: es lo que audita la IA con MAE/MFE.
                $adverse = $tick * (mt_rand(3, 35) / 10);
                $favourable = abs($exitPrice - $entryPrice) + $tick * (mt_rand(0, 40) / 10);

                $index = count($rows);

                $rows[] = [
                    'account_id' => $account->id,
                    'trade_asset_id' => $assetIds[$symbol],
                    'strategy_id' => $strategyIds === [] ? null : $strategyIds[array_rand($strategyIds)],
                    'ticket' => 'S' . $account->id . '-' . str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                    'position_id' => 'S' . $account->id . '-' . $index,
                    'direction' => $direction,
                    'entry_price' => $entryPrice,
                    'exit_price' => $exitPrice,
                    'size' => round(mt_rand(10, 200) / 100, 2),
                    'pnl' => $pnl,
                    'pnl_percentage' => round($pnl / $initialBalance * 100, 4),
                    'duration_minutes' => $duration,
                    'entry_time' => $entryTime,
                    'exit_time' => $entryTime->addMinutes($duration),
                    'mood' => self::MOODS[array_rand(self::MOODS)],
                    'mae_price' => round($direction === 'long' ? $entryPrice - $adverse : $entryPrice + $adverse, $decimals),
                    'mfe_price' => round($direction === 'long' ? $entryPrice + $favourable : $entryPrice - $favourable, $decimals),
                    'pips_traveled' => round($favourable / $tick, 2),
                    'notes' => mt_rand(1, 100) <= 30 ? self::NOTES[array_rand(self::NOTES)] : null,
                    'created_at' => $entryTime,
                    'updated_at' => $entryTime,
                ];
            }
        }

        return $this->normalizeToTarget($rows, $running, $profile, $initialBalance);
    }

    /**
     * Lleva el P&L acumulado al objetivo desplazando cada operación lo mismo.
     *
     * Escalar no sirve: con 150 operaciones manda la varianza, y una serie que
     * sale negativa por mala suerte no se arregla multiplicándola, porque el
     * signo no cambia. Un desplazamiento constante conserva la forma de la curva
     * y mantiene realistas los importes. Se limita a 0,6R para no deformarla.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function normalizeToTarget(array $rows, float $running, SampleProfile $profile, float $initialBalance): array
    {
        $count = count($rows);

        if ($count === 0) {
            return [$rows, 0.0];
        }

        $shift = ($profile->target - $running) / $count;
        $shift = max(-0.6 * $profile->risk, min(0.6 * $profile->risk, $shift));

        $total = 0.0;

        foreach ($rows as $i => $row) {
            $pnl = round((float) $row['pnl'] + $shift, 2);
            $rows[$i]['pnl'] = $pnl;
            $rows[$i]['pnl_percentage'] = round($pnl / $initialBalance * 100, 4);
            $total += $pnl;
        }

        return [$rows, round($total, 2)];
    }
}
