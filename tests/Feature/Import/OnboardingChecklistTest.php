<?php

declare(strict_types=1);

use App\Livewire\OnboardingChecklist;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradingObjective;
use App\Models\User;
use Livewire\Livewire;

it('marca los tres pasos pendientes en una cuenta recién creada', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(OnboardingChecklist::class)
        ->assertSet('done.account', false)
        ->assertSet('done.trades', false)
        ->assertSet('done.goals', false)
        ->assertSee(__('onboarding.title'));
});

it('deduce los pasos del estado real de los datos', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    Trade::factory()->create(['account_id' => $account->id]);
    TradingObjective::create(['user_id' => $user->id, 'text' => 'No mover el stop', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(OnboardingChecklist::class)
        ->assertSet('done.account', true)
        ->assertSet('done.trades', true)
        ->assertSet('done.goals', true)
        ->assertDontSee(__('onboarding.title'));
});

it('no da por hecho ningún paso con solo la cuenta de ejemplo', function () {
    $user = User::factory()->create();
    $sample = Account::factory()->create(['user_id' => $user->id, 'is_sample' => true]);
    Trade::factory()->create(['account_id' => $sample->id]);

    Livewire::actingAs($user)
        ->test(OnboardingChecklist::class)
        ->assertSet('done.account', false)
        ->assertSet('done.trades', false);
});

it('no resucita la guía a quien la ocultó cuando aún se podía', function () {
    // El botón de ocultar se retiró: la guía se pliega con el bloque entero y se
    // va sola al completarse. Pero quien la cerró en su día no tiene por qué
    // volver a encontrársela, así que la marca se sigue respetando.
    $user = User::factory()->create(['onboarding_dismissed_at' => now()]);

    Livewire::actingAs($user)
        ->test(OnboardingChecklist::class)
        ->assertSet('dismissed', true)
        ->assertDontSee(__('onboarding.title'));
});

it('ya no ofrece ocultar la guía', function () {
    // Un cierre definitivo que no se puede deshacer sobra cuando plegar el
    // bloque resuelve el estorbo.
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OnboardingChecklist::class)
        ->assertSee(__('onboarding.title'))
        ->assertDontSee(__('onboarding.dismiss'));
});
