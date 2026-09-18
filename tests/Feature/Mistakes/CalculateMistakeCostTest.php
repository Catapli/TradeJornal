<?php

declare(strict_types=1);

use App\Actions\Mistakes\CalculateMistakeCost;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;

/**
 * El coste de los errores.
 *
 * Lo que se protege aquí es que el número signifique lo que dice: la diferencia
 * entre lo que ganaste y lo que habrías ganado sin esas operaciones. Y que nunca
 * salga solo, sino con la cobertura al lado.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'is_sample' => false,
        'initial_balance' => 10000,
    ]);
    $this->activo = TradeAsset::factory()->create();
    $this->action = app(CalculateMistakeCost::class);
});

function opCoste(float $pnl, array $errores = [], ?string $hora = null, bool $repasada = false): Trade
{
    $trade = Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => $hora ? Carbon\Carbon::parse($hora) : now()->subDay(),
        'mistakes_reviewed_at' => $repasada ? now() : null,
    ]);

    if ($errores !== []) {
        $trade->mistakes()->sync($errores);
    }

    return $trade->fresh();
}

function errorCoste(string $slug): Mistake
{
    return Mistake::create([
        'user_id' => test()->jordi->id,
        'slug' => $slug,
        'name' => $slug,
        'color' => 'rose',
        'weight' => 2,
    ]);
}

it('el coste es lo que habrías ganado sin esas operaciones', function () {
    $revenge = errorCoste('revenge');

    opCoste(500);                  // limpia
    opCoste(-300, [$revenge->id]); // marcada

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    expect($m['real_pnl'])->toBe(200.0)
        ->and($m['pnl_without'])->toBe(500.0)
        ->and($m['cost'])->toBe(300.0);
});

it('una marcada que salió bien resta al coste', function () {
    // El error que a veces sale bien es el que más cuesta dejar; el número tiene
    // que reflejarlo en vez de contar solo las pérdidas.
    $fomo = errorCoste('fomo');

    opCoste(-300, [$fomo->id]);
    opCoste(100, [$fomo->id]);

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    expect($m['cost'])->toBe(200.0);
});

it('dice sobre cuántas operaciones está calculado', function () {
    $err = errorCoste('no_setup');

    opCoste(-100, [$err->id]);      // marcada = repasada
    opCoste(-50, [], null, true);   // repasada y limpia
    opCoste(-40);                   // sin repasar
    opCoste(-30);                   // sin repasar

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    expect($m['trades_total'])->toBe(4)
        ->and($m['reviewed'])->toBe(2)
        ->and($m['coverage'])->toBe(50.0)
        ->and($m['marked_trades'])->toBe(1);
});

it('cuenta como pendientes solo las perdedoras sin repasar', function () {
    // Nadie repasa trescientas ganadoras: la cola solo tiene sentido con las que
    // costaron dinero.
    opCoste(-100);
    opCoste(-80);
    opCoste(400);                  // ganadora sin repasar: no cuenta
    opCoste(-20, [], null, true);  // ya repasada: no cuenta

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    expect($m['pending_review'])->toBe(2);
});

it('ordena los errores del más caro al menos caro', function () {
    $caro = errorCoste('held_loser');
    $barato = errorCoste('early_exit');

    opCoste(-800, [$caro->id]);
    opCoste(-100, [$barato->id]);

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    expect($m['by_mistake'][0]['slug'])->toBe('held_loser')
        ->and($m['by_mistake'][0]['cost'])->toBe(800.0)
        ->and($m['by_mistake'][0]['avg_cost'])->toBe(800.0)
        ->and($m['by_mistake'][1]['slug'])->toBe('early_exit');
});

it('cuenta entera la operación en cada uno de sus errores', function () {
    // Repartir el coste entre los errores de una misma operación sería inventarse
    // una proporción, así que se cuenta entera en los dos y la interfaz lo avisa.
    $a = errorCoste('fomo');
    $b = errorCoste('no_setup');

    opCoste(-200, [$a->id, $b->id]);

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    expect($m['cost'])->toBe(200.0)
        ->and($m['by_mistake'])->toHaveCount(2)
        ->and($m['by_mistake'][0]['cost'])->toBe(200.0)
        ->and($m['by_mistake'][1]['cost'])->toBe(200.0);
});

it('reparte el coste por franja horaria', function () {
    $err = errorCoste('fomo');

    opCoste(-100, [$err->id], '2026-08-20 09:30:00');
    opCoste(-400, [$err->id], '2026-08-20 15:00:00');

    $m = $this->action->fromTrades(Trade::with('mistakes')->get());

    $porFranja = collect($m['by_slot'])->keyBy('slot');

    expect($porFranja['afternoon']['cost'])->toBe(400.0)
        ->and($porFranja['morning']['cost'])->toBe(100.0)
        ->and($m['by_slot'][0]['slot'])->toBe('afternoon');
});

it('no inventa nada cuando no hay operaciones', function () {
    $m = $this->action->fromTrades(collect());

    expect($m['trades_total'])->toBe(0)
        ->and($m['cost'])->toBe(0.0)
        ->and($m['coverage'])->toBe(0.0)
        ->and($m['by_mistake'])->toBe([]);
});
