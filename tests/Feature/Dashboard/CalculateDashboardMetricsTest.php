<?php

use App\Actions\Dashboard\CalculateDashboardMetrics;
use App\Actions\Dashboard\DashboardTradeQuery;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;

/**
 * Los KPIs del dashboard, ya fuera del componente Livewire.
 *
 * Eran 398 líneas dentro de DashboardPage, así que probar una media exigía
 * arrancar Livewire entero. Ahora es filtros → array y se puede fijar el cálculo.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id]);
    $this->action = app(CalculateDashboardMetrics::class);

    $this->actingAs($this->jordi);
});

/** Filtros sobre todas las cuentas del usuario del test, sin rango de fechas. */
function filtros(string $desde = '', string $hasta = ''): DashboardTradeQuery
{
    return new DashboardTradeQuery(['all'], $desde, $hasta, test()->jordi->id);
}

/** Trade con PnL y fecha de cierre concretos. */
function trade(float $pnl, string $cierre = '2026-08-20 12:00:00', array $extra = []): Trade
{
    return Trade::factory()->create(array_merge([
        'account_id' => test()->cuenta->id,
        'pnl' => $pnl,
        'entry_time' => $cierre,
        'exit_time' => $cierre,
    ], $extra));
}

// ---------------------------------------------------------------------------
// KPIs
// ---------------------------------------------------------------------------

it('calcula el win rate sobre los trades filtrados', function () {
    trade(100);
    trade(50);
    trade(-30);
    trade(-20);

    $m = $this->action->execute(filtros());

    expect($m['winRateChartData']['rate'])->toEqual(50.0)
        ->and($m['winRateChartData']['count_wins'])->toBe(2)
        ->and($m['winRateChartData']['count_losses'])->toBe(2);
});

it('suma el PnL total del periodo', function () {
    trade(100);
    trade(-30);

    expect((float) $this->action->execute(filtros())['pnlTotal'])->toEqual(70.0);
});

it('calcula el profit factor como ganancia bruta entre pérdida bruta', function () {
    trade(200);
    trade(100);
    trade(-150); // 300 / 150 = 2.0

    expect($this->action->execute(filtros())['extraKpis']['profit_factor'])->toEqual(2.0);
});

it('deja el profit factor en null cuando no hay pérdidas', function () {
    // null = infinito. Devolver 0 lo pintaría como el peor sistema posible.
    trade(200);
    trade(100);

    expect($this->action->execute(filtros())['extraKpis']['profit_factor'])->toBeNull();
});

it('reporta el mejor y el peor trade del periodo', function () {
    trade(500);
    trade(20);
    trade(-300);

    $kpis = $this->action->execute(filtros())['extraKpis'];

    expect((float) $kpis['best_trade'])->toEqual(500.0)
        ->and((float) $kpis['worst_trade'])->toEqual(-300.0);
});

// ---------------------------------------------------------------------------
// Racha
// ---------------------------------------------------------------------------

it('cuenta la racha actual desde el trade más reciente hacia atrás', function () {
    trade(-50, '2026-08-18 10:00:00');
    trade(100, '2026-08-19 10:00:00');
    trade(80, '2026-08-20 10:00:00');

    expect($this->action->execute(filtros())['extraKpis']['streak'])
        ->toBe(['type' => 'win', 'count' => 2]);
});

it('corta la racha en un break-even', function () {
    trade(100, '2026-08-18 10:00:00');
    trade(0, '2026-08-19 10:00:00');
    trade(80, '2026-08-20 10:00:00');

    expect($this->action->execute(filtros())['extraKpis']['streak'])
        ->toBe(['type' => 'win', 'count' => 1]);
});

it('devuelve una racha vacía sin trades', function () {
    expect($this->action->execute(filtros())['extraKpis']['streak'])
        ->toBe(['type' => null, 'count' => 0]);
});

// ---------------------------------------------------------------------------
// Curva de capital y drawdown
// ---------------------------------------------------------------------------

it('acumula la curva de evolución empezando en cero', function () {
    trade(100, '2026-08-18 10:00:00');
    trade(-40, '2026-08-19 10:00:00');

    $curva = $this->action->execute(filtros())['evolutionChartData'];

    expect($curva['data'])->toBe([0, 100.0, 60.0])
        ->and($curva['is_positive'])->toBeTrue();
});

it('mide el max drawdown como la mayor caída pico a valle', function () {
    trade(100, '2026-08-18 10:00:00'); // pico 100
    trade(-70, '2026-08-19 10:00:00'); // valle 30 -> drawdown 70
    trade(200, '2026-08-20 10:00:00');

    expect($this->action->execute(filtros())['extraKpis']['max_drawdown'])->toEqual(70.0);
});

it('marca la curva como negativa cuando se cierra en pérdidas', function () {
    trade(-100, '2026-08-18 10:00:00');

    expect($this->action->execute(filtros())['evolutionChartData']['is_positive'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// Agrupaciones
// ---------------------------------------------------------------------------

it('cuenta días ganadores contra días perdedores, no trades', function () {
    trade(100, '2026-08-18 10:00:00');
    trade(-30, '2026-08-18 14:00:00'); // mismo día: neto +70, día ganador
    trade(-50, '2026-08-19 10:00:00');

    $dias = $this->action->execute(filtros())['dailyWinLossData'];

    expect($dias['count_wins'])->toBe(1)
        ->and($dias['count_losses'])->toBe(1);
});

it('desglosa el rendimiento por activo de mejor a peor', function () {
    $oro = TradeAsset::factory()->create(['name' => 'Oro']);
    $euro = TradeAsset::factory()->create(['name' => 'Euro']);

    trade(500, '2026-08-18 10:00:00', ['trade_asset_id' => $oro->id]);
    trade(-200, '2026-08-19 10:00:00', ['trade_asset_id' => $euro->id]);

    $desglose = $this->action->execute(filtros())['assetBreakdown'];

    expect($desglose['count'])->toBe(2)
        ->and($desglose['all'][0]['asset'])->toBe('Oro')
        ->and($desglose['all'][1]['asset'])->toBe('Euro');
});

it('devuelve el heatmap con los cinco días laborables y 24 horas', function () {
    trade(100, '2026-08-20 09:00:00'); // jueves

    $heatmap = $this->action->execute(filtros())['heatmapData'];

    expect($heatmap)->toHaveCount(5)
        ->and($heatmap[0]['data'])->toHaveCount(24);
});

// ---------------------------------------------------------------------------
// Comparativa con el periodo anterior
// ---------------------------------------------------------------------------

it('no compara si no hay rango de fechas activo', function () {
    trade(100);

    expect($this->action->execute(filtros())['comparison'])->toBeNull();
});

it('no compara si el periodo anterior no tiene trades', function () {
    trade(100, '2026-08-20 10:00:00');

    expect($this->action->execute(filtros('2026-08-19', '2026-08-21'))['comparison'])->toBeNull();
});

it('compara contra el periodo equivalente inmediatamente anterior', function () {
    trade(50, '2026-08-17 10:00:00');  // periodo previo (17 y 18)
    trade(200, '2026-08-20 10:00:00');  // periodo actual (19 al 21)

    $comparativa = $this->action->execute(filtros('2026-08-19', '2026-08-21'))['comparison'];

    expect($comparativa)->not->toBeNull()
        ->and($comparativa['pnl_prev'])->toEqual(50.0)
        ->and($comparativa['pnl_diff'])->toEqual(150.0);
});

// ---------------------------------------------------------------------------
// Aislamiento y filtros
// ---------------------------------------------------------------------------

it('no incluye trades de otro usuario', function () {
    trade(100);

    $ajena = Account::factory()->create(['user_id' => User::factory()->create()->id]);
    Trade::factory()->create(['account_id' => $ajena->id, 'pnl' => 9999]);

    expect((float) $this->action->execute(filtros())['pnlTotal'])->toEqual(100.0);
});

it('excluye las cuentas quemadas', function () {
    trade(100);

    $quemada = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'burned']);
    Trade::factory()->create(['account_id' => $quemada->id, 'pnl' => 9999]);

    expect((float) $this->action->execute(filtros())['pnlTotal'])->toEqual(100.0);
});

it('respeta el rango de fechas al sumar el PnL', function () {
    trade(100, '2026-08-20 10:00:00');
    trade(999, '2026-07-01 10:00:00');

    expect((float) $this->action->execute(filtros('2026-08-19', '2026-08-21'))['pnlTotal'])
        ->toEqual(100.0);
});

it('devuelve la estructura completa sin ningún trade', function () {
    // El dashboard no debe quedarse en blanco por una cuenta recién creada.
    $m = $this->action->execute(filtros());

    expect($m)->toHaveKeys([
        'winRateChartData', 'pnlTotal', 'pnlTotal_perc', 'avgPnLChartData', 'extraKpis',
        'comparison', 'assetBreakdown', 'dailyWinLossData', 'evolutionChartData',
        'dailyPnLChartData', 'heatmapData',
    ])
        ->and($m['assetBreakdown'])->toBe([])
        ->and($m['winRateChartData']['rate'])->toEqual(0);
});
