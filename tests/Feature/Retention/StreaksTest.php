<?php

declare(strict_types=1);

use App\Actions\Retention\CalculateStreaks;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Rachas de disciplina (R3).
 *
 * Lo que se fija aquí es la definición de cada racha, que es justo lo que el
 * tooltip promete al usuario: si el cálculo se mueve, la interfaz empieza a
 * mentir.
 */
beforeEach(function () {
    // Miércoles: hay días laborables antes y el fin de semana queda a mano.
    $this->travelTo('2026-08-26 10:00:00');

    Cache::flush();

    $this->jordi = User::factory()->create(['timezone' => 'Europe/Madrid']);
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => false]);
    $this->activo = TradeAsset::factory()->create();
    $this->action = app(CalculateStreaks::class);
});

function diario(string $date, string $content = 'Sesión revisada', array $extra = []): JournalEntry
{
    return JournalEntry::create(array_merge([
        'user_id' => test()->jordi->id,
        'date' => $date,
        'content' => $content,
    ], $extra));
}

function operacion(string $date, float $pnl = 100, ?Account $account = null): Trade
{
    return Trade::factory()->create([
        'account_id' => ($account ?? test()->cuenta)->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => $date . ' 09:00:00',
        'exit_time' => $date . ' 11:00:00',
    ]);
}

function errorGrave(Trade $trade): void
{
    $mistake = Mistake::create([
        'user_id' => null,
        'slug' => 'revenge-trading',
        'name' => 'Revenge trading',
        'color' => 'red',
        'weight' => 3,
    ]);

    $trade->mistakes()->attach($mistake->id);
}

// ─────────────────────────────────────────────────────────────
// Racha de diario
// ─────────────────────────────────────────────────────────────

it('cuenta los días laborables seguidos con diario escrito', function () {
    diario('2026-08-26'); // miércoles (hoy)
    diario('2026-08-25'); // martes
    diario('2026-08-24'); // lunes

    $streaks = $this->action->execute($this->jordi);

    expect($streaks['journal']['current'])->toBe(3)
        ->and($streaks['journal']['today'])->toBeTrue();
});

it('salta el fin de semana sin romper la racha', function () {
    diario('2026-08-26'); // miércoles
    diario('2026-08-25');
    diario('2026-08-24');
    diario('2026-08-21'); // viernes anterior; sábado y domingo en blanco

    expect($this->action->execute($this->jordi)['journal']['current'])->toBe(4);
});

it('no rompe la racha porque hoy todavía no esté escrito', function () {
    diario('2026-08-25');
    diario('2026-08-24');

    $streaks = $this->action->execute($this->jordi);

    expect($streaks['journal']['current'])->toBe(2)
        ->and($streaks['journal']['today'])->toBeFalse();
});

it('no cuenta una entrada de diario vacía', function () {
    // La entrada existe en cuanto se abre el día en el diario: sin nada escrito
    // no puede contar, o la racha se regalaría sola.
    diario('2026-08-26', '');
    diario('2026-08-25');

    expect($this->action->execute($this->jordi)['journal']['current'])->toBe(1);
});

it('cuenta como escrito el día en que solo se hizo el ritual pre-mercado', function () {
    diario('2026-08-26', '', ['pre_market_mood' => 'calm']);

    expect($this->action->execute($this->jordi)['journal']['current'])->toBe(1);
});

// ─────────────────────────────────────────────────────────────
// Racha sin errores graves
// ─────────────────────────────────────────────────────────────

it('cuenta los días operados seguidos sin ningún error grave', function () {
    operacion('2026-08-26');
    operacion('2026-08-25');
    operacion('2026-08-24');

    expect($this->action->execute($this->jordi)['clean']['current'])->toBe(3);
});

it('rompe la racha limpia el día que hay un error grave', function () {
    operacion('2026-08-26');
    errorGrave(operacion('2026-08-25', -300));
    operacion('2026-08-24');

    $streaks = $this->action->execute($this->jordi);

    expect($streaks['clean']['current'])->toBe(1)
        ->and($streaks['clean']['broken_on'])->toBe('2026-08-25');
});

it('no rompe la racha limpia por un día sin operar', function () {
    // No arriesgar no es disciplina: los días sin operaciones ni suman ni restan.
    operacion('2026-08-26');
    operacion('2026-08-20');

    expect($this->action->execute($this->jordi)['clean']['current'])->toBe(2);
});

it('deja fuera las operaciones de la cuenta de ejemplo', function () {
    $ejemplo = Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => true]);

    operacion('2026-08-26', 100, $ejemplo);
    operacion('2026-08-25', 100, $ejemplo);

    expect($this->action->execute($this->jordi)['clean']['current'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────
// Semanas con el plan cumplido
// ─────────────────────────────────────────────────────────────

it('cuenta las semanas cerradas con todos los objetivos marcados', function () {
    // Semana anterior (17–23 de agosto), con actividad y todo cumplido.
    diario('2026-08-18', 'Bien', ['daily_objectives' => [['text' => 'Sin noticias', 'done' => true]]]);
    operacion('2026-08-18');

    expect($this->action->execute($this->jordi)['plan']['current'])->toBe(1);
});

it('rompe la racha de semanas si quedó algún objetivo sin marcar', function () {
    diario('2026-08-18', 'Regular', ['daily_objectives' => [['text' => 'Sin noticias', 'done' => false]]]);
    operacion('2026-08-18');

    expect($this->action->execute($this->jordi)['plan']['current'])->toBe(0);
});

it('no cuenta la semana en curso, que todavía puede romperse', function () {
    diario('2026-08-24', 'Bien', ['daily_objectives' => [['text' => 'Sin noticias', 'done' => true]]]);

    expect($this->action->execute($this->jordi)['plan']['current'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────
// Caché
// ─────────────────────────────────────────────────────────────

it('sirve el resultado cacheado hasta que alguien lo invalida', function () {
    diario('2026-08-26');

    expect($this->action->execute($this->jordi)['journal']['current'])->toBe(1);

    diario('2026-08-25');
    expect($this->action->execute($this->jordi)['journal']['current'])->toBe(1);

    CalculateStreaks::forget($this->jordi->id);
    expect($this->action->execute($this->jordi)['journal']['current'])->toBe(2);
});
