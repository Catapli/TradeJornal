<?php

declare(strict_types=1);

use App\Actions\Accounts\BuildDailyResults;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use Carbon\Carbon;

/**
 * El día operado, tal como lo cuenta la fase (Fase 6 · P3).
 *
 * De aquí sale la muestra del Monte Carlo, así que un error de agrupación no da
 * un número raro: da una probabilidad creíble y equivocada.
 */
beforeEach(function () {
    $this->cuenta = Account::factory()->create();
    $this->activo = TradeAsset::factory()->create();
    $this->accion = app(BuildDailyResults::class);
});

/** Una operación con entrada y salida a medida. */
function opDiaria(string $entrada, string $salida, float $pnl): Trade
{
    return Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon::parse($entrada),
        'exit_time' => Carbon::parse($salida),
    ]);
}

it('agrupa por el día de cierre, no por el de apertura', function () {
    // Una operación abierta el viernes y cerrada el lunes es resultado del lunes:
    // el PnL se realiza al cerrar. Es el mismo criterio que el semáforo de la
    // cuenta, y si aquí cambiara, las dos pantallas se contradirían.
    opDiaria('2026-01-09 22:00:00', '2026-01-12 08:00:00', 300);

    $dias = $this->accion->execute($this->cuenta);

    expect($dias)->toHaveCount(1)
        ->and($dias[0]['date'])->toBe('2026-01-12');
});

it('suma el resultado del día y cuenta sus operaciones', function () {
    opDiaria('2026-01-12 09:00:00', '2026-01-12 10:00:00', 200);
    opDiaria('2026-01-12 11:00:00', '2026-01-12 12:00:00', -50);
    opDiaria('2026-01-13 09:00:00', '2026-01-13 10:00:00', 75);

    $dias = $this->accion->execute($this->cuenta);

    expect($dias)->toHaveCount(2)
        ->and($dias[0])->toBe(['date' => '2026-01-12', 'pnl' => 150.0, 'trades' => 2])
        ->and($dias[1])->toBe(['date' => '2026-01-13', 'pnl' => 75.0, 'trades' => 1]);
});

it('no mezcla las operaciones de otra cuenta', function () {
    $otra = Account::factory()->create();
    opDiaria('2026-01-12 09:00:00', '2026-01-12 10:00:00', 200);
    Trade::factory()->create([
        'account_id' => $otra->id,
        'trade_asset_id' => $this->activo->id,
        'pnl' => 999,
        'entry_time' => Carbon::parse('2026-01-12 09:00:00'),
        'exit_time' => Carbon::parse('2026-01-12 10:00:00'),
    ]);

    expect($this->accion->execute($this->cuenta)->pluck('pnl')->all())->toBe([200.0]);
});

it('devuelve los días en orden cronológico', function () {
    opDiaria('2026-02-03 09:00:00', '2026-02-03 10:00:00', 10);
    opDiaria('2026-01-05 09:00:00', '2026-01-05 10:00:00', 20);
    opDiaria('2026-01-20 09:00:00', '2026-01-20 10:00:00', 30);

    expect($this->accion->execute($this->cuenta)->pluck('date')->all())
        ->toBe(['2026-01-05', '2026-01-20', '2026-02-03']);
});
