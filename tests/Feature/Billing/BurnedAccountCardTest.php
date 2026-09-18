<?php

declare(strict_types=1);

use App\Livewire\BurnedAccountCard;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Database\Seeders\MistakesSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function burnedAccount(User $user): Account
{
    return Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Challenge 10k',
        'status' => 'burned',
        'initial_balance' => 10000,
        'updated_at' => now()->subDays(2),
    ]);
}

it('no enseña nada si no hay ninguna cuenta quemada', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(BurnedAccountCard::class)
        ->assertSet('accountId', null)
        ->assertDontSee(__('recovery.cta_title'));
});

it('enseña la autopsia de la cuenta recién quemada', function () {
    $user = User::factory()->create();
    $account = burnedAccount($user);
    Trade::factory()->count(3)->create(['account_id' => $account->id, 'pnl' => -400]);

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->assertSet('accountId', $account->id)
        ->assertSee('Challenge 10k')
        ->assertSee(__('recovery.cta_title'));
});

it('ordena los errores por lo que costaron, no por cuántas veces se repitieron', function () {
    (new MistakesSeeder)->run();

    $user = User::factory()->create();
    $account = burnedAccount($user);

    $cheap = Mistake::where('slug', 'early_exit')->firstOrFail();
    $expensive = Mistake::where('slug', 'averaging_down')->firstOrFail();

    // Un solo símbolo compartido: TradeAssetFactory sortea de una lista de seis
    // con unique(), y crear uno por operación agota el generador.
    $asset = TradeAsset::factory()->create();

    // Cinco salidas prematuras baratas frente a dos promedios caros.
    foreach (range(1, 5) as $i) {
        $trade = Trade::factory()->create(['account_id' => $account->id, 'trade_asset_id' => $asset->id, 'pnl' => -20]);
        DB::table('trade_mistake')->insert(['trade_id' => $trade->id, 'mistake_id' => $cheap->id]);
    }

    foreach (range(1, 2) as $i) {
        $trade = Trade::factory()->create(['account_id' => $account->id, 'trade_asset_id' => $asset->id, 'pnl' => -500]);
        DB::table('trade_mistake')->insert(['trade_id' => $trade->id, 'mistake_id' => $expensive->id]);
    }

    $mistakes = Livewire::actingAs($user)->test(BurnedAccountCard::class)->instance()->costliestMistakes();

    expect($mistakes[0]['name'])->toBe(__('mistakes.averaging_down.name'))
        ->and($mistakes[0]['cost'])->toBe(-1000.0)
        ->and($mistakes[0]['count'])->toBe(2)
        ->and($mistakes[1]['name'])->toBe(__('mistakes.early_exit.name'));
});

it('lo dice claro cuando no hay errores etiquetados', function () {
    $user = User::factory()->create();
    $account = burnedAccount($user);
    Trade::factory()->count(2)->create(['account_id' => $account->id, 'pnl' => -300]);

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->assertSee(__('recovery.no_mistakes'));
});

it('se calla para siempre en esa cuenta al cerrarla', function () {
    $user = User::factory()->create();
    $account = burnedAccount($user);

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->call('dismiss')
        ->assertSet('accountId', null);

    expect($account->fresh()->recovery_dismissed_at)->not->toBeNull();

    Livewire::actingAs($user->fresh())
        ->test(BurnedAccountCard::class)
        ->assertSet('accountId', null);
});

it('deja de insistir pasado el mes', function () {
    $user = User::factory()->create();
    burnedAccount($user)->forceFill(['updated_at' => now()->subDays(45)])->save();

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->assertSet('accountId', null);
});

it('ignora la cuenta de ejemplo aunque figure como quemada', function () {
    $user = User::factory()->create();
    Account::factory()->create([
        'user_id' => $user->id,
        'status' => 'burned',
        'is_sample' => true,
        'updated_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->assertSet('accountId', null);
});

it('no anuncia descuento si no hay código configurado', function () {
    config(['billing.recovery_coupon' => null]);

    $user = User::factory()->create();
    burnedAccount($user);

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->assertDontSee(__('recovery.cta_coupon', ['coupon' => '']));
});

it('anuncia el código cuando está configurado', function () {
    config(['billing.recovery_coupon' => 'SEGUNDOINTENTO']);

    $user = User::factory()->create();
    burnedAccount($user);

    Livewire::actingAs($user)
        ->test(BurnedAccountCard::class)
        ->assertSee('SEGUNDOINTENTO');
});
