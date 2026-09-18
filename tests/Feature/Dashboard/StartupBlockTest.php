<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradingObjective;
use App\Models\User;

/**
 * El bloque de puesta en marcha del panel.
 *
 * Las tres tarjetas (ritual, primeros pasos y datos de ejemplo) se apilan justo
 * donde un principiante espera ver sus números. Primero se hicieron plegables
 * una a una y no servía de nada: tres tarjetas plegadas siguen siendo tres
 * barras. Ahora se pliega el bloque entero y queda una sola línea.
 *
 * El plegado en sí vive en Alpine y en localStorage, así que aquí solo se puede
 * fijar que **el enganche está en la página** y que el bloque no se pinta
 * cuando no hay nada que enseñar. Lo demás es comprobación manual.
 */
it('envuelve las tres tarjetas en un solo bloque plegable', function () {
    // Usuario recién registrado: sin cuentas, sin operaciones y sin ritual hecho,
    // que es cuando las tres salen a la vez.
    $novato = User::factory()->create();

    $this->actingAs($novato)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('tfStartupBlock()', false)
        ->assertSee(__('labels.startup_block'))
        // Las tres siguen dentro, cada una entera.
        ->assertSee(__('ritual.card.title'))
        ->assertSee(__('onboarding.title'))
        ->assertSee(__('sample.cta_title'));
});

it('ya no pliega las tarjetas por separado', function () {
    // El galón por tarjeta se retiró: si vuelve, este test lo canta.
    $novato = User::factory()->create();

    $this->actingAs($novato)
        ->get(route('dashboard'))
        ->assertDontSee("tfMinimizable('onboarding')", false)
        ->assertDontSee("tfMinimizable('ritual')", false)
        ->assertDontSee("tfMinimizable('sample')", false);
});

it('no pinta el bloque cuando ya no queda ninguna tarjeta', function () {
    // Con los tres pasos hechos y la guía descartada, el panel tiene que quedarse
    // limpio: ni bloque, ni barra, ni hueco.
    $listo = User::factory()->create(['onboarding_dismissed_at' => now()]);
    $cuenta = Account::factory()->create(['user_id' => $listo->id]);
    Trade::factory()->create(['account_id' => $cuenta->id]);
    TradingObjective::create(['user_id' => $listo->id, 'text' => 'No mover el stop', 'is_active' => true]);

    $this->actingAs($listo)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('onboarding.title'))
        ->assertDontSee(__('sample.cta_title'));
});
