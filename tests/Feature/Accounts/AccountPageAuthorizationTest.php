<?php

use App\Livewire\AccountPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

/**
 * Los métodos y las propiedades públicas de un componente Livewire los controla el
 * cliente. `AccountPage` los usaba para localizar cuentas sin comprobar de quién eran,
 * así que se podían leer y reescribir cuentas ajenas — incluidos los límites de riesgo,
 * que es lo que decide si una cuenta de prop firm está viva o quemada.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->otro = User::factory()->create();

    $this->propia = Account::factory()->balance(100000)->create(['user_id' => $this->jordi->id]);
    $this->ajena = Account::factory()->balance(50000)->create(['user_id' => $this->otro->id]);

    $this->actingAs($this->jordi);
});

// ---------------------------------------------------------------------------
// ESCRITURA
// ---------------------------------------------------------------------------

it('no deja reescribir los límites de riesgo de una cuenta ajena', function () {
    // El caso grave: cambiarle a otro el max_daily_loss lo deja operando por encima
    // del límite real de su prop firm, creyéndose dentro.
    Livewire::test(AccountPage::class)
        ->set('editingAccountId', $this->ajena->id)
        ->set('rules_max_loss_percent', 99)
        ->set('rules_profit_target_percent', 1)
        ->call('saveRules')
        ->assertForbidden();

    expect($this->ajena->fresh()->tradingPlan)->toBeNull();
});

it('deja guardar los límites de riesgo de una cuenta propia', function () {
    Livewire::test(AccountPage::class)
        ->set('editingAccountId', $this->propia->id)
        ->set('rules_max_loss_percent', 4)
        ->set('rules_profit_target_percent', 2)
        ->call('saveRules');

    expect((float) $this->propia->fresh()->tradingPlan->max_daily_loss_percent)->toBe(4.0);
});

it('no deja actualizar una cuenta ajena', function () {
    Livewire::test(AccountPage::class)
        ->call('updateAccount', $this->ajena->id)
        ->assertForbidden();

    expect($this->ajena->fresh()->name)->toBe($this->ajena->name);
});

// ---------------------------------------------------------------------------
// LECTURA
// ---------------------------------------------------------------------------

it('no deja abrir las reglas de una cuenta ajena', function () {
    Livewire::test(AccountPage::class)
        ->call('openRules', $this->ajena->id)
        ->assertForbidden();
});

it('no deja abrir en edición una cuenta ajena', function () {
    // Filtraba nombre, mt5_login, servidor y prop firm de la cuenta.
    Livewire::test(AccountPage::class)
        ->call('editAccount', $this->ajena->id)
        ->assertForbidden();
});

it('no lista los trades de una cuenta ajena aunque se fuerce selectedAccountId', function () {
    // `selectedAccountId` es una propiedad pública. Antes bastaba con cambiarla para
    // que la tabla del histórico pintara los trades del otro usuario.
    Trade::factory()->for($this->ajena)->count(3)->create();
    Trade::factory()->for($this->propia)->count(1)->create();

    $componente = Livewire::test(AccountPage::class)
        ->set('selectedAccountId', $this->ajena->id);

    $trades = $componente->instance()->historyTrades;

    // Cae a la cuenta propia (el computed filtra por Auth::id()), nunca a la ajena.
    expect($trades->pluck('account_id')->unique()->all())->toBe([$this->propia->id]);
});

it('no filtra el last_sync de una cuenta ajena', function () {
    $this->ajena->update(['last_sync' => now()]);

    Livewire::test(AccountPage::class)
        ->set('selectedAccountId', $this->ajena->id)
        ->call('checkSyncStatus')
        ->assertOk();
});

it('no filtra los ids de trades de una cuenta ajena al abrir el detalle', function () {
    $ajenos = Trade::factory()->for($this->ajena)->count(2)->create();

    $componente = Livewire::test(AccountPage::class)
        ->set('selectedAccountId', $this->ajena->id)
        ->call('openTradeDetail', $ajenos->first()->id);

    foreach ($componente->effects['dispatches'] ?? [] as $evento) {
        if (($evento['name'] ?? null) === 'open-trade-detail') {
            expect($evento['params']['tradeIds'] ?? [])
                ->not->toContain($ajenos->first()->id);
        }
    }
});
