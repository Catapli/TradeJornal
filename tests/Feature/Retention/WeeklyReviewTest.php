<?php

declare(strict_types=1);

use App\Livewire\WeeklyReviewPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use App\Models\WeeklyReview;
use Livewire\Livewire;

/**
 * Revisión semanal guiada (R2). Módulo PRO.
 *
 * Se fija aquí la selección de operaciones —tres mejores y tres peores— y el
 * cierre, que congela las métricas de esa semana para que el archivo siga
 * cuadrando aunque el usuario reclasifique una operación meses después.
 */
beforeEach(function () {
    // Miércoles: la semana a revisar es la del 17 al 23 de agosto.
    $this->travelTo('2026-08-26 10:00:00');

    $this->jordi = User::factory()->create([
        'timezone' => 'Europe/Madrid',
        'trial_ends_at' => now()->addDays(10), // PRO por prueba
    ]);

    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => false]);
    $this->activo = TradeAsset::factory()->create();
});

function tradeRevision(float $pnl, string $day = '2026-08-18'): Trade
{
    return Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => $day . ' 09:00:00',
        'exit_time' => $day . ' 11:00:00',
    ]);
}

// ─────────────────────────────────────────────────────────────
// Selección
// ─────────────────────────────────────────────────────────────

it('abre por defecto la semana que acaba de terminar', function () {
    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->assertSet('weekStart', '2026-08-17');
});

it('elige las tres mejores y las tres peores de la semana', function () {
    foreach ([500, 400, 300, 200, 100, -100, -200, -300] as $pnl) {
        tradeRevision((float) $pnl);
    }

    $component = Livewire::actingAs($this->jordi)->test(WeeklyReviewPage::class);

    $pnls = $component->instance()->trades->pluck('pnl')->map(fn ($p): float => (float) $p)->all();

    expect($pnls)->toHaveCount(6)
        ->and($pnls)->toContain(500.0, 400.0, 300.0)
        ->and($pnls)->toContain(-300.0, -200.0, -100.0);
});

it('no repite una operación que es a la vez la mejor y la peor', function () {
    tradeRevision(120);

    $component = Livewire::actingAs($this->jordi)->test(WeeklyReviewPage::class);

    expect($component->instance()->trades)->toHaveCount(1);
});

// ─────────────────────────────────────────────────────────────
// Detalle de la operación
// ─────────────────────────────────────────────────────────────

it('abre el detalle de una operación de la revisión con las seis como contexto', function () {
    $trade = tradeRevision(300);
    $otro = tradeRevision(-120);

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->call('openTradeDetail', $trade->id)
        ->assertDispatched('open-trade-detail', tradeId: $trade->id, tradeIds: [$trade->id, $otro->id]);
});

it('no abre una operación que no está en la revisión', function () {
    tradeRevision(300);

    // Otra semana: no es una de las seis que se están revisando.
    $ajeno = tradeRevision(500, '2026-08-11');

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->call('openTradeDetail', $ajeno->id)
        ->assertNotDispatched('open-trade-detail');
});

// ─────────────────────────────────────────────────────────────
// Cierre
// ─────────────────────────────────────────────────────────────

it('no deja cerrar la revisión con preguntas sin contestar', function () {
    $trade = tradeRevision(100);

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->call('complete');

    expect(WeeklyReview::where('user_id', $this->jordi->id)->exists())->toBeFalse();
});

it('cierra la revisión y congela las métricas de la semana', function () {
    $trade = tradeRevision(250);

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->set("answers.trade_{$trade->id}.plan", 'yes')
        ->set("answers.trade_{$trade->id}.trigger", 'Ruptura del rango')
        ->set('takeaway', 'Esperar el retest')
        ->call('complete');

    $review = WeeklyReview::where('user_id', $this->jordi->id)->firstOrFail();

    expect($review->completed_at)->not->toBeNull()
        ->and($review->week_start->toDateString())->toBe('2026-08-17')
        ->and($review->takeaway)->toBe('Esperar el retest')
        ->and($review->stats['pnl'])->toEqual(250.0)
        ->and($review->answers["trade_{$trade->id}"]['plan'])->toBe('yes');
});

it('mantiene congeladas las cifras aunque la semana cambie después', function () {
    $trade = tradeRevision(250);

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->set("answers.trade_{$trade->id}.plan", 'yes')
        ->call('complete');

    // Una operación que aparece más tarde (importación, corrección del broker).
    tradeRevision(-9000);

    $review = WeeklyReview::where('user_id', $this->jordi->id)->firstOrFail();

    expect($review->stats['pnl'])->toEqual(250.0);
});

it('guarda un borrador sin cerrar la revisión', function () {
    $trade = tradeRevision(100);

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->set("answers.trade_{$trade->id}.trigger", 'A medias')
        ->call('save');

    $review = WeeklyReview::where('user_id', $this->jordi->id)->firstOrFail();

    expect($review->completed_at)->toBeNull()
        ->and($review->answers["trade_{$trade->id}"]['trigger'])->toBe('A medias');
});

it('descarta una respuesta inventada en la primera pregunta', function () {
    $trade = tradeRevision(100);

    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->set("answers.trade_{$trade->id}.plan", 'perfecto')
        ->call('save');

    $review = WeeklyReview::where('user_id', $this->jordi->id)->firstOrFail();

    expect($review->answers["trade_{$trade->id}"]['plan'])->toBeNull();
});

// ─────────────────────────────────────────────────────────────
// Navegación
// ─────────────────────────────────────────────────────────────

it('permite retroceder de semana pero no adelantarse al futuro', function () {
    $component = Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->call('previousWeek')
        ->assertSet('weekStart', '2026-08-10')
        ->call('nextWeek')
        ->assertSet('weekStart', '2026-08-17')
        ->call('nextWeek')
        ->assertSet('weekStart', '2026-08-24'); // semana en curso, el tope

    $component->call('nextWeek')->assertSet('weekStart', '2026-08-24');
});

// ─────────────────────────────────────────────────────────────
// Muro PRO
// ─────────────────────────────────────────────────────────────

it('corta en servidor a un usuario gratuito', function () {
    $gratuito = User::factory()->create(['trial_ends_at' => now()->subDay()]);

    Livewire::actingAs($gratuito)
        ->test(WeeklyReviewPage::class)
        ->call('save')
        ->assertForbidden();
});

it('deja pasar a quien tiene PRO', function () {
    Livewire::actingAs($this->jordi)
        ->test(WeeklyReviewPage::class)
        ->call('save')
        ->assertOk();
});
