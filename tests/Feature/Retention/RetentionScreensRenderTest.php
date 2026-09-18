<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;

/**
 * Humo: las pantallas de la Fase 4 se pintan enteras.
 *
 * Los chips del navbar, la tarjeta de rachas y el ritual viven en el panel, que
 * hasta ahora ningún test cargaba de punta a punta. Una plantilla rota ahí se
 * lleva por delante toda la aplicación, y Blade tiene trampas —las directivas en
 * línea, sin ir más lejos— que solo aparecen al compilar la vista de verdad.
 */
beforeEach(function () {
    $this->travelTo('2026-08-26 09:00:00');

    $this->jordi = User::factory()->create([
        'timezone' => 'Europe/Madrid',
        'trial_ends_at' => now()->addDays(10),
    ]);
});

it('pinta el panel con las rachas, el ritual y el navbar', function () {
    $cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => false]);

    Trade::factory()->create([
        'account_id' => $cuenta->id,
        'trade_asset_id' => TradeAsset::factory()->create()->id,
        'pnl' => 150,
        'entry_time' => '2026-08-25 09:00:00',
        'exit_time' => '2026-08-25 11:00:00',
    ]);

    $this->actingAs($this->jordi)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('streaks.title'))
        ->assertSee(__('ritual.card.title'));
});

it('pinta la revisión semanal de un usuario PRO', function () {
    $this->actingAs($this->jordi)
        ->get(route('weekly.review'))
        ->assertOk()
        ->assertSee(__('weekly.review.title'));
});

it('enseña la revisión semanal difuminada a un usuario gratuito', function () {
    $gratuito = User::factory()->create(['trial_ends_at' => now()->subDay()]);

    $this->actingAs($gratuito)
        ->get(route('weekly.review'))
        ->assertOk()
        ->assertSee(__('landing.gate.badge'));
});

it('pinta la tarjeta de preferencias del perfil', function () {
    $this->actingAs($this->jordi)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee(__('weekly.settings.title'));
});
