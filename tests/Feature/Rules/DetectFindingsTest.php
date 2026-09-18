<?php

declare(strict_types=1);

use App\Actions\Rules\DetectFindings;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\TradingRule;
use App\Models\User;

/**
 * De datos a frases que se pueden cumplir.
 *
 * Lo que se protege aquí es lo que hundió al sistema R: **no enseñar como
 * conclusión lo que sale de una muestra ridícula**. Un hallazgo se convierte en
 * una regla que el usuario va a obedecer; si sale de tres días, le estamos
 * cambiando la operativa con una casualidad.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'status' => 'active',
        'is_sample' => false,
        'initial_balance' => 10000,
    ]);
    $this->activo = TradeAsset::factory()->create();
    $this->action = app(DetectFindings::class);
});

function opHallazgo(float $pnl, string $cuando): Trade
{
    return Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon\Carbon::parse($cuando),
        'exit_time' => Carbon\Carbon::parse($cuando)->addHour(),
    ]);
}

/** @return Illuminate\Support\Collection<int, Trade> */
function todas(): Illuminate\Support\Collection
{
    return Trade::with('mistakes')->get();
}

it('no dice nada si no hay operaciones suficientes', function () {
    // Por debajo del mínimo no hay hallazgo, por muy claro que parezca el patrón.
    for ($i = 0; $i < DetectFindings::MIN_SAMPLE - 1; $i++) {
        opHallazgo(-200, '2026-08-1' . ($i % 9 + 1) . ' 15:00:00');
    }

    expect($this->action->execute(todas()))->toBe([]);
});

it('señala la franja horaria que da dinero en contra', function () {
    // 20 operaciones perdedoras por la tarde, 20 ganadoras por la mañana.
    for ($i = 1; $i <= 20; $i++) {
        $dia = str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT);
        opHallazgo(-150, "2026-08-{$dia} 15:00:00");
        opHallazgo(200, "2026-08-{$dia} 09:00:00");
    }

    $hallazgo = collect($this->action->execute(todas()))->firstWhere('key', 'time_of_day');

    expect($hallazgo)->not->toBeNull()
        ->and($hallazgo['params']['slot'])->toBe('afternoon')
        ->and($hallazgo['cost'])->toBe(3000.0)
        ->and($hallazgo['sample'])->toBe(20);
});

it('encuentra a partir de qué operación del día se tuerce todo', function () {
    // Cada día: dos ganadoras y luego perdedoras.
    for ($d = 1; $d <= 10; $d++) {
        $dia = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
        opHallazgo(100, "2026-08-{$dia} 09:00:00");
        opHallazgo(100, "2026-08-{$dia} 10:00:00");
        opHallazgo(-200, "2026-08-{$dia} 11:00:00");
        opHallazgo(-200, "2026-08-{$dia} 12:00:00");
    }

    $hallazgo = collect($this->action->execute(todas()))->firstWhere('key', 'trades_per_day');

    expect($hallazgo)->not->toBeNull()
        ->and($hallazgo['params']['position'])->toBe(3)
        ->and($hallazgo['config']['limit'])->toBe(2)
        ->and($hallazgo['kind'])->toBe(TradingRule::KIND_MAX_TRADES);
});

it('señala el día de la semana caro', function () {
    // 2026-08-06 es jueves; se repite el jueves durante seis semanas.
    for ($s = 0; $s < 6; $s++) {
        $jueves = Carbon\Carbon::parse('2026-08-06')->addWeeks($s);

        for ($i = 0; $i < 3; $i++) {
            opHallazgo(-120, $jueves->format('Y-m-d') . ' 1' . $i . ':00:00');
        }
    }

    $hallazgo = collect($this->action->execute(todas()))->firstWhere('key', 'weekday');

    expect($hallazgo)->not->toBeNull()
        ->and($hallazgo['config']['weekday'])->toBe(4)
        ->and($hallazgo['sample'])->toBe(18);
});

it('señala el error más caro con su muestra', function () {
    $error = Mistake::create([
        'user_id' => $this->jordi->id,
        'slug' => 'held_loser',
        'name' => 'Aguantar la perdedora',
        'color' => 'rose',
        'weight' => 3,
    ]);

    for ($i = 1; $i <= 14; $i++) {
        $dia = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $trade = opHallazgo(-110, "2026-08-{$dia} 09:00:00");
        $trade->mistakes()->sync([$error->id]);
    }

    $hallazgo = collect($this->action->execute(todas()))->firstWhere('key', 'mistake_cost');

    expect($hallazgo)->not->toBeNull()
        ->and($hallazgo['cost'])->toBe(1540.0)
        ->and($hallazgo['sample'])->toBe(14)
        ->and($hallazgo['config']['mistake_id'])->toBe($error->id);
});

it('no inventa un patrón cuando ganas dinero', function () {
    for ($i = 1; $i <= 20; $i++) {
        $dia = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        opHallazgo(300, "2026-08-{$dia} 15:00:00");
    }

    expect($this->action->execute(todas()))->toBe([]);
});

it('pone primero lo que más dinero cuesta', function () {
    for ($d = 1; $d <= 12; $d++) {
        $dia = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
        opHallazgo(50, "2026-08-{$dia} 09:00:00");
        opHallazgo(-600, "2026-08-{$dia} 15:00:00");
        opHallazgo(-100, "2026-08-{$dia} 16:00:00");
    }

    $hallazgos = $this->action->execute(todas());

    expect($hallazgos)->not->toBeEmpty();

    $costes = array_column($hallazgos, 'cost');
    expect($costes)->toBe(collect($costes)->sortDesc()->values()->all());
});
