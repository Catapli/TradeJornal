<?php

declare(strict_types=1);

use App\Actions\Rules\CheckRulesCompliance;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\TradingRule;
use App\Models\User;

/**
 * Cuánto se respetaron las reglas adoptadas (Fase 5 · P10).
 *
 * La regla del bloque: solo se puntúa lo que la máquina puede comprobar sola. Lo
 * demás sale en el informe sin número, porque un cumplimiento inventado es peor
 * que ninguno — nadie lo puede discutir y todo el mundo se lo cree.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id]);
    $this->activo = TradeAsset::factory()->create();
    $this->action = app(CheckRulesCompliance::class);
});

function opRegla(string $entrada, array $attrs = []): Trade
{
    return Trade::factory()->create(array_merge([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'entry_time' => Carbon\Carbon::parse($entrada),
        'exit_time' => Carbon\Carbon::parse($entrada)->addHour(),
    ], $attrs));
}

function reglaDe(string $kind, array $config, ?int $accountId = null): TradingRule
{
    return TradingRule::create([
        'user_id' => test()->jordi->id,
        'account_id' => $accountId,
        'kind' => $kind,
        'text' => 'Regla de prueba',
        'config' => $config,
        'source_key' => 'test',
        'source_summary' => 'Hallazgo de prueba',
        'source_sample' => 12,
        'is_active' => true,
    ]);
}

function cumplimientoDe(array $tradeIds = []): array
{
    $trades = Trade::whereIn('id', $tradeIds ?: Trade::pluck('id')->all())
        ->with('mistakes')
        ->get();

    return test()->action->execute(test()->jordi->id, test()->cuenta->id, $trades);
}

it('el tope de operaciones al día se rompe por días, no por operaciones', function () {
    reglaDe(TradingRule::KIND_MAX_TRADES, ['limit' => 2]);

    // Lunes: tres operaciones, se rompe. Martes: dos, no se rompe.
    opRegla('2026-08-03 09:00');
    opRegla('2026-08-03 10:00');
    opRegla('2026-08-03 11:00');
    opRegla('2026-08-04 09:00');
    opRegla('2026-08-04 10:00');

    $regla = cumplimientoDe()[0];

    expect($regla['unit'])->toBe('days')
        ->and($regla['breaches'])->toBe(1)
        ->and($regla['scope'])->toBe(2)
        ->and($regla['rate'])->toBe(50.0);
});

it('la ventana horaria cuenta cada operación abierta fuera de ella', function () {
    reglaDe(TradingRule::KIND_TIME_WINDOW, ['from' => '09:00', 'to' => '13:59']);

    opRegla('2026-08-03 07:30');  // antes: rompe
    opRegla('2026-08-03 10:00');  // dentro
    opRegla('2026-08-03 12:00');  // dentro
    opRegla('2026-08-03 18:00');  // después: rompe

    $regla = cumplimientoDe()[0];

    expect($regla['unit'])->toBe('trades')
        ->and($regla['breaches'])->toBe(2)
        ->and($regla['scope'])->toBe(4)
        ->and($regla['rate'])->toBe(50.0);
});

it('el día evitado cuenta las operaciones de ese día de la semana', function () {
    // 5 = viernes en la numeración de Carbon (0 domingo).
    reglaDe(TradingRule::KIND_WEEKDAY, ['weekday' => 5]);

    opRegla('2026-08-07 10:00');  // viernes
    opRegla('2026-08-07 11:00');  // viernes
    opRegla('2026-08-06 10:00');  // jueves

    $regla = cumplimientoDe()[0];

    expect($regla['breaches'])->toBe(2)
        ->and($regla['scope'])->toBe(3);
});

it('el recordatorio de un error cuenta las operaciones marcadas con él', function () {
    $fomo = Mistake::create([
        'user_id' => $this->jordi->id,
        'slug' => 'fomo-regla',
        'name' => 'FOMO',
        'color' => 'amber',
        'weight' => 2,
    ]);

    reglaDe(TradingRule::KIND_MISTAKE, ['mistake_id' => $fomo->id]);

    $marcada = opRegla('2026-08-03 10:00');
    $marcada->mistakes()->sync([$fomo->id]);
    opRegla('2026-08-03 11:00');

    $regla = cumplimientoDe()[0];

    expect($regla['breaches'])->toBe(1)
        ->and($regla['scope'])->toBe(2)
        ->and($regla['rate'])->toBe(50.0);
});

it('una regla que no se puede comprobar sola sale sin número', function () {
    // Recordatorio adoptado desde una franja horaria no contigua: sin mistake_id
    // no hay nada que contar.
    reglaDe(TradingRule::KIND_MISTAKE, ['slot' => 'afternoon']);
    opRegla('2026-08-03 10:00');

    $regla = cumplimientoDe()[0];

    expect($regla['breaches'])->toBeNull()
        ->and($regla['unit'])->toBeNull()
        ->and($regla['rate'])->toBeNull();
});

it('coge las reglas globales y las de la cuenta, y deja fuera las apagadas', function () {
    reglaDe(TradingRule::KIND_MAX_TRADES, ['limit' => 1]);                       // global
    reglaDe(TradingRule::KIND_WEEKDAY, ['weekday' => 5], $this->cuenta->id);     // de la cuenta

    $otraCuenta = Account::factory()->create(['user_id' => $this->jordi->id]);
    reglaDe(TradingRule::KIND_WEEKDAY, ['weekday' => 1], $otraCuenta->id);       // de otra cuenta

    TradingRule::create([
        'user_id' => $this->jordi->id,
        'kind' => TradingRule::KIND_MAX_TRADES,
        'text' => 'Apagada',
        'config' => ['limit' => 1],
        'source_key' => 'test',
        'source_summary' => 'Hallazgo de prueba',
        'source_sample' => 12,
        'is_active' => false,
    ]);

    opRegla('2026-08-03 10:00');

    expect(cumplimientoDe())->toHaveCount(2);
});

it('sin operaciones no hay porcentaje que enseñar', function () {
    reglaDe(TradingRule::KIND_MAX_TRADES, ['limit' => 2]);

    $regla = $this->action->execute($this->jordi->id, $this->cuenta->id, collect());

    expect($regla[0]['breaches'])->toBe(0)
        ->and($regla[0]['rate'])->toBeNull();
});
