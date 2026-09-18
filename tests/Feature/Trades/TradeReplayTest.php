<?php

declare(strict_types=1);

use App\Livewire\TradeDetailModal;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Reproductor de la operación (Fase 6 · P8): la parte de servidor.
 *
 * El reproductor vive en el navegador, pero los cuatro precios que dibuja —
 * entrada, salida, MAE y MFE— salen de aquí. Si el evento deja de llevarlos, el
 * gráfico se sigue pintando y nadie se entera de que faltan dos líneas.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id]);
    $this->activo = TradeAsset::factory()->create();
});

/** Operación del usuario, con los precios de la excursión. */
function opDelReproductor(array $extra = []): Trade
{
    return Trade::factory()->create(array_merge([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'direction' => 'long',
        'entry_price' => 1.10000,
        'exit_price' => 1.10400,
        'mae_price' => 1.09850,
        'mfe_price' => 1.10520,
        'entry_time' => '2026-08-20 10:00:00',
        'exit_time' => '2026-08-20 12:30:00',
        'pnl' => 400,
    ], $extra));
}

/** Abre una operación en el modal y devuelve el test de Livewire. */
function abrirEnElModal(Trade $trade): Testable
{
    return Livewire::actingAs(test()->jordi)
        ->test(TradeDetailModal::class)
        ->call('loadTradeData', $trade->id);
}

it('manda el MAE y el MFE al abrir una operación', function () {
    $trade = opDelReproductor();

    abrirEnElModal($trade)->assertDispatched(
        'trade-selected',
        fn (string $evento, array $datos): bool => (float) $datos['mae'] === 1.0985
            && (float) $datos['mfe'] === 1.1052,
    );
});

it('sigue mandando el evento aunque no haya excursión guardada', function () {
    // Una operación importada por CSV no trae MAE ni MFE. El gráfico tiene que
    // abrirse igual, solo que sin esas dos líneas.
    $trade = opDelReproductor(['mae_price' => null, 'mfe_price' => null]);

    abrirEnElModal($trade)->assertDispatched(
        'trade-selected',
        fn (string $evento, array $datos): bool => $datos['mae'] === null
            && $datos['mfe'] === null,
    );
});

it('manda también la entrada, la salida y la dirección', function () {
    $trade = opDelReproductor(['direction' => 'short']);

    abrirEnElModal($trade)->assertDispatched(
        'trade-selected',
        fn (string $evento, array $datos): bool => (float) $datos['entry'] === 1.1
            && (float) $datos['exit'] === 1.104
            && $datos['direction'] === 'short',
    );
});

it('no manda ruta de velas si la operación no las tiene', function () {
    // Sin `chart_data_path` no hay nada que reproducir, y la barra del
    // reproductor no debe aparecer.
    $trade = opDelReproductor();

    abrirEnElModal($trade)->assertDispatched(
        'trade-selected',
        fn (string $evento, array $datos): bool => $datos['path'] === null,
    );
});

it('manda la ruta de velas cuando la operación sí las tiene', function () {
    $trade = opDelReproductor(['chart_data_path' => 'users/1/trades/T1/chart.json']);

    abrirEnElModal($trade)->assertDispatched(
        'trade-selected',
        fn (string $evento, array $datos): bool => $datos['path'] === route('trades.chart-data', $trade->id),
    );
});
