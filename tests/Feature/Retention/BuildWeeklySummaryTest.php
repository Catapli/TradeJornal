<?php

declare(strict_types=1);

use App\Actions\Retention\BuildWeeklySummary;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Las cifras del resumen semanal (R1).
 *
 * El mismo array alimenta el correo y la revisión guiada, así que un error aquí
 * sale por dos sitios a la vez.
 */
beforeEach(function () {
    $this->semana = CarbonImmutable::parse('2026-08-17'); // lunes
    $this->jordi = User::factory()->create(['timezone' => 'Europe/Madrid']);
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => false]);
    $this->activo = TradeAsset::factory()->create();
    $this->builder = app(BuildWeeklySummary::class);
});

function tradeSemana(float $pnl, string $exit, ?Account $account = null): Trade
{
    return Trade::factory()->create([
        'account_id' => ($account ?? test()->cuenta)->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => $exit,
        'exit_time' => $exit,
    ]);
}

function resumen(): array
{
    return test()->builder->execute(test()->jordi, test()->semana);
}

it('suma el resultado y el acierto de la semana', function () {
    tradeSemana(100, '2026-08-17 10:00:00');
    tradeSemana(50, '2026-08-18 10:00:00');
    tradeSemana(-30, '2026-08-19 10:00:00');

    $summary = resumen();

    expect($summary['trades'])->toBe(3)
        ->and($summary['pnl'])->toBe(120.0)
        ->and($summary['wins'])->toBe(2)
        ->and($summary['losses'])->toBe(1)
        ->and($summary['win_rate'])->toBe(66.7);
});

it('deja fuera lo que cerró en otra semana', function () {
    tradeSemana(100, '2026-08-17 10:00:00');
    tradeSemana(999, '2026-08-24 10:00:00'); // lunes siguiente

    expect(resumen()['trades'])->toBe(1);
});

it('deja fuera la cuenta de ejemplo', function () {
    $ejemplo = Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => true]);

    tradeSemana(100, '2026-08-17 10:00:00');
    tradeSemana(5000, '2026-08-18 10:00:00', $ejemplo);

    expect(resumen()['pnl'])->toBe(100.0);
});

it('señala la mejor y la peor operación', function () {
    tradeSemana(100, '2026-08-17 10:00:00');
    tradeSemana(-250, '2026-08-18 10:00:00');

    $summary = resumen();

    expect($summary['best_trade']['pnl'])->toBe(100.0)
        ->and($summary['worst_trade']['pnl'])->toBe(-250.0);
});

it('ordena los errores repetidos por gravedad y los cuenta', function () {
    $grave = Mistake::create(['user_id' => null, 'slug' => 'no-plan', 'name' => 'Sin plan', 'color' => 'red', 'weight' => 3]);
    $leve = Mistake::create(['user_id' => null, 'slug' => 'early', 'name' => 'Entrada anticipada', 'color' => 'amber', 'weight' => 1]);

    tradeSemana(-100, '2026-08-17 10:00:00')->mistakes()->attach($grave->id);
    tradeSemana(-50, '2026-08-18 10:00:00')->mistakes()->attach($grave->id);
    tradeSemana(-10, '2026-08-19 10:00:00')->mistakes()->attach($leve->id);
    tradeSemana(-10, '2026-08-20 10:00:00')->mistakes()->attach($leve->id);
    tradeSemana(-10, '2026-08-21 10:00:00')->mistakes()->attach($leve->id);

    $mistakes = resumen()['mistakes'];

    expect($mistakes)->toHaveCount(2)
        ->and($mistakes[0]['slug'])->toBe('no-plan')
        ->and($mistakes[0]['times'])->toBe(2)
        ->and($mistakes[0]['pnl'])->toBe(-150.0)
        ->and($mistakes[1]['times'])->toBe(3);
});

it('recoge las reglas que se quedaron sin marcar', function () {
    JournalEntry::create([
        'user_id' => $this->jordi->id,
        'date' => '2026-08-18',
        'daily_objectives' => [
            ['text' => 'No operar noticias', 'done' => false],
            ['text' => 'Máximo 3 operaciones', 'done' => true],
        ],
    ]);

    JournalEntry::create([
        'user_id' => $this->jordi->id,
        'date' => '2026-08-19',
        'daily_objectives' => [['text' => 'No operar noticias', 'done' => false]],
    ]);

    $broken = resumen()['broken_rules'];

    expect($broken)->toHaveCount(1)
        ->and($broken[0]['text'])->toBe('No operar noticias')
        ->and($broken[0]['times'])->toBe(2);
});

it('encuentra la franja horaria más rentable por hora de entrada', function () {
    tradeSemana(300, '2026-08-17 09:30:00');
    tradeSemana(-100, '2026-08-18 15:30:00');

    expect(resumen()['best_hour']['hour'])->toBe(9)
        ->and(resumen()['best_hour']['pnl'])->toBe(300.0);
});

it('compara con la semana anterior', function () {
    tradeSemana(100, '2026-08-17 10:00:00');
    tradeSemana(400, '2026-08-10 10:00:00'); // semana previa

    $summary = resumen();

    expect($summary['pnl'])->toBe(100.0)
        ->and($summary['previous']['pnl'])->toBe(400.0);
});

it('marca la semana como sin actividad cuando no hay nada que contar', function () {
    expect(resumen()['has_activity'])->toBeFalse();

    tradeSemana(10, '2026-08-17 10:00:00');

    expect(resumen()['has_activity'])->toBeTrue();
});

it('cuenta los días de diario escritos y su disciplina media', function () {
    JournalEntry::create(['user_id' => $this->jordi->id, 'date' => '2026-08-17', 'content' => 'Buen día', 'discipline_score' => 8]);
    JournalEntry::create(['user_id' => $this->jordi->id, 'date' => '2026-08-18', 'content' => 'Regular', 'discipline_score' => 6]);

    $summary = resumen();

    expect($summary['journal_days'])->toBe(2)
        ->and($summary['discipline'])->toBe(7.0);
});
