<?php

declare(strict_types=1);

/**
 * PWA instalable (R4, sin push).
 *
 * El manifiesto se sirve desde una ruta para poder traducirlo, y la pantalla sin
 * conexión tiene que ser pública: el service worker la guarda al instalarse,
 * cuando puede no haber sesión.
 */
it('sirve el manifiesto con el tipo de contenido correcto', function () {
    $response = $this->get(route('pwa.manifest'));

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/manifest+json');
});

it('describe una aplicación instalable', function () {
    $manifest = $this->get(route('pwa.manifest'))->json();

    expect($manifest['display'])->toBe('standalone')
        ->and($manifest['start_url'])->toBe('/dashboard')
        ->and($manifest['icons'])->toHaveCount(3)
        ->and(collect($manifest['icons'])->pluck('purpose'))->toContain('maskable');
});

it('traduce el nombre del manifiesto al idioma de la sesión', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('pwa.manifest'))
        ->assertJsonPath('name', 'TradeForge — Trading journal');

    $this->withSession(['locale' => 'es'])
        ->get(route('pwa.manifest'))
        ->assertJsonPath('name', 'TradeForge — Diario de trading');
});

it('sirve la pantalla sin conexión sin necesidad de sesión', function () {
    $this->get(route('pwa.offline'))
        ->assertOk()
        ->assertSee(__('pwa.offline.title'));
});

it('publica el service worker en la raíz, que es donde puede controlar la aplicación', function () {
    expect(file_exists(public_path('sw.js')))->toBeTrue();
});
