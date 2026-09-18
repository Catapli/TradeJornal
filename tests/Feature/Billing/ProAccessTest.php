<?php

declare(strict_types=1);

use App\Livewire\ReportsPage;
use App\Models\User;
use Laravel\Cashier\Subscription;
use Livewire\Livewire;

function paying(): User
{
    $user = User::factory()->create(['trial_ends_at' => null]);

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

function trialing(int $days = 14): User
{
    return User::factory()->create(['trial_ends_at' => now()->addDays($days)]);
}

function freeUser(): User
{
    return User::factory()->create(['trial_ends_at' => now()->subDay()]);
}

// ─────────────────────────────────────────────────────────────
// hasProAccess: la única puerta
// ─────────────────────────────────────────────────────────────

it('da acceso PRO a quien paga y a quien está de prueba, y a nadie más', function () {
    expect(paying()->hasProAccess())->toBeTrue()
        ->and(trialing()->hasProAccess())->toBeTrue()
        ->and(freeUser()->hasProAccess())->toBeFalse()
        ->and(User::factory()->create(['trial_ends_at' => null])->hasProAccess())->toBeFalse();
});

it('no considera en prueba a quien ya paga', function () {
    $user = paying();
    $user->forceFill(['trial_ends_at' => now()->addDays(5)])->save();

    expect($user->onProTrial())->toBeFalse()
        ->and($user->trialDaysLeft())->toBe(0);
});

it('cuenta los días que quedan de prueba', function () {
    expect(trialing(14)->trialDaysLeft())->toBe(14)
        ->and(trialing(1)->trialDaysLeft())->toBe(1)
        ->and(freeUser()->trialDaysLeft())->toBe(0);
});

it('arranca la prueba al registrarse', function () {
    $this->post(route('register'), [
        'name' => 'Nuevo Trader',
        'email' => 'nuevo@ejemplo.test',
        'password' => 'contrasena-larga-123',
        'password_confirmation' => 'contrasena-larga-123',
    ]);

    $user = User::where('email', 'nuevo@ejemplo.test')->firstOrFail();

    expect($user->onProTrial())->toBeTrue()
        ->and($user->hasProAccess())->toBeTrue()
        ->and($user->trialDaysLeft())->toBe((int) config('billing.trial_days'));
});

it('no arranca ninguna prueba si está desactivada', function () {
    config(['billing.trial_days' => 0]);

    $this->post(route('register'), [
        'name' => 'Sin Prueba',
        'email' => 'sinprueba@ejemplo.test',
        'password' => 'contrasena-larga-123',
        'password_confirmation' => 'contrasena-larga-123',
    ]);

    expect(User::where('email', 'sinprueba@ejemplo.test')->firstOrFail()->trial_ends_at)->toBeNull();
});

// ─────────────────────────────────────────────────────────────
// Los módulos PRO durante la prueba
// ─────────────────────────────────────────────────────────────

it('abre los módulos PRO durante la prueba', function (string $route) {
    $this->actingAs(trialing())
        ->get(route($route))
        ->assertOk()
        ->assertDontSee(__('landing.gate.badge'));
})->with(['journal', 'session', 'reports', 'playbook', 'backtesting']);

it('vuelve a poner el muro cuando la prueba caduca', function () {
    $this->actingAs(freeUser())
        ->get(route('reports'))
        ->assertOk()
        ->assertSee(__('landing.gate.badge'));
});

// ─────────────────────────────────────────────────────────────
// Cierre en servidor de los módulos PRO
// ─────────────────────────────────────────────────────────────

it('deja renderizar la vista previa difuminada al usuario gratuito', function () {
    // El render inicial no es una petición de Livewire: es el escaparate.
    $this->actingAs(freeUser())->get(route('reports'))->assertOk();
});

it('corta cualquier acción de un módulo PRO invocada por un usuario gratuito', function () {
    // Antes de la Fase 3 el muro era solo visual: la acción se ejecutaba igual.
    Livewire::actingAs(freeUser())
        ->test(ReportsPage::class)
        ->call('$refresh')
        ->assertStatus(403);
});

it('deja trabajar en los módulos PRO a quien está de prueba', function () {
    Livewire::actingAs(trialing())
        ->test(ReportsPage::class)
        ->call('$refresh')
        ->assertOk();
});

// ─────────────────────────────────────────────────────────────
// Créditos de IA
// ─────────────────────────────────────────────────────────────

it('aplica el cupo de IA de PRO durante la prueba', function () {
    expect(trialing()->aiDailyLimit())->toBe((int) config('services.groq.daily_limit_pro'))
        ->and(freeUser()->aiDailyLimit())->toBe((int) config('services.groq.daily_limit_free'));
});

it('descuenta del cupo lo ya consumido hoy', function () {
    $user = freeUser();
    $limit = $user->aiDailyLimit();

    \App\Models\AiUsage::create([
        'user_id' => $user->id,
        'date' => today()->toDateString(),
        'count' => 2,
    ]);

    expect($user->aiCreditsLeft())->toBe(max(0, $limit - 2));
});

it('enseña el contador de IA en la barra de navegación', function () {
    $user = freeUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($user->aiCreditsLeft() . '/' . $user->aiDailyLimit());
});
