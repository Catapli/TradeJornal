<?php

declare(strict_types=1);

use App\Models\User;

/**
 * La prueba de 14 días para los que ya estaban.
 *
 * La prueba se marca en `CreateNewUser`, así que solo la reciben los que se
 * registran después de la Fase 3: los usuarios anteriores se quedaron con
 * `trial_ends_at` a null y sin acceso a los módulos PRO que nunca han podido
 * probar. Quedó anotado en el roadmap y llevaba ahí desde el 26 de agosto.
 */
beforeEach(function () {
    config()->set('billing.trial_days', 14);
    config()->set('demo.email', 'demo@tradeforge.app');
});

/** Usuario con una suscripción viva en el estado que se le pase. */
function conSuscripcion(User $user, string $estado = 'active'): User
{
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_' . $user->id,
        'stripe_status' => $estado,
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    return $user;
}

it('da la prueba a quien no la ha tenido nunca', function () {
    $antiguo = User::factory()->create(['trial_ends_at' => null]);

    $this->artisan('prueba:retroactiva')->assertSuccessful();

    expect($antiguo->fresh()->trial_ends_at?->toDateString())
        ->toBe(now()->addDays(14)->toDateString());
});

it('no se la da dos veces a quien ya la gastó', function () {
    // Volver a dársela sería regalar tiempo a quien ya lo consumió.
    $gastada = User::factory()->create(['trial_ends_at' => now()->subMonth()]);

    $this->artisan('prueba:retroactiva')->assertSuccessful();

    expect($gastada->fresh()->trial_ends_at->toDateString())
        ->toBe(now()->subMonth()->toDateString());
});

it('deja fuera a quien ya está suscrito', function () {
    $suscrito = conSuscripcion(User::factory()->create(['trial_ends_at' => null]));

    $this->artisan('prueba:retroactiva')->assertSuccessful();

    expect($suscrito->fresh()->trial_ends_at)->toBeNull();
});

it('sí se la da a quien tuvo suscripción y la canceló', function () {
    // Una suscripción cancelada no da acceso, así que ese usuario está tan
    // fuera del producto como el que nunca pagó.
    $cancelado = conSuscripcion(User::factory()->create(['trial_ends_at' => null]), 'canceled');

    $this->artisan('prueba:retroactiva')->assertSuccessful();

    expect($cancelado->fresh()->trial_ends_at)->not->toBeNull();
});

it('deja fuera al usuario de la demo', function () {
    // No es una persona: es el escaparate público.
    $demo = User::factory()->create(['email' => 'demo@tradeforge.app', 'trial_ends_at' => null]);

    $this->artisan('prueba:retroactiva')->assertSuccessful();

    expect($demo->fresh()->trial_ends_at)->toBeNull();
});

it('en seco no escribe nada', function () {
    $antiguo = User::factory()->create(['trial_ends_at' => null]);

    $this->artisan('prueba:retroactiva --dry-run')->assertSuccessful();

    expect($antiguo->fresh()->trial_ends_at)->toBeNull();
});

it('acepta un número de días distinto', function () {
    $antiguo = User::factory()->create(['trial_ends_at' => null]);

    $this->artisan('prueba:retroactiva --days=30')->assertSuccessful();

    expect($antiguo->fresh()->trial_ends_at->toDateString())
        ->toBe(now()->addDays(30)->toDateString());
});

it('puede apuntar a un solo usuario por correo', function () {
    $elegido = User::factory()->create(['email' => 'elegido@ejemplo.com', 'trial_ends_at' => null]);
    $otro = User::factory()->create(['trial_ends_at' => null]);

    $this->artisan('prueba:retroactiva --user=elegido@ejemplo.com')->assertSuccessful();

    expect($elegido->fresh()->trial_ends_at)->not->toBeNull()
        ->and($otro->fresh()->trial_ends_at)->toBeNull();
});

it('no hace nada con días inválidos', function () {
    // Con TRIAL_DAYS=0 la prueba está desactivada a propósito: el comando no
    // puede saltársela dando cero días y dejando a todos con la fecha de hoy.
    config()->set('billing.trial_days', 0);
    $antiguo = User::factory()->create(['trial_ends_at' => null]);

    $this->artisan('prueba:retroactiva')->assertFailed();

    expect($antiguo->fresh()->trial_ends_at)->toBeNull();
});

it('deja el acceso PRO abierto en cuanto se la das', function () {
    // Es el efecto que se busca: `hasProAccess()` mira la prueba además de la
    // suscripción, así que el usuario entra sin tocar Stripe.
    $antiguo = User::factory()->create(['trial_ends_at' => null]);

    expect($antiguo->hasProAccess())->toBeFalse();

    $this->artisan('prueba:retroactiva')->assertSuccessful();

    expect($antiguo->fresh()->hasProAccess())->toBeTrue();
});
