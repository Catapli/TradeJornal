<?php

declare(strict_types=1);

use App\Actions\Mentor\BuildTraderProfile;
use App\Actions\Mentor\ProposeMonthlyGoal;
use App\Actions\Mentor\TrackMonthlyGoal;
use App\Livewire\MentorPage;
use App\Models\Account;
use App\Models\ImprovementGoal;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * El objetivo de mejora del mes (Fase 6 · P5).
 *
 * Lo que se protege: **una sola cosa al mes**, sacada del mes anterior completo,
 * imposible de editar cuando va mal, y cerrada con un sí o un no cuando el mes
 * termina. Un objetivo que se puede reabrir para que salga bien no es un objetivo.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-08-15 10:00:00');

    $this->jordi = User::factory()->create(['trial_ends_at' => now()->addDays(20)]);
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'active']);
    $this->activo = TradeAsset::factory()->create();

    $this->error = Mistake::create(['slug' => 'objetivo_venganza', 'name' => 'Operar por venganza', 'color' => 'red', 'weight' => 5]);

    $this->proponer = app(ProposeMonthlyGoal::class);
    $this->seguir = app(TrackMonthlyGoal::class);
});

afterEach(fn () => Carbon::setTestNow());

/** Operación cerrada, con error o sin él. */
function opDeObjetivo(string $fecha, float $pnl, bool $conError = false): Trade
{
    $trade = Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon::parse($fecha . ' 09:00:00'),
        'exit_time' => Carbon::parse($fecha . ' 10:00:00'),
        'duration_minutes' => 60,
    ]);

    if ($conError) {
        $trade->mistakes()->attach(test()->error->id);
    }

    return $trade;
}

/** Julio con `$conError` operaciones marcadas de `$total`. */
function julioCon(int $conError, int $total): void
{
    for ($i = 1; $i <= $total; $i++) {
        opDeObjetivo('2026-07-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), $i <= $conError ? -120 : 90, $i <= $conError);
    }
}

/** El perfil del usuario del test. */
function perfilDeJordi(): array
{
    return app(BuildTraderProfile::class)->execute(test()->jordi->id);
}

it('propone bajar a la mitad de lo del mes pasado', function () {
    julioCon(12, 20);

    $propuesta = $this->proponer->execute($this->jordi->id, perfilDeJordi());

    expect($propuesta['baseline'])->toBe(12)
        ->and($propuesta['target'])->toBe(6)
        ->and($propuesta['sample'])->toBe(20)
        ->and($propuesta['month'])->toBe('2026-08-01')
        ->and($propuesta['reference_month'])->toBe('2026-07-01');
});

it('pide cero cuando de lo que se viene es de una o dos veces', function () {
    // Bajar de 2 a 1 no es un objetivo. A partir de ahí sí se pide cero.
    julioCon(12, 20);
    ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'month' => '2026-07-01', 'statement' => 'x',
        'baseline' => 2, 'target' => 0, 'sample' => 20,
    ]);

    expect((new ProposeMonthlyGoal)->execute($this->jordi->id, perfilDeJordi())['target'])->toBe(6);
});

it('no propone nada si el mes de referencia apenas tiene operaciones', function () {
    // Doce marcas en el semestre, pero repartidas: julio se queda en cuatro
    // operaciones y de ahí no sale ninguna cifra defendible.
    for ($i = 1; $i <= 8; $i++) {
        opDeObjetivo('2026-05-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), -120, true);
    }
    for ($i = 1; $i <= 4; $i++) {
        opDeObjetivo('2026-07-0' . $i, -120, true);
    }

    expect($this->proponer->execute($this->jordi->id, perfilDeJordi()))->toBeNull();
});

it('no propone nada si el error no apareció el mes pasado', function () {
    for ($i = 1; $i <= 12; $i++) {
        opDeObjetivo('2026-06-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), -120, true);
    }
    for ($i = 1; $i <= 15; $i++) {
        opDeObjetivo('2026-07-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 90);
    }

    expect($this->proponer->execute($this->jordi->id, perfilDeJordi()))->toBeNull();
});

it('no propone nada sin perfil', function () {
    julioCon(3, 20);

    expect($this->proponer->execute($this->jordi->id, perfilDeJordi()))->toBeNull();
});

it('cuenta lo que va del mes contra el objetivo', function () {
    julioCon(12, 20);
    opDeObjetivo('2026-08-05', -120, true);
    opDeObjetivo('2026-08-07', -120, true);

    $objetivo = ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'mistake_id' => $this->error->id, 'month' => '2026-08-01',
        'statement' => 'Bajar de 12 a 6', 'baseline' => 12, 'target' => 6, 'sample' => 20,
    ]);

    $avance = $this->seguir->progress($objetivo);

    expect($avance['so_far'])->toBe(2)
        ->and($avance['blown'])->toBeFalse()
        ->and($avance['days_left'])->toBe(16); // del 15 al 31
});

it('avisa de que el objetivo está roto en cuanto se pasa', function () {
    // Enterarse el día 30 de algo que se rompió el día 4 no corrige nada.
    julioCon(12, 20);
    for ($i = 1; $i <= 7; $i++) {
        opDeObjetivo('2026-08-0' . $i, -120, true);
    }

    $objetivo = ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'mistake_id' => $this->error->id, 'month' => '2026-08-01',
        'statement' => 'Bajar de 12 a 6', 'baseline' => 12, 'target' => 6, 'sample' => 20,
    ]);

    expect($this->seguir->progress($objetivo)['blown'])->toBeTrue();
});

it('cierra como cumplido el mes que se quedó dentro', function () {
    for ($i = 1; $i <= 4; $i++) {
        opDeObjetivo('2026-07-0' . $i, -120, true);
    }

    $objetivo = ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'mistake_id' => $this->error->id, 'month' => '2026-07-01',
        'statement' => 'Bajar de 12 a 6', 'baseline' => 12, 'target' => 6, 'sample' => 20,
    ]);

    expect($this->seguir->closeFinished($this->jordi->id))->toBe(1);

    $objetivo->refresh();

    expect($objetivo->status)->toBe(ImprovementGoal::STATUS_ACHIEVED)
        ->and($objetivo->result)->toBe(4)
        ->and($objetivo->closed_at)->not->toBeNull();
});

it('cierra como no cumplido el mes que se pasó', function () {
    for ($i = 1; $i <= 9; $i++) {
        opDeObjetivo('2026-07-0' . $i, -120, true);
    }

    $objetivo = ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'mistake_id' => $this->error->id, 'month' => '2026-07-01',
        'statement' => 'Bajar de 12 a 6', 'baseline' => 12, 'target' => 6, 'sample' => 20,
    ]);

    $this->seguir->closeFinished($this->jordi->id);

    expect($objetivo->refresh()->status)->toBe(ImprovementGoal::STATUS_MISSED)
        ->and($objetivo->result)->toBe(9);
});

it('no toca el objetivo del mes en curso', function () {
    $objetivo = ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'mistake_id' => $this->error->id, 'month' => '2026-08-01',
        'statement' => 'Bajar de 12 a 6', 'baseline' => 12, 'target' => 6, 'sample' => 20,
    ]);

    expect($this->seguir->closeFinished($this->jordi->id))->toBe(0)
        ->and($objetivo->refresh()->status)->toBe(ImprovementGoal::STATUS_ACTIVE);
});

it('no vuelve a cerrar lo ya cerrado', function () {
    // El cierre es irreversible: reabrir un objetivo para que salga bien es la
    // manera más rápida de que dejen de significar nada.
    for ($i = 1; $i <= 9; $i++) {
        opDeObjetivo('2026-07-0' . $i, -120, true);
    }

    $objetivo = ImprovementGoal::create([
        'user_id' => $this->jordi->id, 'mistake_id' => $this->error->id, 'month' => '2026-07-01',
        'statement' => 'Bajar de 12 a 6', 'baseline' => 12, 'target' => 6, 'sample' => 20,
        'status' => ImprovementGoal::STATUS_ACHIEVED, 'result' => 1, 'closed_at' => now(),
    ]);

    expect($this->seguir->closeFinished($this->jordi->id))->toBe(0)
        ->and($objetivo->refresh()->result)->toBe(1);
});

it('fija el objetivo desde la pantalla y lo deja congelado', function () {
    julioCon(12, 20);

    Livewire::actingAs($this->jordi)
        ->test(MentorPage::class)
        ->call('setGoal')
        ->assertDispatched('show-alert');

    $objetivo = ImprovementGoal::where('user_id', $this->jordi->id)->firstOrFail();

    expect($objetivo->month->toDateString())->toBe('2026-08-01')
        ->and($objetivo->baseline)->toBe(12)
        ->and($objetivo->target)->toBe(6)
        ->and($objetivo->sample)->toBe(20)
        ->and($objetivo->statement)->toContain('Operar por venganza');
});

it('no deja fijar dos objetivos el mismo mes', function () {
    julioCon(12, 20);

    Livewire::actingAs($this->jordi)->test(MentorPage::class)->call('setGoal');
    Livewire::actingAs($this->jordi)->test(MentorPage::class)->call('setGoal');

    expect(ImprovementGoal::where('user_id', $this->jordi->id)->count())->toBe(1);
});

it('pinta la pantalla del mentor', function () {
    julioCon(12, 20);

    $this->actingAs($this->jordi)
        ->get(route('mentor'))
        ->assertOk()
        ->assertSee(__('mentor.title'))
        ->assertSee(__('mentor.profile_title'))
        ->assertSee(__('mentor.goal_title'));
});

it('avisa en pantalla de que faltan marcas en vez de inventarse un patrón', function () {
    julioCon(3, 20);

    $this->actingAs($this->jordi)
        ->get(route('mentor'))
        ->assertOk()
        ->assertSee(__('mentor.not_enough_title'))
        ->assertDontSee(__('mentor.goal.set'));
});

it('enseña el mentor difuminado a un usuario gratuito', function () {
    $gratuito = User::factory()->create(['trial_ends_at' => now()->subDay()]);

    $this->actingAs($gratuito)
        ->get(route('mentor'))
        ->assertOk()
        ->assertSee(__('landing.gate.badge'));
});
