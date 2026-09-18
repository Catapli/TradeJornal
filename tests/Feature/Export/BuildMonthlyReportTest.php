<?php

declare(strict_types=1);

use App\Actions\Dashboard\CalculateDashboardMetrics;
use App\Actions\Dashboard\DashboardTradeQuery;
use App\Actions\Export\BuildMonthlyReport;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * El informe mensual (Fase 5 · P10).
 *
 * Lo que se protege aquí es que el informe no invente: recorta el mes natural,
 * compara contra el mes pasado de verdad y saca los mismos números que el
 * dashboard para ese mismo periodo. Dos verdades distintas sobre el mismo mes es
 * exactamente lo que no puede pasar cuando el PDF se le enseña a una prop firm.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'name' => 'FTMO 100k',
        'currency' => 'EUR',
    ]);
    $this->activo = TradeAsset::factory()->create();
    $this->action = app(BuildMonthlyReport::class);
    $this->agosto = CarbonImmutable::parse('2026-08-15');
});

function opInforme(string $cierre, float $pnl, ?Account $cuenta = null): Trade
{
    $salida = Carbon\Carbon::parse($cierre);

    return Trade::factory()->create([
        'account_id' => ($cuenta ?? test()->cuenta)->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => (clone $salida)->subHour(),
        'exit_time' => $salida,
    ]);
}

it('recorta el mes natural y deja fuera lo que cae al lado', function () {
    opInforme('2026-07-31 23:00', 500);   // julio
    opInforme('2026-08-01 08:00', 100);
    opInforme('2026-08-31 22:00', 200);
    opInforme('2026-09-01 09:00', 900);   // septiembre

    $informe = $this->action->execute($this->jordi, $this->agosto, $this->cuenta);

    expect($informe['trades_count'])->toBe(2)
        ->and((float) $informe['metrics']['pnlTotal'])->toBe(300.0)
        ->and($informe['month']['start'])->toBe('2026-08-01')
        ->and($informe['month']['end'])->toBe('2026-08-31');
});

it('saca las mismas cifras que el dashboard para ese mismo mes', function () {
    opInforme('2026-08-05 10:00', 400);
    opInforme('2026-08-06 10:00', -150);
    opInforme('2026-08-07 10:00', 50);

    $informe = $this->action->execute($this->jordi, $this->agosto, $this->cuenta);

    $delDashboard = app(CalculateDashboardMetrics::class)->execute(
        new DashboardTradeQuery([$this->cuenta->id], '2026-08-01', '2026-08-31', $this->jordi->id)
    );

    expect((float) $informe['metrics']['pnlTotal'])->toBe((float) $delDashboard['pnlTotal'])
        ->and($informe['metrics']['winRateChartData']['rate'])->toBe($delDashboard['winRateChartData']['rate'])
        ->and($informe['metrics']['extraKpis']['profit_factor'])->toBe($delDashboard['extraKpis']['profit_factor']);
});

it('compara contra el mes pasado de verdad, no contra los últimos treinta días', function () {
    opInforme('2026-07-10 10:00', 1000);
    opInforme('2026-08-20 10:00', 250);

    $informe = $this->action->execute($this->jordi, $this->agosto, $this->cuenta);

    expect($informe['previous'])->not->toBeNull()
        ->and($informe['previous']['pnl'])->toBe(1000.0)
        ->and($informe['previous']['trades'])->toBe(1)
        ->and($informe['previous']['label'])->toContain('2026');
});

it('sin mes anterior no hay comparativa que enseñar', function () {
    opInforme('2026-08-20 10:00', 250);

    expect($this->action->execute($this->jordi, $this->agosto, $this->cuenta)['previous'])->toBeNull();
});

it('coloca cada día en su casilla del calendario', function () {
    opInforme('2026-08-05 10:00', 120);
    opInforme('2026-08-05 15:00', -20);
    opInforme('2026-08-19 10:00', 75);

    $informe = $this->action->execute($this->jordi, $this->agosto, $this->cuenta);

    $dias = collect($informe['calendar'])
        ->flatten(1)
        ->filter()
        ->keyBy('date');

    expect($dias['2026-08-05']['pnl'])->toBe(100.0)
        ->and($dias['2026-08-05']['trades'])->toBe(2)
        ->and($dias['2026-08-19']['pnl'])->toBe(75.0)
        ->and($dias['2026-08-06']['trades'])->toBe(0)
        // Agosto de 2026 empieza en sábado: la primera semana trae cinco huecos.
        ->and($informe['calendar'][0])->toHaveCount(7)
        ->and($dias)->toHaveCount(31);
});

it('informa de una cuenta quemada si la eliges, y la deja fuera del "todas"', function () {
    $quemada = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'status' => 'burned',
        'currency' => 'EUR',
    ]);

    opInforme('2026-08-10 10:00', -800, $quemada);

    $suya = $this->action->execute($this->jordi, $this->agosto, $quemada);
    $todas = $this->action->execute($this->jordi, $this->agosto, null);

    expect($suya['trades_count'])->toBe(1)
        ->and($suya['has_activity'])->toBeTrue()
        ->and($todas['trades_count'])->toBe(0);
});

it('trae el coste de los errores con su cobertura al lado', function () {
    $fomo = Mistake::create([
        'user_id' => $this->jordi->id,
        'slug' => 'fomo-informe',
        'name' => 'FOMO',
        'color' => 'amber',
        'weight' => 2,
    ]);

    opInforme('2026-08-10 10:00', 300);
    $marcada = opInforme('2026-08-11 10:00', -200);
    $marcada->mistakes()->sync([$fomo->id]);

    $coste = $this->action->execute($this->jordi, $this->agosto, $this->cuenta)['mistake_cost'];

    expect($coste['cost'])->toBe(200.0)
        ->and($coste['real_pnl'])->toBe(100.0)
        ->and($coste['pnl_without'])->toBe(300.0)
        ->and($coste['trades_total'])->toBe(2)
        ->and($coste['coverage'])->toBe(50.0);
});

it('un mes sin operaciones se marca como vacío en vez de imprimir ceros', function () {
    $informe = $this->action->execute($this->jordi, $this->agosto, $this->cuenta);

    expect($informe['has_activity'])->toBeFalse()
        ->and($informe['trades_count'])->toBe(0);
});

it('rotula la divisa de la cuenta, y ninguna cuando hay varias mezcladas', function () {
    $enDolares = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'currency' => 'USD',
    ]);

    opInforme('2026-08-10 10:00', 100);
    opInforme('2026-08-11 10:00', 100, $enDolares);

    expect($this->action->execute($this->jordi, $this->agosto, $this->cuenta)['currency'])->toBe('EUR')
        ->and($this->action->execute($this->jordi, $this->agosto, null)['currency'])->toBeNull();
});
