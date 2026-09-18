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

it('recuerda que el usuario cerró la guía', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OnboardingChecklist::class)
        ->call('dismiss')
        ->assertSet('dismissed', true)
        ->assertDontSee(__('onboarding.title'));

    expect($user->fresh()->onboarding_dismissed_at)->not->toBeNull();

    Livewire::actingAs($user->fresh())
        ->test(OnboardingChecklist::class)
        ->assertSet('dismissed', true);
});
