<?php

declare(strict_types=1);

use App\Actions\Accounts\SimulateChallenge;
use App\Models\Account;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use App\Models\Trade;
use App\Models\TradeAsset;
use Carbon\Carbon;

/**
 * Monte Carlo sobre la distribución propia de la cuenta (Fase 6 · P3).
 *
 * Lo que se protege aquí es lo mismo que hundió al sistema R: **no dar por
 * conclusión lo que sale de una muestra ridícula**. Una probabilidad de pasar el
 * challenge no es un adorno; es el número con el que alguien decide cuánto
 * arriesga mañana.
 */
beforeEach(function () {
    $this->nivel = ProgramLevel::factory()->create();
    // Un único activo compartido: TradeAssetFactory usa unique() sobre seis
    // símbolos y se agota si cada operación crea el suyo.
    $this->activo = TradeAsset::factory()->create();
    $this->accion = app(SimulateChallenge::class);
});

/** Cuenta de 10.000 con una fase a medida. */
function cuentaEnFase(array $fase = [], array $cuenta = []): Account
{
    $objetivo = ProgramObjective::factory()->create(array_merge([
        'program_level_id' => test()->nivel->id,
        'profit_target_percent' => 8,
        'max_daily_loss_percent' => 5,
        'max_total_loss_percent' => 10,
        'min_trading_days' => 0,
    ], $fase));

    return Account::factory()->create(array_merge([
        'program_level_id' => test()->nivel->id,
        'program_objective_id' => $objetivo->id,
        'initial_balance' => 10000,
        'current_balance' => 10000,
        'current_equity' => 10000,
    ], $cuenta));
}

/**
 * Un día operado con un resultado exacto.
 *
 * La operación entra y sale el mismo día a propósito: `BuildDailyResults` agrupa
 * por `exit_time`, y la duración por defecto de la factory llega a tres días.
 */
function diaCerrado(Account $cuenta, string $fecha, float $pnl): Trade
{
    return Trade::factory()->create([
        'account_id' => $cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon::parse($fecha . ' 09:00:00'),
        'exit_time' => Carbon::parse($fecha . ' 10:00:00'),
        'duration_minutes' => 60,
    ]);
}

/** N días consecutivos con el mismo resultado, empezando el 2026-01-05 (lunes). */
function diasCerrados(Account $cuenta, int $cuantos, float $pnl): void
{
    $dia = Carbon::parse('2026-01-05');

    for ($i = 0; $i < $cuantos; $i++) {
        diaCerrado($cuenta, $dia->copy()->addDays($i)->toDateString(), $pnl);
    }
}

it('no da ninguna probabilidad por debajo de la muestra mínima', function () {
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, SimulateChallenge::MIN_SAMPLE_DAYS - 1, 100);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['has_enough_data'])->toBeFalse()
        ->and($r['missing_sample_days'])->toBe(1)
        ->and($r)->not->toHaveKey('pass');
});

it('sigue diciendo cuánto falta aunque no haya muestra para simular', function () {
    // «Cuánto queda para el objetivo» es una resta, no una estimación: callarla
    // por falta de muestra sería esconder un dato exacto.
    $cuenta = cuentaEnFase(['min_trading_days' => 4], ['current_balance' => 10300]);
    diasCerrados($cuenta, 3, 100);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['has_enough_data'])->toBeFalse()
        ->and($r['target_left'])->toBe(500.0)   // 8% de 10.000 = 800, llevas 300
        ->and($r['missing_profitable_days'])->toBe(1); // 3 días rentables de 4
});

it('simula en cuanto llega a la muestra mínima', function () {
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, SimulateChallenge::MIN_SAMPLE_DAYS, 100);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['has_enough_data'])->toBeTrue()
        ->and($r['sample_days'])->toBe(SimulateChallenge::MIN_SAMPLE_DAYS)
        ->and($r['runs'])->toBe(SimulateChallenge::RUNS)
        ->and($r['pass'] + $r['daily_breach'] + $r['total_breach'] + $r['timeout'])
        ->toEqualWithDelta(100.0, 0.2);
});

it('da por pasada la fase cuando todos los días ganan', function () {
    // 25 días de +100 sobre un objetivo de 800: se llega sí o sí.
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, 25, 100);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['pass'])->toBe(100.0)
        ->and($r['median_days'])->toBe(8); // 800 / 100
});

it('cuenta como violación diaria el día que se pasa del límite', function () {
    // Límite diario del 5% = 500. Cada día pierde 600: el primer día revienta.
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, 25, -600);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['daily_breach'])->toBe(100.0)
        ->and($r['pass'])->toBe(0.0);
});

it('cuenta como violación total la sangría lenta', function () {
    // Pérdidas de 100 al día: nunca rompen el 5% diario, pero el 10% total (1.000)
    // cae al décimo día. Es el caso que más se parece a una cuenta quemada de verdad.
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, 25, -100);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['total_breach'])->toBe(100.0)
        ->and($r['daily_breach'])->toBe(0.0);
});

it('no deja pasar la fase mientras falten días mínimos rentables', function () {
    // El dinero llega al octavo día (800 de objetivo a 100 por día), pero la fase
    // exige 40 días rentables y solo llevas 25: manda el contador de días.
    $cuenta = cuentaEnFase(['min_trading_days' => 40]);
    diasCerrados($cuenta, 25, 100);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['missing_profitable_days'])->toBe(15)
        ->and($r['median_days'])->toBe(15);
});

it('solo cuenta como día rentable el que supera el umbral de la cuenta', function () {
    // Umbral = 0,3% de 10.000 = 30 €. Ganar 10 € no es un día rentable.
    $cuenta = cuentaEnFase(['min_trading_days' => 4]);
    diasCerrados($cuenta, 20, 10);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['profitable_days'])->toBe(0)
        ->and($r['missing_profitable_days'])->toBe(4);
});

it('mide supervivencia y no fecha de paso en una cuenta fondeada', function () {
    // Sin objetivo de beneficio no hay hito que fechar. Dar una fecha aquí sería
    // inventarse un día en el que «lo consigues» que la fase no tiene.
    $cuenta = cuentaEnFase(['profit_target_percent' => 0]);
    diasCerrados($cuenta, 25, 50);

    $r = $this->accion->execute($cuenta, 1);

    expect($r['mode'])->toBe('survival')
        ->and($r['pass'])->toBe(100.0)
        ->and($r['median_days'])->toBeNull()
        ->and($r['estimated_date'])->toBeNull();
});

it('saca la fecha estimada del ritmo real de la cuenta', function () {
    // 20 días operados repartidos en 20 días naturales = 7 días por semana (el
    // tope). Mediana de 8 días → 8 días naturales.
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, 20, 100);

    Carbon::setTestNow('2026-03-02');
    $r = $this->accion->execute($cuenta, 1);
    Carbon::setTestNow();

    expect($r['days_per_week'])->toBe(7.0)
        ->and($r['median_days'])->toBe(8)
        ->and($r['estimated_date'])->toBe('2026-03-10');
});

it('recorta el ritmo a una vez por semana cuando la cuenta apenas opera', function () {
    // 20 días operados repartidos en más de cinco meses. Sin el recorte, la
    // proyección se iría a años vista.
    $cuenta = cuentaEnFase();
    $dia = Carbon::parse('2026-01-05');

    for ($i = 0; $i < 20; $i++) {
        diaCerrado($cuenta, $dia->copy()->addDays($i * 8)->toDateString(), 100);
    }

    expect($this->accion->execute($cuenta, 1)['days_per_week'])->toBe(1.0);
});

it('repite el mismo resultado con la misma semilla', function () {
    // Sin esto, dos cargas seguidas de la pantalla darían probabilidades
    // distintas y el usuario no sabría a cuál hacer caso.
    $cuenta = cuentaEnFase();
    diasCerrados($cuenta, 25, fake()->randomFloat(2, -400, 400));

    for ($i = 0; $i < 22; $i++) {
        diaCerrado($cuenta, Carbon::parse('2026-02-01')->addDays($i)->toDateString(), $i % 3 === 0 ? -250 : 180);
    }

    expect($this->accion->execute($cuenta, 99))->toBe($this->accion->execute($cuenta, 99));
});

it('no simula nada si la cuenta no tiene fase', function () {
    expect($this->accion->execute(new Account))->toBe(['has_objective' => false]);
});
