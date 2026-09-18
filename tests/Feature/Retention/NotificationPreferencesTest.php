<?php

declare(strict_types=1);

use App\Livewire\LanguageManager;
use App\Livewire\Settings\NotificationPreferences;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/**
 * Idioma, huso horario y baja del resumen semanal.
 *
 * Las tres preferencias existen por el mismo motivo: el correo se compone desde
 * la cola, sin sesión, así que lo que no esté en la ficha del usuario no existe.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create(['timezone' => null, 'locale' => null]);
});

it('guarda huso horario, idioma y la preferencia de correo', function () {
    Livewire::actingAs($this->jordi)
        ->test(NotificationPreferences::class)
        ->set('timezone', 'America/Mexico_City')
        ->set('locale', 'en')
        ->set('weeklySummary', false)
        ->call('save');

    $this->jordi->refresh();

    expect($this->jordi->timezone)->toBe('America/Mexico_City')
        ->and($this->jordi->locale)->toBe('en')
        ->and($this->jordi->weekly_summary)->toBeFalse();
});

it('rechaza un huso horario inventado', function () {
    Livewire::actingAs($this->jordi)
        ->test(NotificationPreferences::class)
        ->set('timezone', 'Marte/Olympus')
        ->call('save')
        ->assertHasErrors('timezone');

    expect($this->jordi->refresh()->timezone)->toBeNull();
});

it('rechaza un idioma que no está traducido', function () {
    Livewire::actingAs($this->jordi)
        ->test(NotificationPreferences::class)
        ->set('locale', 'zz')
        ->call('save')
        ->assertHasErrors('locale');
});

it('viene activado de fábrica y usa el huso de la aplicación mientras no elijan otro', function () {
    expect($this->jordi->weekly_summary)->toBeTrue()
        ->and($this->jordi->preferredTimezone())->toBe(config('app.timezone'))
        ->and($this->jordi->preferredLocale())->toBe(config('app.locale'));
});

it('el selector de idioma también deja el idioma escrito en la ficha', function () {
    Livewire::actingAs($this->jordi)
        ->test(LanguageManager::class)
        ->call('changeLocale', 'en');

    expect($this->jordi->refresh()->locale)->toBe('en');
});

it('el selector de idioma ignora un idioma no soportado', function () {
    Livewire::actingAs($this->jordi)
        ->test(LanguageManager::class)
        ->call('changeLocale', 'zz');

    expect($this->jordi->refresh()->locale)->toBeNull();
});

// ─────────────────────────────────────────────────────────────
// Firebase
// ─────────────────────────────────────────────────────────────

it('no queda rastro de Firebase', function () {
    // Se retiró en la Fase 4: la integración nunca tuvo consumidor y guardaba un
    // identificador de dispositivo por usuario que nadie leía.
    expect(Schema::hasColumn('users', 'fcm_token'))->toBeFalse()
        ->and(config('services.firebase'))->toBeNull()
        ->and(file_exists(config_path('firebase.php')))->toBeFalse()
        ->and(json_decode(file_get_contents(base_path('composer.json')), true)['require'])
        ->not->toHaveKey('kreait/laravel-firebase');
});
