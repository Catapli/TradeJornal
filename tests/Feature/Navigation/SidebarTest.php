<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;

/**
 * El carril lateral y las cuatro puertas que salieron de él.
 *
 * Al quitar un icono del menú, el riesgo real es dejar la pantalla huérfana: sin
 * enlace en ningún sitio, existe pero no se llega. Estos tests son justo eso —
 * que cada destino retirado sigue teniendo su entrada donde toca.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    Account::factory()->create(['user_id' => $this->jordi->id, 'is_sample' => false]);
});

/** Recorta el <aside> del HTML: el resto de la página enlaza a lo que le da la gana. */
function soloElCarril(string $html): string
{
    preg_match('#<aside.*?</aside>#s', $html, $match);

    return $match[0] ?? '';
}

it('deja en el carril los ocho destinos del día a día', function () {
    $sidebar = soloElCarril($this->actingAs($this->jordi)->get(route('dashboard'))->getContent());

    expect($sidebar)->not->toBeEmpty();

    foreach (['dashboard', 'cuentas', 'trades', 'session', 'journal', 'weekly.review', 'reports', 'playbook'] as $name) {
        expect($sidebar)->toContain(route($name));
    }
});

it('saca del carril los cuatro destinos puntuales', function () {
    // Ojo: `importar` sigue apareciendo en la página, pero dentro de
    // `window.tjRoutes`, que es lo que usa el atajo de teclado «I». Lo que se
    // comprueba aquí es que ya no ocupa un icono permanente en el menú.
    $sidebar = soloElCarril($this->actingAs($this->jordi)->get(route('dashboard'))->getContent());

    expect($sidebar)->not->toBeEmpty();

    foreach (['trades.import', 'sync.settings', 'session-history', 'backtesting'] as $name) {
        expect($sidebar)->not->toContain(route($name));
    }
});

it('mantiene la entrada a importar dentro de operaciones', function () {
    $this->actingAs($this->jordi)
        ->get(route('trades'))
        ->assertOk()
        ->assertSee(route('trades.import'), false);
});

it('mantiene la entrada a la conexión dentro de cuentas', function () {
    $this->actingAs($this->jordi)
        ->get(route('cuentas'))
        ->assertOk()
        ->assertSee(route('sync.settings'), false);
});

it('mantiene la entrada al histórico dentro de sesión', function () {
    $this->actingAs($this->jordi)
        ->get(route('session'))
        ->assertOk()
        ->assertSee(route('session-history'), false);
});

it('mantiene la entrada al backtesting dentro de estrategias', function () {
    $this->actingAs($this->jordi)
        ->get(route('playbook'))
        ->assertOk()
        ->assertSee(route('backtesting'), false);
});

it('sigue sirviendo las cuatro pantallas retiradas del carril', function () {
    foreach (['trades.import', 'sync.settings', 'session-history', 'backtesting'] as $name) {
        $this->actingAs($this->jordi)->get(route($name))->assertOk();
    }
});
