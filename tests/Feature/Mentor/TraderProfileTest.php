<?php

declare(strict_types=1);

use App\Actions\Mentor\BuildTraderProfile;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * El perfil acumulado del trader (Fase 6 · P5).
 *
 * Es lo que convierte N auditorías sueltas en un mentor: qué error se repite,
 * cuánto y si va a mejor. De aquí sale el objetivo al que alguien se compromete
 * un mes entero, así que la muestra mínima no es una formalidad.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-08-28 10:00:00');

    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'active']);
    $this->activo = TradeAsset::factory()->create();
    $this->accion = app(BuildTraderProfile::class);

    $this->venganza = Mistake::create(['slug' => 'perfil_venganza', 'name' => 'Operar por venganza', 'color' => 'red', 'weight' => 5]);
    $this->tarde = Mistake::create(['slug' => 'perfil_tarde', 'name' => 'Entrada tardía', 'color' => 'amber', 'weight' => 3]);
});

afterEach(fn () => Carbon::setTestNow());

/** Operación cerrada con los errores que se le pasen. */
function opDePerfil(string $fecha, float $pnl, array $errores = []): Trade
{
    $trade = Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon::parse($fecha . ' 09:00:00'),
        'exit_time' => Carbon::parse($fecha . ' 10:00:00'),
        'duration_minutes' => 60,
    ]);

    if ($errores !== []) {
        $trade->mistakes()->attach(collect($errores)->pluck('id')->all());
    }

    return $trade;
}

/** N operaciones perdedoras seguidas con el mismo error, dentro de un mes. */
function opsConError(string $mes, int $cuantas, Mistake $error): void
{
    for ($i = 0; $i < $cuantas; $i++) {
        opDePerfil($mes . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), -120, [$error]);
    }
}

it('no dice nada por debajo de la muestra mínima de marcas', function () {
    opsConError('2026-07', BuildTraderProfile::MIN_MARKS - 1, $this->venganza);

    $perfil = $this->accion->execute($this->jordi->id);

    expect($perfil['has_enough_data'])->toBeFalse()
        ->and($perfil['missing_marks'])->toBe(1)
        ->and($perfil['mistakes'])->toBe([])
        ->and($perfil['top'])->toBeNull();
});

it('saca el perfil en cuanto hay marcas suficientes', function () {
    opsConError('2026-07', BuildTraderProfile::MIN_MARKS, $this->venganza);

    $perfil = $this->accion->execute($this->jordi->id);

    expect($perfil['has_enough_data'])->toBeTrue()
        ->and($perfil['marks'])->toBe(BuildTraderProfile::MIN_MARKS)
        ->and($perfil['top']['name'])->toBe('Operar por venganza');
});

it('declara siempre sobre cuántas operaciones está mirando', function () {
    // Sin la cobertura, un perfil sacado de doce repasadas de cien parece un
    // retrato y es una anécdota.
    opsConError('2026-07', 12, $this->venganza);
    opDePerfil('2026-07-20', 300);   // sin repasar

    $perfil = $this->accion->execute($this->jordi->id);

    expect($perfil['trades'])->toBe(13)
        ->and($perfil['reviewed'])->toBe(12)
        ->and($perfil['coverage'])->toBe(92.3);
});

it('elige el error que más se repite entre los caros, no el más caro de una vez', function () {
    // Un error de una sola vez que costó 900 € no se corrige con un hábito, y el
    // objetivo del mes es exactamente un hábito.
    opsConError('2026-07', 12, $this->venganza);      // 12 veces, 120 € cada una
    opDePerfil('2026-07-25', -900, [$this->tarde]);   // 1 vez, 900 €

    $perfil = $this->accion->execute($this->jordi->id);

    expect($perfil['top']['name'])->toBe('Operar por venganza')
        ->and($perfil['top']['count'])->toBe(12);
});

it('descarta como objetivo lo que solo ha pasado un par de veces', function () {
    opsConError('2026-07', 12, $this->venganza);
    opDePerfil('2026-08-02', -200, [$this->tarde]);
    opDePerfil('2026-08-03', -200, [$this->tarde]);

    $errores = collect($this->accion->execute($this->jordi->id)['mistakes']);

    // Aparece en el perfil (es información), pero no puede ser el objetivo.
    expect($errores->pluck('name'))->toContain('Entrada tardía')
        ->and($this->accion->execute($this->jordi->id)['top']['name'])->toBe('Operar por venganza');
});

it('compara por operaciones y no por número suelto', function () {
    // Julio: 20 operaciones, 12 con el error (60 %).
    opsConError('2026-07', 12, $this->venganza);
    for ($i = 13; $i <= 20; $i++) {
        opDePerfil('2026-07-' . $i, 100);
    }

    // Agosto: 5 operaciones, 3 con el error (60 %). Menos marcas, misma tasa:
    // no ha mejorado nada y el perfil no debe decir que sí.
    opsConError('2026-08', 3, $this->venganza);
    opDePerfil('2026-08-20', 100);
    opDePerfil('2026-08-21', 100);

    $top = $this->accion->execute($this->jordi->id)['top'];

    expect($top['this_month'])->toBe(3)
        ->and($top['rate_now'])->toBe(60.0)
        ->and($top['rate_before'])->toBe(60.0)
        ->and($top['trend'])->toBe('flat');
});

it('marca a mejor el error que baja de frecuencia', function () {
    opsConError('2026-07', 12, $this->venganza);   // julio: 12 de 12 = 100 %
    opDePerfil('2026-08-10', -120, [$this->venganza]);
    for ($i = 11; $i <= 20; $i++) {
        opDePerfil('2026-08-' . $i, 100);           // agosto: 1 de 11 = 9,1 %
    }

    expect($this->accion->execute($this->jordi->id)['top']['trend'])->toBe('down');
});

it('marca a peor el error que sube de frecuencia', function () {
    // Julio: 3 de 15 (20 %). Agosto: 9 de 10 (90 %).
    opsConError('2026-07', 3, $this->venganza);
    for ($i = 4; $i <= 15; $i++) {
        opDePerfil('2026-07-' . $i, 100);
    }
    opsConError('2026-08', 9, $this->venganza);
    opDePerfil('2026-08-20', 100);

    expect($this->accion->execute($this->jordi->id)['top']['trend'])->toBe('up');
});

it('no mira más allá de su ventana de meses', function () {
    // Lo de hace un año no dice nada de cómo opera hoy.
    $viejo = CarbonImmutable::parse('2026-08-28')->subMonths(BuildTraderProfile::MONTHS + 2);
    opsConError($viejo->format('Y-m'), 12, $this->venganza);

    expect($this->accion->execute($this->jordi->id)['has_enough_data'])->toBeFalse();
});

it('no mezcla el historial de otro usuario', function () {
    $otro = User::factory()->create();
    $suCuenta = Account::factory()->create(['user_id' => $otro->id, 'status' => 'active']);

    for ($i = 1; $i <= 12; $i++) {
        Trade::factory()->create([
            'account_id' => $suCuenta->id,
            'trade_asset_id' => $this->activo->id,
            'pnl' => -120,
            'entry_time' => Carbon::parse('2026-07-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 09:00:00'),
            'exit_time' => Carbon::parse('2026-07-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00'),
        ])->mistakes()->attach($this->venganza->id);
    }

    expect($this->accion->execute($this->jordi->id)['has_enough_data'])->toBeFalse();
});
