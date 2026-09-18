<?php

declare(strict_types=1);

use App\Models\TradingObjective;
use App\Models\User;
use App\Support\Demo;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MistakesSeeder;
use Database\Seeders\PropFirmsSeeder;
use Illuminate\Support\Facades\Route;

/**
 * La demo pública autentica un usuario sembrado y marca la sesión; a partir de
 * ahí DemoGuard cancela toda escritura de Eloquent. Lo que se prueba aquí es
 * justamente eso: que se entra, y que no se puede escribir nada.
 */
function seedDemo(): void
{
    (new PropFirmsSeeder)->run();
    (new MistakesSeeder)->run();
    (new DemoSeeder)->run();
}

it('redirige al registro si todavía no hay usuario de demo sembrado', function () {
    $this->get(route('demo.enter'))->assertRedirect(route('register'));
});

it('autentica el usuario de demo y lleva al dashboard', function () {
    seedDemo();

    $this->get(route('demo.enter'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs(Demo::user());
    expect(session(Demo::SESSION_KEY))->toBeTrue();
});

it('deja ver el producto completo: el usuario de demo cuenta como PRO', function () {
    seedDemo();

    expect(Demo::user()->subscribed('default'))->toBeTrue();
});

it('no toca la sesión de un usuario real que entra en /demo', function () {
    seedDemo();

    $real = User::factory()->create();

    $this->actingAs($real)
        ->get(route('demo.enter'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($real);
    expect(session(Demo::SESSION_KEY))->toBeNull();
});

it('cancela cualquier escritura de Eloquent durante la demo', function () {
    seedDemo();

    // Sonda: una ruta con el grupo `web` completo, que es donde vive DemoGuard.
    Route::middleware('web')->get('/__demo-write-probe', function () {
        $objective = TradingObjective::create([
            'user_id' => auth()->id(),
            'text' => 'Regla escrita desde la demo',
            'is_active' => true,
        ]);

        return response()->json(['exists' => $objective->exists]);
    });

    $this->get(route('demo.enter'));

    $this->get('/__demo-write-probe')
        ->assertOk()
        ->assertJson(['exists' => false]);

    $this->assertDatabaseMissing('trading_objectives', [
        'text' => 'Regla escrita desde la demo',
    ]);
});

it('permite escribir con normalidad fuera de la demo', function () {
    // Contraprueba: si la sonda tampoco escribiera sin demo, el test anterior
    // estaría pasando por el motivo equivocado.
    Route::middleware('web')->get('/__demo-write-probe', function () {
        $objective = TradingObjective::create([
            'user_id' => auth()->id(),
            'text' => 'Regla escrita fuera de la demo',
            'is_active' => true,
        ]);

        return response()->json(['exists' => $objective->exists]);
    });

    $this->actingAs(User::factory()->create())
        ->get('/__demo-write-probe')
        ->assertOk()
        ->assertJson(['exists' => true]);

    $this->assertDatabaseHas('trading_objectives', [
        'text' => 'Regla escrita fuera de la demo',
    ]);
});

it('cierra la sesión de demo al salir', function () {
    seedDemo();

    $this->get(route('demo.enter'));
    $this->get(route('demo.exit'))->assertRedirect(route('landing'));

    $this->assertGuest();
});

it('no deja subir ficheros durante la demo', function () {
    seedDemo();

    $this->get(route('demo.enter'));

    $this->post(route('journal.upload'))->assertForbidden();
});
