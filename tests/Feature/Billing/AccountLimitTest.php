<?php

declare(strict_types=1);

use App\Livewire\AccountPage;
use App\Models\Account;
use App\Models\User;
use Laravel\Cashier\Subscription;
use Livewire\Livewire;

function payingUser(): User
{
    $user = User::factory()->create(['trial_ends_at' => null]);

    Subscription::create([
        'user_id' => $user->id,
        'type' => 'default',
        'stripe_id' => 'sub_limit_' . $user->id,
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    return $user->refresh();
}

function expiredUser(): User
{
    return User::factory()->create(['trial_ends_at' => now()->subDay()]);
}

it('deja al plan gratuito llegar hasta su tope', function () {
    $user = expiredUser();
    $limit = (int) config('billing.accounts.free');

    Account::factory()->count($limit - 1)->create(['user_id' => $user->id]);

    expect($user->canCreateAccount())->toBeTrue();

    Account::factory()->create(['user_id' => $user->id]);

    expect($user->fresh()->canCreateAccount())->toBeFalse();
});

it('no pone tope a quien tiene PRO', function () {
    // Este era el bug: la pricing anunciaba cuentas ilimitadas y el código
    // cortaba en 3 sin mirar la suscripción.
    $user = payingUser();
    Account::factory()->count(8)->create(['user_id' => $user->id]);

    expect($user->accountLimit())->toBeNull()
        ->and($user->canCreateAccount())->toBeTrue();
});

it('tampoco pone tope durante la prueba', function () {
    $user = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    Account::factory()->count(6)->create(['user_id' => $user->id]);

    expect($user->canCreateAccount())->toBeTrue();
});

it('no cuenta las cuentas quemadas contra el tope', function () {
    $user = expiredUser();
    $limit = (int) config('billing.accounts.free');

    Account::factory()->count($limit)->create(['user_id' => $user->id, 'status' => 'burned']);

    expect($user->canCreateAccount())->toBeTrue();
});

it('conserva las cuentas de sobra al degradar y solo frena las nuevas', function () {
    // Al caducar la prueba, quien tenga más cuentas de las que permite Free no
    // pierde ninguna: el límite solo impide crear más.
    $user = User::factory()->create(['trial_ends_at' => now()->addDay()]);
    Account::factory()->count(6)->create(['user_id' => $user->id]);

    $user->forceFill(['trial_ends_at' => now()->subDay()])->save();
    $user->refresh();

    expect($user->hasProAccess())->toBeFalse()
        ->and($user->canCreateAccount())->toBeFalse()
        ->and(Account::where('user_id', $user->id)->count())->toBe(6);
});

it('avisa con el límite de su plan al intentar pasarse', function () {
    $user = expiredUser();
    Account::factory()->count((int) config('billing.accounts.free'))->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(AccountPage::class)
        ->call('insertAccount')
        ->assertDispatched('show-alert', function (string $event, array $params) {
            return str_contains($params[0]['message'], (string) config('billing.accounts.free'));
        });

    expect(Account::where('user_id', $user->id)->count())->toBe((int) config('billing.accounts.free'));
});

it('la página de precios ya no dice que PRO tenga tope de cuentas', function () {
    // La primera fila de la matriz es la de cuentas. Decía «Hasta 3» en ambos
    // planes porque era la verdad mientras el código no miraba la suscripción.
    $accountsRow = collect(__('landing.pricing.features'))->first();

    expect($accountsRow['free'])->toBe('Hasta 3')
        ->and($accountsRow['pro'])->toBe('Ilimitadas');
});
