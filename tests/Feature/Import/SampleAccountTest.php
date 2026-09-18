<?php

declare(strict_types=1);

use App\Actions\Accounts\ManageSampleAccount;
use App\Livewire\SampleDataCard;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\Trade;
use App\Models\User;
use Database\Seeders\PropFirmsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    // La cuenta de ejemplo necesita un nivel de programa real: sus dos claves
    // foráneas a programa y objetivo son NOT NULL.
    (new PropFirmsSeeder)->run();
});

it('crea una cuenta de ejemplo con historial creíble', function () {
    $user = User::factory()->create();

    $account = app(ManageSampleAccount::class)->create($user);

    expect($account->is_sample)->toBeTrue()
        ->and($account->name)->toBe(__('sample.account_name'));

    $trades = Trade::where('account_id', $account->id)->get();

    expect($trades->count())->toBeGreaterThan(80);

    // El balance sale del histórico y cae cerca del objetivo del perfil (+8 %).
    $growth = ((float) $account->current_balance - 50000) / 50000 * 100;

    expect($growth)->toBeGreaterThan(4.0)->toBeLessThan(12.0);
});

it('no crea dos cuentas de ejemplo para el mismo usuario', function () {
    $user = User::factory()->create();
    $manage = app(ManageSampleAccount::class);

    $first = $manage->create($user);
    $second = $manage->create($user);

    expect($second->id)->toBe($first->id)
        ->and(Account::where('user_id', $user->id)->where('is_sample', true)->count())->toBe(1);
});

it('borra la cuenta de ejemplo con todas sus operaciones', function () {
    $user = User::factory()->create();
    $manage = app(ManageSampleAccount::class);

    $account = $manage->create($user);
    $manage->destroy($user);

    expect(Account::where('id', $account->id)->exists())->toBeFalse()
        ->and(Trade::where('account_id', $account->id)->count())->toBe(0)
        ->and(Strategy::where('user_id', $user->id)->count())->toBe(0);
});

it('no borra la estrategia de ejemplo si el usuario le puso operaciones suyas', function () {
    $user = User::factory()->create();
    $manage = app(ManageSampleAccount::class);
    $manage->create($user);

    $strategy = Strategy::where('user_id', $user->id)->firstOrFail();
    $ownAccount = Account::factory()->create(['user_id' => $user->id]);
    Trade::factory()->create(['account_id' => $ownAccount->id, 'strategy_id' => $strategy->id]);

    $manage->destroy($user);

    expect(Strategy::where('id', $strategy->id)->exists())->toBeTrue();
});

it('los datos de ejemplo no se cuentan como operaciones propias', function () {
    $user = User::factory()->create();
    app(ManageSampleAccount::class)->create($user);

    Livewire::actingAs($user)
        ->test(SampleDataCard::class)
        ->assertSet('hasSample', true)
        ->assertSet('hasTrades', false)
        ->assertSee(__('sample.banner_title'));
});

it('ofrece los datos de ejemplo al usuario con el panel a cero', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(SampleDataCard::class)
        ->assertSet('hasSample', false)
        ->assertSee(__('sample.cta_title'));
});

it('no ofrece nada al usuario que ya tiene operaciones suyas', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    Trade::factory()->create(['account_id' => $account->id]);

    Livewire::actingAs($user)
        ->test(SampleDataCard::class)
        ->assertSet('hasTrades', true)
        ->assertDontSee(__('sample.cta_title'))
        ->assertDontSee(__('sample.banner_title'));
});

it('crea y borra los datos de ejemplo desde la tarjeta', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SampleDataCard::class)
        ->call('create')
        ->assertSet('hasSample', true);

    expect(Account::where('user_id', $user->id)->where('is_sample', true)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test(SampleDataCard::class)
        ->call('destroySample')
        ->assertSet('hasSample', false);

    expect(Account::where('user_id', $user->id)->where('is_sample', true)->exists())->toBeFalse();
});
