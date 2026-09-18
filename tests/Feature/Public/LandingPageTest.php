<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Fase 1 del ROADMAP: la raíz servía un redirect a /login, así que no había forma
 * de conocer el producto ni su precio sin registrarse antes.
 */
it('sirve la landing a los visitantes sin sesión', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('landing.hero.title'))
        ->assertSee(__('landing.hero.cta_demo'));
});

it('lleva al dashboard a quien ya tiene sesión', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertRedirect(route('dashboard'));
});

it('muestra los precios sin necesidad de iniciar sesión', function () {
    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee(__('landing.pricing.free.name'))
        ->assertSee(__('landing.pricing.pro.name'));
});

it('sirve los precios dentro de la aplicación cuando hay sesión', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('pricing'))
        ->assertOk()
        ->assertSee(__('landing.pricing.pro_cta'));
});

it('enseña la comparativa completa de planes en la página de precios', function () {
    $features = __('landing.pricing.features');

    $response = $this->get(route('pricing'))->assertOk();

    foreach ($features as $feature) {
        $response->assertSee($feature['label']);
    }
});

it('no anuncia cuentas ilimitadas mientras el código las limite a tres', function () {
    // El texto viejo de la pricing prometía "Cuentas Ilimitadas" y AccountPage
    // cortaba en 3 sin mirar el plan. Este test evita que vuelva a colarse.
    $this->get(route('pricing'))
        ->assertOk()
        ->assertDontSee('Cuentas Ilimitadas')
        ->assertDontSee('Unlimited accounts');
});

it('sirve la landing en inglés cuando el idioma de la sesión es en', function () {
    $this->withSession(['locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee(__('landing.hero.title', [], 'en'));
});
