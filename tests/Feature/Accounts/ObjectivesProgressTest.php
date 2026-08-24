<?php

use App\Models\Account;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use App\Models\Trade;

/**
 * Cobertura de `Account::getObjectivesProgressAttribute()`, el cálculo que decide
 * si una cuenta de prop firm va en verde o está quemada.
 *
 * Es la lógica más crítica del proyecto: un error aquí hace que un usuario siga
 * operando creyéndose dentro de límite cuando ya ha roto la regla, o al revés.
 */

/**
 * Cuenta con un único objetivo activo.
 *
 * Todos los porcentajes arrancan a 0 (= regla desactivada) para que cada test
 * habilite solo la que mide y `objectives_progress` devuelva una sola entrada.
 */
function accountWithObjective(array $objective = [], array $account = []): Account
{
    $level = ProgramLevel::factory()->create();

    $created = ProgramObjective::factory()->create(array_merge([
        'program_level_id' => $level->id,
        'profit_target_percent' => 0,
        'max_daily_loss_percent' => 0,
        'max_total_loss_percent' => 0,
        'min_trading_days' => 0,
    ], $objective));

    return Account::factory()->create(array_merge([
        'program_level_id' => $level->id,
        'program_objective_id' => $created->id,
        'initial_balance' => 100000,
        'current_balance' => 100000,
        'current_equity' => 100000,
    ], $account));
}

/** La única regla activa de la cuenta, ya evaluada. */
function onlyRule(Account $account): array
{
    $progress = $account->objectives_progress;

    expect($progress)->toHaveCount(1);

    return $progress->first();
}

/** Trade cerrado con un PnL exacto en el día indicado. */
function objectiveTrade(Account $account, float $pnl, string $day = 'today', int $hour = 10): Trade
{
    $entry = now()->modify($day)->startOfDay()->addHours($hour);

    return Trade::factory()->for($account)->create([
        'pnl' => $pnl,
        'entry_time' => $entry,
        'exit_time' => $entry->copy()->addHour(),
        'duration_minutes' => 60,
    ]);
}

// ---------------------------------------------------------------------------
// PROFIT TARGET
// ---------------------------------------------------------------------------

it('mide el profit target contra el balance inicial y lo marca en curso mientras no se alcanza', function () {
    // Objetivo 8% de 100.000 = 8.000. Llevamos +4.000.
    $account = accountWithObjective(
        ['profit_target_percent' => 8],
        ['current_balance' => 104000, 'current_equity' => 104000],
    );

    $rule = onlyRule($account);

    // `type` es una clave estable, no el literal traducido: `card-objectives`
    // la usa en un `match` para elegir el icono del trofeo.
    expect($rule['type'])->toBe('profit_target')
        ->and($rule['target_value'])->toBe(8000.0)
        ->and($rule['current_value'])->toBe(4000.0)
        ->and($rule['status'])->toBe('ongoing')
        ->and($rule['is_hard_rule'])->toBeFalse();
});

it('da el profit target por superado al alcanzarlo exactamente', function () {
    // El corte es inclusivo: 8.000 de beneficio con objetivo de 8.000 ya pasa.
    $account = accountWithObjective(
        ['profit_target_percent' => 8],
        ['current_balance' => 108000, 'current_equity' => 108000],
    );

    expect(onlyRule($account)['status'])->toBe('passed');
});

it('no muestra progreso negativo hacia el profit target cuando la cuenta va en pérdidas', function () {
    // -3.000 no es "-37,5% del objetivo": el progreso se corta en 0.
    $account = accountWithObjective(
        ['profit_target_percent' => 8],
        ['current_balance' => 97000, 'current_equity' => 97000],
    );

    $rule = onlyRule($account);

    expect((float) $rule['current_value'])->toBe(0.0)
        ->and($rule['status'])->toBe('ongoing');
});

it('mide el profit target sobre el balance cerrado, ignorando el beneficio flotante', function () {
    // Las prop firms exigen el objetivo con posiciones cerradas: 6.000 flotantes
    // sobre un balance plano no cuentan como progreso.
    $account = accountWithObjective(
        ['profit_target_percent' => 8],
        ['current_balance' => 100000, 'current_equity' => 106000],
    );

    expect((float) onlyRule($account)['current_value'])->toBe(0.0);
});

// ---------------------------------------------------------------------------
// MAX DAILY LOSS
// ---------------------------------------------------------------------------

it('mide la pérdida diaria contra el equity con el que se abrió el día', function () {
    // Empezó el día en 102.000 y va por 99.000: 3.000 de drawdown, límite 5.000.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 99000,
            'current_equity' => 99000,
            'today_starting_equity' => 102000,
        ],
    );

    $rule = onlyRule($account);

    expect($rule['type'])->toBe('max_daily_loss')
        ->and($rule['target_value'])->toBe(5000.0)
        ->and($rule['current_value'])->toBe(3000.0)
        ->and($rule['status'])->toBe('passing')
        ->and($rule['is_hard_rule'])->toBeTrue();
});

it('quema la cuenta cuando la pérdida diaria alcanza el límite exacto', function () {
    // 5.000 de pérdida con límite de 5.000 ya es violación: el corte es inclusivo.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 95000,
            'current_equity' => 95000,
            'today_starting_equity' => 100000,
        ],
    );

    expect(onlyRule($account)['status'])->toBe('failed');
});

it('calcula el límite diario sobre el balance inicial, no sobre el actual', function () {
    // Con 90.000 en cuenta el límite sigue siendo el 5% de los 100.000 iniciales.
    // Medirlo sobre el balance vivo daría 4.500 y quemaría la cuenta antes de tiempo.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 90000,
            'current_equity' => 90000,
            'today_starting_equity' => 90000,
        ],
    );

    expect(onlyRule($account)['target_value'])->toBe(5000.0);
});

it('nunca reporta pérdida diaria negativa en un día en verde', function () {
    // Día ganador: el drawdown es 0, no -2.000.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 102000,
            'current_equity' => 102000,
            'today_starting_equity' => 100000,
        ],
    );

    $rule = onlyRule($account);

    expect((float) $rule['current_value'])->toBe(0.0)
        ->and($rule['status'])->toBe('passing');
});

it('reconstruye el equity de inicio del día restando el PnL cerrado hoy cuando no hay snapshot', function () {
    // Sin `today_starting_equity` el fallback es: balance actual - PnL de hoy.
    // 98.000 - (-2.000) = 100.000 de arranque => 2.000 de drawdown.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 98000,
            'current_equity' => 98000,
            'today_starting_equity' => null,
        ],
    );

    objectiveTrade($account, -2000);

    expect(onlyRule($account)['current_value'])->toBe(2000.0);
});

it('deja fuera del arranque del día los trades cerrados en días anteriores', function () {
    // La pérdida de ayer ya está consolidada en el balance: si contara, el
    // drawdown de hoy se inflaría y la cuenta se daría por quemada sin serlo.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 96000,
            'current_equity' => 96000,
            'today_starting_equity' => null,
        ],
    );

    objectiveTrade($account, -3000, 'yesterday');
    objectiveTrade($account, -1000);

    // Arranque = 96.000 - (-1.000) = 97.000. Solo cuenta la pérdida de hoy.
    expect(onlyRule($account)['current_value'])->toBe(1000.0);
});

it('incluye la pérdida flotante en el drawdown diario', function () {
    // Balance intacto pero -4.000 flotantes: la regla se mide sobre equity,
    // que es como la vigila la prop firm en tiempo real.
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5],
        [
            'current_balance' => 100000,
            'current_equity' => 96000,
            'today_starting_equity' => 100000,
        ],
    );

    expect(onlyRule($account)['current_value'])->toBe(4000.0);
});

// ---------------------------------------------------------------------------
// MAX TOTAL LOSS
// ---------------------------------------------------------------------------

it('mide la pérdida total contra el balance inicial de la cuenta', function () {
    // 100.000 iniciales, 92.000 de equity => 8.000 de drawdown, límite 10.000.
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10],
        ['current_balance' => 92000, 'current_equity' => 92000],
    );

    $rule = onlyRule($account);

    expect($rule['type'])->toBe('max_total_loss')
        ->and($rule['target_value'])->toBe(10000.0)
        ->and($rule['current_value'])->toBe(8000.0)
        ->and($rule['status'])->toBe('passing')
        ->and($rule['is_hard_rule'])->toBeTrue();
});

it('quema la cuenta cuando la pérdida total alcanza el límite exacto', function () {
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10],
        ['current_balance' => 90000, 'current_equity' => 90000],
    );

    expect(onlyRule($account)['status'])->toBe('failed');
});

it('incluye la pérdida flotante en el drawdown total', function () {
    // El balance dice que no hemos perdido nada, pero hay -11.000 abiertos:
    // la cuenta está rota. Medirlo sobre balance sería el peor falso verde posible.
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10],
        ['current_balance' => 100000, 'current_equity' => 89000],
    );

    $rule = onlyRule($account);

    expect($rule['current_value'])->toBe(11000.0)
        ->and($rule['status'])->toBe('failed');
});

it('nunca reporta pérdida total negativa en una cuenta en beneficios', function () {
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10],
        ['current_balance' => 108000, 'current_equity' => 108000],
    );

    expect((float) onlyRule($account)['current_value'])->toBe(0.0);
});

it('cae al balance cuando la cuenta todavía no tiene equity sincronizado', function () {
    // Cuenta recién creada por el .exe: `current_equity` llega null hasta el
    // primer sync. El drawdown se mide entonces sobre el balance.
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10],
        ['current_balance' => 94000, 'current_equity' => null],
    );

    expect(onlyRule($account)['current_value'])->toBe(6000.0);
});

// ---------------------------------------------------------------------------
// MIN TRADING DAYS
// ---------------------------------------------------------------------------

it('cuenta como día operado el que supera el 0,3% del balance inicial', function () {
    // Umbral = 300. Dos días por encima, uno por debajo.
    $account = accountWithObjective(['min_trading_days' => 4]);

    objectiveTrade($account, 500, '-3 days');
    objectiveTrade($account, 400, '-2 days');
    objectiveTrade($account, 100, '-1 day');

    $rule = onlyRule($account);

    expect($rule['type'])->toBe('min_trading_days')
        ->and($rule['target_value'])->toBe(4)
        ->and($rule['current_value'])->toBe(2)
        ->and($rule['status'])->toBe('ongoing')
        ->and($rule['is_hard_rule'])->toBeFalse();
});

it('agrupa los trades por fecha, así que varios en el mismo día suman un solo día', function () {
    // 3 trades de 200 el mismo día = 600 > 300: un día, no tres.
    $account = accountWithObjective(['min_trading_days' => 1]);

    foreach ([10, 12, 14] as $hour) {
        objectiveTrade($account, 200, 'yesterday', $hour);
    }

    $rule = onlyRule($account);

    expect($rule['current_value'])->toBe(1)
        ->and($rule['status'])->toBe('passed');
});

it('no cuenta un día cerrado en pérdidas como día operado', function () {
    $account = accountWithObjective(['min_trading_days' => 1]);

    objectiveTrade($account, -500, 'yesterday');

    expect(onlyRule($account)['current_value'])->toBe(0);
});

it('imputa el día operado a la fecha de cierre, no a la de apertura', function () {
    // El PnL se realiza al cerrar. Una posición abierta el viernes y cerrada el
    // lunes es un día operado el lunes: agrupar por `entry_time` mientras se suma
    // el PnL realizado mezclaba dos criterios distintos.
    //
    // Umbral = 300. Los dos trades cierran el mismo día y suman 400, así que por
    // cierre es 1 día válido; por apertura serían dos días de 200 y ninguno valdría.
    $account = accountWithObjective(['min_trading_days' => 1]);
    $abre = now()->subDays(3)->startOfDay()->addHours(22);
    $cierra = now()->subDays(2)->startOfDay()->addHours(11);

    Trade::factory()->for($account)->create([
        'pnl' => 200,
        'entry_time' => $abre,
        'exit_time' => $cierra,
        'duration_minutes' => 780,
    ]);
    objectiveTrade($account, 200, '-2 days', 10);

    $rule = onlyRule($account);

    expect($rule['current_value'])->toBe(1)
        ->and($rule['status'])->toBe('passed');
});

// ---------------------------------------------------------------------------
// CONFIGURACIÓN Y AISLAMIENTO
// ---------------------------------------------------------------------------

it('no devuelve ningún objetivo cuando la cuenta no tiene fase asignada', function () {
    $account = accountWithObjective();
    $account->setRelation('currentObjective', null);

    // Colección vacía, no array: la vista puede encadenar métodos de Collection
    // sin comprobar antes el tipo.
    expect($account->objectives_progress)
        ->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->toBeEmpty();
});

it('omite las reglas desactivadas y solo evalúa las configuradas', function () {
    // Una fase de cuenta fondeada: sin objetivo de beneficio ni días mínimos.
    $account = accountWithObjective([
        'max_daily_loss_percent' => 5,
        'max_total_loss_percent' => 10,
    ]);

    $types = $account->objectives_progress->pluck('type');

    expect($types)->toHaveCount(2)
        ->and($types)->toContain('max_daily_loss', 'max_total_loss');
});

it('evalúa las cuatro reglas cuando el programa las define todas', function () {
    $account = accountWithObjective([
        'profit_target_percent' => 8,
        'max_daily_loss_percent' => 5,
        'max_total_loss_percent' => 10,
        'min_trading_days' => 4,
    ]);

    expect($account->objectives_progress)->toHaveCount(4);
});

it('no mezcla los trades de otras cuentas al calcular los objetivos', function () {
    $account = accountWithObjective(
        ['max_daily_loss_percent' => 5, 'min_trading_days' => 1],
        ['today_starting_equity' => null],
    );
    $otra = accountWithObjective();

    objectiveTrade($otra, -4000);

    $progress = $account->objectives_progress;

    expect((float) $progress->firstWhere('type', 'max_daily_loss')['current_value'])->toBe(0.0)
        ->and($progress->firstWhere('type', 'min_trading_days')['current_value'])->toBe(0);
});

it('arrastra la divisa de la cuenta a cada regla monetaria', function () {
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10],
        ['currency' => 'EUR'],
    );

    $rule = onlyRule($account);

    expect($rule['unit'])->toBe('money')
        ->and($rule['currency'])->toBe('EUR');
});

it('mide el drawdown sobre equity sea cual sea el loss_type del programa', function () {
    // PREMISA FIJADA A PROPÓSITO, NO COMPORTAMIENTO DESEADO.
    //
    // `program_objectives.loss_type` admite balance_based | equity_based | relative,
    // todos los seeders lo pueblan a `balance_based`, y el cálculo lo ignora: siempre
    // mide sobre equity. Con `relative` (drawdown trailing sobre el máximo alcanzado)
    // la diferencia es aún mayor y hoy no está implementado.
    //
    // Decisión de Jordi (2026-08-24): la columna no la lee nadie, así que se deja como
    // está. Este test no es una tarea pendiente, es el candado: si algún día se respeta
    // `loss_type`, falla y avisa de que hay que repasar las tres reglas de arriba.
    $account = accountWithObjective(
        ['max_total_loss_percent' => 10, 'loss_type' => 'balance_based'],
        ['current_balance' => 100000, 'current_equity' => 93000],
    );

    expect(onlyRule($account)['current_value'])->toBe(7000.0);
});
