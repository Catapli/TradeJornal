<?php

declare(strict_types=1);

use App\Livewire\OnboardingChecklist;
use App\Livewire\PreMarketRitual;
use App\Livewire\SampleDataCard;
use App\Models\User;
use Livewire\Livewire;

/**
 * Las tres tarjetas de puesta en marcha se apilan justo donde un principiante
 * espera ver sus números, y son las que más sitio ocupan precisamente cuando
 * menos datos hay. Ahora se pueden plegar.
 *
 * El estado del plegado vive en localStorage, así que lo único que se puede
 * fijar aquí es que **el enganche está en el marcado**: si alguien se lleva por
 * delante el `x-data` o el botón, estos tests lo cantan. Que la tarjeta se
 * pliegue de verdad y recuerde el estado es comprobación manual —está en
 * `VALIDACION.MD`—, igual que pasa con todo lo que resuelve Alpine.
 */
beforeEach(function () {
    // Usuario recién registrado: sin cuentas, sin operaciones y sin ritual hecho,
    // que es cuando las tres tarjetas salen a la vez.
    $this->novato = User::factory()->create();
});

it('deja plegar la guía de primeros pasos', function () {
    Livewire::actingAs($this->novato)
        ->test(OnboardingChecklist::class)
        ->assertSee("tfMinimizable('onboarding')", false)
        ->assertSee(__('labels.card_minimize'), false)
        ->assertSee(__('onboarding.title'));
});

it('deja plegar el ritual pre-mercado sin esconder el botón de empezar', function () {
    // Plegar no puede convertirse en cerrar: el ritual son 60 segundos y su CTA
    // se queda a la vista.
    Livewire::actingAs($this->novato)
        ->test(PreMarketRitual::class)
        ->assertSee("tfMinimizable('ritual')", false)
        ->assertSee(__('labels.card_minimize'), false)
        ->assertSee(__('ritual.card.cta'));
});

it('deja plegar la invitación a los datos de ejemplo', function () {
    Livewire::actingAs($this->novato)
        ->test(SampleDataCard::class)
        ->assertSee("tfMinimizable('sample')", false)
        ->assertSee(__('labels.card_minimize'), false)
        ->assertSee(__('sample.cta_title'));
});

it('no pinta ninguna tarjeta plegable cuando la guía ya está descartada', function () {
    // Plegar es para lo que sigue haciendo falta; lo descartado no vuelve.
    $this->novato->forceFill(['onboarding_dismissed_at' => now()])->save();

    Livewire::actingAs($this->novato->fresh())
        ->test(OnboardingChecklist::class)
        ->assertDontSee("tfMinimizable('onboarding')", false);
});
