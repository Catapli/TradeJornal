<?php

declare(strict_types=1);

use App\Livewire\DashboardPage;
use App\Livewire\ReportsPage;
use App\Livewire\ReviewPage;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use App\Services\TradingAnalysisService;
use Livewire\Livewire;

/**
 * Dónde se ve el coste de los errores y qué se puede hacer con él.
 *
 * Portada y Laboratorio tienen que decir lo mismo sobre las mismas operaciones,
 * y el escenario «sin las operaciones con este error» tiene que dar exactamente
 * el número que anuncia la tarjeta.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'is_sample' => false,
        'status' => 'active',
        'initial_balance' => 10000,
    ]);
    $this->activo = TradeAsset::factory()->create();

    $this->error = Mistake::create([
        'user_id' => $this->jordi->id,
        'slug' => 'held_loser',
        'name' => 'Aguantar la perdedora',
        'color' => 'rose',
        'weight' => 3,
    ]);
});

function opConError(float $pnl): Trade
{
    $trade = Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
    ]);

    $trade->mistakes()->sync([test()->error->id]);

    return $trade->fresh();
}

function opLimpia(float $pnl): Trade
{
    return Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'mistakes_reviewed_at' => now(),
    ]);
}

it('la portada calcula el coste con los filtros del panel', function () {
    opLimpia(500);
    opConError(-300);

    $component = Livewire::actingAs($this->jordi)->test(DashboardPage::class);

    expect($component->instance()->mistakeCost['cost'])->toBe(300.0)
        ->and($component->instance()->mistakeCost['pnl_without'])->toBe(500.0);
});

it('la portada pinta la tarjeta con el titular', function () {
    opConError(-300);

    $this->actingAs($this->jordi)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('mistake_cost.title'));
});

it('portada y Laboratorio dicen lo mismo', function () {
    opLimpia(500);
    opConError(-300);
    opConError(120);

    $portada = Livewire::actingAs($this->jordi)->test(DashboardPage::class)->instance()->mistakeCost;
    $laboratorio = Livewire::actingAs($this->jordi)->test(ReportsPage::class)->instance()->mistakeCost;

    expect($laboratorio['cost'])->toBe($portada['cost'])
        ->and($laboratorio['coverage'])->toBe($portada['coverage']);
});

it('el escenario quita justo las operaciones con ese error', function () {
    opLimpia(500);
    opConError(-300);

    $trades = Trade::with('mistakes')->get();

    $sinError = app(TradingAnalysisService::class)
        ->applyScenarios($trades, ['exclude_mistakes' => [$this->error->id]]);

    expect($sinError)->toHaveCount(1)
        ->and((float) $sinError->sum('pnl'))->toBe(500.0);
});

it('el escenario cuenta como activo y coincide con el coste anunciado', function () {
    opLimpia(500);
    opConError(-300);

    $component = Livewire::actingAs($this->jordi)
        ->test(ReportsPage::class)
        ->set('scenarios.exclude_mistakes', [$this->error->id]);

    $coste = $component->instance()->mistakeCost;
    $curva = $component->instance()->simulatedData;

    // La simulación tiene que terminar donde dice la tarjeta: sin las marcadas.
    expect($curva['curve'])->not->toBeEmpty()
        ->and($coste['pnl_without'])->toBe(500.0);
});

it('el repaso sube la cobertura', function () {
    Trade::factory()->create([
        'account_id' => $this->cuenta->id,
        'trade_asset_id' => $this->activo->id,
        'pnl' => -80,
        'mistakes_reviewed_at' => null,
    ]);

    opConError(-300);

    $antes = Livewire::actingAs($this->jordi)->test(DashboardPage::class)->instance()->mistakeCost;
    expect($antes['coverage'])->toBe(50.0);

    // La pantalla de repaso arranca en la primera pendiente, que es la única.
    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->call('markClean');

    $despues = Livewire::actingAs($this->jordi)->test(DashboardPage::class)->instance()->mistakeCost;
    expect($despues['coverage'])->toBe(100.0);
});

it('el Laboratorio pinta el escenario y la entrada al repaso', function () {
    opConError(-300);

    $this->actingAs($this->jordi)
        ->get(route('reports'))
        ->assertOk()
        ->assertSee(__('mistake_cost.scenario_title'))
        ->assertSee(__('mistake_cost.review.title'));
});
