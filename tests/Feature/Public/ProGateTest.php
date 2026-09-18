<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Cashier\Subscription;

/**
 * Fase 1 del ROADMAP: los cinco módulos de pago dejaban de aparecer en el sidebar
 * para el usuario gratuito, que así no podía saber qué estaría comprando. Ahora se
 * ven con candado y la pantalla real se sirve difuminada tras el muro de venta.
 */
function subscribedUser(): User
{
    $user = User::factory()->create();

    Subscription::create([
        'user_id' => $user->id,
        'type' => 'default',
        'stripe_id' => 'sub_test_' . $user->id,
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    return $user->refresh();
}

it('enseña el muro de venta al usuario gratuito en cada módulo PRO', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk()
        ->assertSee(__('landing.gate.badge'))
        ->assertSee(__('landing.gate.cta'));
})->with(['journal', 'session', 'session-history', 'reports', 'playbook', 'backtesting']);

it('no interpone nada al usuario suscrito', function (string $route) {
    $this->actingAs(subscribedUser())
        ->get(route($route))
        ->assertOk()
        ->assertDontSee(__('landing.gate.badge'));
})->with(['journal', 'session', 'session-history', 'reports', 'playbook', 'backtesting']);

it('lista los módulos PRO en el sidebar aunque el usuario sea gratuito', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('journal'))
        ->assertSee(route('reports'))
        ->assertSee(route('playbook'))
        // Backtesting salió del carril: ahora se entra desde Estrategias, y su
        // muro se comprueba en el caso de arriba. Lo cubre SidebarTest.
        ->assertSee(route('weekly.review'))
        ->assertSee(__('landing.gate.tag'));
});
