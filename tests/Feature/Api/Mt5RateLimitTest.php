<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * El freno de los endpoints del `.exe`.
 *
 * Son la puerta por la que entran todos los datos del proyecto y estaban sin
 * `throttle`: un agente en bucle, o un `sync_token` filtrado, podían martillear
 * la API sin límite.
 *
 * Van dos topes a la vez porque uno solo no vale: limitando solo por token,
 * quien probase tokens al azar estrenaría cubo con cada intento y la fuerza
 * bruta saldría gratis; limitando solo por IP, una oficina con varios terminales
 * detrás del mismo NAT compartiría cubo sin motivo.
 */
beforeEach(function () {
    Cache::flush();
    RateLimiter::clear('mt5-ip:127.0.0.1');

    $this->user = User::factory()->create();
    $this->token = $this->user->sync_token;

    Account::factory()
        ->balance(100000)
        ->create(['user_id' => $this->user->id, 'mt5_login' => '5000123']);

    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_' . $this->user->id,
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
});

/** Petición mínima de sync, sin operaciones: solo refresca el balance. */
function syncMinimo(array $overrides = []): array
{
    return array_merge([
        'sync_token' => test()->token,
        'account_login' => '5000123',
        'broker' => 'FTMO-Demo',
        'balance' => 100000,
        'trades' => [],
    ], $overrides);
}

it('corta al agente que se pasa del tope por token', function () {
    // Los límites leen la configuración en cada petición, así que se puede
    // bajar el tope aquí en vez de mandar sesenta peticiones.
    config()->set('services.mt5.rate_limit_token', 2);
    config()->set('services.mt5.rate_limit_ip', 100);

    $this->postJson('/api/mt5-sync', syncMinimo())->assertOk();
    $this->postJson('/api/mt5-sync', syncMinimo())->assertOk();

    $this->postJson('/api/mt5-sync', syncMinimo())->assertStatus(429);
});

it('corta por IP aunque el token cambie en cada intento', function () {
    // Este es el caso que el tope por token no cubre: probar tokens al azar.
    // Un token que no existe devuelve 404, y lo que importa es que el tercer
    // intento ya no llegue ni al controlador.
    config()->set('services.mt5.rate_limit_token', 100);
    config()->set('services.mt5.rate_limit_ip', 2);

    $this->postJson('/api/mt5-sync', syncMinimo(['sync_token' => 'intento-1']))->assertStatus(404);
    $this->postJson('/api/mt5-sync', syncMinimo(['sync_token' => 'intento-2']))->assertStatus(404);

    $this->postJson('/api/mt5-sync', syncMinimo(['sync_token' => 'intento-3']))->assertStatus(429);
});

it('no mezcla el cubo de dos agentes con tokens distintos', function () {
    // Dos terminales del mismo usuario, o de usuarios distintos, no deben
    // frenarse entre ellos mientras la IP aguante.
    config()->set('services.mt5.rate_limit_token', 1);
    config()->set('services.mt5.rate_limit_ip', 100);

    $otro = User::factory()->create();
    Account::factory()->create(['user_id' => $otro->id, 'mt5_login' => '5000999']);
    $otro->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_' . $otro->id,
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    $this->postJson('/api/mt5-sync', syncMinimo())->assertOk();

    $this->postJson('/api/mt5-sync', syncMinimo([
        'sync_token' => $otro->sync_token,
        'account_login' => '5000999',
    ]))->assertOk();
});

it('protege también el reset y los dos endpoints de velas', function () {
    // El tope es del grupo entero y se comparte entre las cuatro rutas: si
    // alguna se quedara fuera, bastaría con machacar esa.
    config()->set('services.mt5.rate_limit_ip', 1);

    // La primera gasta el único hueco que hay. Da igual cómo acabe: lo que se
    // fija aquí es que a partir de ahí no se entra por ninguna puerta.
    $this->postJson('/api/mt5-sync', syncMinimo());

    foreach (['/api/mt5-sync', '/api/mt5-reset', '/api/mt5-refresh-charts', '/api/mt5-update-chart'] as $ruta) {
        $this->postJson($ruta, syncMinimo())->assertStatus(429);
    }
});
