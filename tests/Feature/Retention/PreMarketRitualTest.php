<?php

declare(strict_types=1);

use App\Livewire\PreMarketRitual;
use App\Models\JournalEntry;
use App\Models\TradingObjective;
use App\Models\User;
use Livewire\Livewire;

/**
 * Ritual pre-mercado (R5).
 *
 * Escribe en la entrada del diario de hoy, no en una tabla aparte: si guardara
 * en otro sitio, el diario y el ritual contarían cosas distintas del mismo día
 * y la racha dejaría de cuadrar.
 */
beforeEach(function () {
    $this->travelTo('2026-08-26 09:00:00');

    $this->jordi = User::factory()->create(['timezone' => 'Europe/Madrid']);
});

it('se ofrece cuando el día todavía no tiene ritual', function () {
    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->assertSet('doneToday', false)
        ->assertSee(__('ritual.card.title'));
});

it('deja de ofrecerse pasada la hora de cierre', function () {
    // A las once de la noche ya no hay sesión que preparar.
    $this->travelTo('2026-08-26 23:30:00');

    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->assertDontSee(__('ritual.card.title'));
});

it('guarda el ritual en el diario de hoy', function () {
    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->call('start')
        ->set('mood', 'calm')
        ->set('objectives', [['text' => 'No operar noticias', 'done' => false]])
        ->set('notes', 'Vigilar el impulso tras dos pérdidas')
        ->call('finish')
        ->assertSet('doneToday', true)
        ->assertSet('open', false);

    $entry = JournalEntry::where('user_id', $this->jordi->id)->firstOrFail();

    expect($entry->date->toDateString())->toBe('2026-08-26')
        ->and($entry->pre_market_mood)->toBe('calm')
        ->and($entry->pre_market_notes)->toBe('Vigilar el impulso tras dos pérdidas')
        ->and($entry->daily_objectives)->toBe([['text' => 'No operar noticias', 'done' => false]]);
});

it('no guarda nada sin elegir cómo se llega', function () {
    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->call('start')
        ->call('finish')
        ->assertSet('step', 1)
        ->assertSet('doneToday', false)
        ->assertHasErrors('mood');

    expect(JournalEntry::where('user_id', $this->jordi->id)->exists())->toBeFalse();
});

it('no deja pasar del primer paso sin elegir cómo se llega', function () {
    // Antes se podía llegar al último paso sin ánimo, y allí «Listo» rebotaba al
    // primero sin explicar por qué: parecía que el botón no hacía nada.
    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->call('start')
        ->call('next')
        ->assertSet('step', 1)
        ->assertHasErrors('mood')
        ->set('mood', 'calm')
        ->call('next')
        ->assertSet('step', 2)
        ->assertHasNoErrors('mood');
});

it('descarta los objetivos vacíos', function () {
    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->set('mood', 'confident')
        ->set('objectives', [
            ['text' => 'Máximo tres operaciones', 'done' => false],
            ['text' => '   ', 'done' => false],
        ])
        ->call('finish');

    $entry = JournalEntry::where('user_id', $this->jordi->id)->firstOrFail();

    expect($entry->daily_objectives)->toHaveCount(1);
});

it('no se vuelve a ofrecer una vez hecho', function () {
    JournalEntry::create([
        'user_id' => $this->jordi->id,
        'date' => '2026-08-26',
        'pre_market_mood' => 'calm',
    ]);

    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->assertSet('doneToday', true)
        ->assertDontSee(__('ritual.card.title'));
});

it('se puede posponer hasta mañana sin escribir en base de datos', function () {
    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->call('postpone')
        ->assertSet('postponed', true)
        ->assertDontSee(__('ritual.card.title'));

    expect(JournalEntry::where('user_id', $this->jordi->id)->exists())->toBeFalse();
});

it('parte de las reglas activas del usuario', function () {
    TradingObjective::create(['user_id' => $this->jordi->id, 'text' => 'Esperar cierre de vela', 'is_active' => true]);
    TradingObjective::create(['user_id' => $this->jordi->id, 'text' => 'Regla apagada', 'is_active' => false]);

    $component = Livewire::actingAs($this->jordi)->test(PreMarketRitual::class);

    expect($component->get('objectives'))->toBe([['text' => 'Esperar cierre de vela', 'done' => false]]);
});

it('no pisa lo que ya tuviera escrito el día en el diario', function () {
    JournalEntry::create([
        'user_id' => $this->jordi->id,
        'date' => '2026-08-26',
        'content' => 'Notas de la sesión',
    ]);

    Livewire::actingAs($this->jordi)
        ->test(PreMarketRitual::class)
        ->set('mood', 'tired')
        ->call('finish');

    $entry = JournalEntry::where('user_id', $this->jordi->id)->firstOrFail();

    expect($entry->content)->toBe('Notas de la sesión')
        ->and($entry->pre_market_mood)->toBe('tired');
});
