<?php

use App\Livewire\AccountPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

/**
 * «Eliminar cuenta» destruía el histórico entero.
 *
 * `trades.account_id` es `onDelete('cascade')` y `Account` no tenía soft
 * deletes, así que un clic se llevaba por delante meses de operaciones sin
 * vuelta atrás. Un trader que quema una cuenta de fondeo no cambia de forma de
 * operar: el dato que explica cómo llegó ahí es justo el que no debe perder.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->otro = User::factory()->create();

    $this->cuenta = Account::factory()->balance(100000)->create(['user_id' => $this->jordi->id]);
    $this->ajena = Account::factory()->balance(50000)->create(['user_id' => $this->otro->id]);

    $this->actingAs($this->jordi);
});

// ---------------------------------------------------------------------------
// ARCHIVAR
// ---------------------------------------------------------------------------

it('archiva la cuenta en vez de borrarla, y conserva sus operaciones', function () {
    Trade::factory()->count(3)->create(['account_id' => $this->cuenta->id]);

    Livewire::test(AccountPage::class)->call('deleteAccount', $this->cuenta->id);

    expect(Account::withTrashed()->find($this->cuenta->id)->trashed())->toBeTrue()
        ->and(Trade::where('account_id', $this->cuenta->id)->count())->toBe(3);
});

it('saca la cuenta archivada de las consultas normales sin tocar las filas', function () {
    Trade::factory()->count(2)->create(['account_id' => $this->cuenta->id]);

    Livewire::test(AccountPage::class)->call('deleteAccount', $this->cuenta->id);

    // El scope global de SoftDeletes también filtra el whereHas('account') de Trade:
    // las operaciones desaparecen de las pantallas, pero siguen en la base.
    expect(Account::find($this->cuenta->id))->toBeNull()
        ->and(Trade::forUser($this->jordi->id)->count())->toBe(0)
        ->and(Trade::count())->toBe(2);
});

it('no deja archivar la cuenta de otro usuario', function () {
    Livewire::test(AccountPage::class)
        ->call('deleteAccount', $this->ajena->id)
        ->assertForbidden();

    expect($this->ajena->fresh()->trashed())->toBeFalse();
});

// ---------------------------------------------------------------------------
// RESTAURAR
// ---------------------------------------------------------------------------

it('restaura la cuenta con todo su histórico', function () {
    Trade::factory()->count(4)->create(['account_id' => $this->cuenta->id]);

    Livewire::test(AccountPage::class)
        ->call('deleteAccount', $this->cuenta->id)
        ->call('restoreAccount', $this->cuenta->id);

    expect(Account::find($this->cuenta->id))->not->toBeNull()
        ->and(Trade::forUser($this->jordi->id)->count())->toBe(4);
});

it('no deja restaurar la cuenta archivada de otro usuario', function () {
    $this->ajena->delete();

    Livewire::test(AccountPage::class)
        ->call('restoreAccount', $this->ajena->id)
        ->assertForbidden();

    expect(Account::withTrashed()->find($this->ajena->id)->trashed())->toBeTrue();
});

it('lista las cuentas archivadas con cuántas operaciones guardan', function () {
    Trade::factory()->count(5)->create(['account_id' => $this->cuenta->id]);

    $component = Livewire::test(AccountPage::class)->call('deleteAccount', $this->cuenta->id);

    $archivadas = $component->instance()->archivedAccounts();

    expect($archivadas)->toHaveCount(1)
        ->and($archivadas->first()->trades_count)->toBe(5);
});

// ---------------------------------------------------------------------------
// BORRADO DEFINITIVO
// ---------------------------------------------------------------------------

it('exige archivar antes de borrar definitivamente', function () {
    Trade::factory()->count(2)->create(['account_id' => $this->cuenta->id]);

    Livewire::test(AccountPage::class)->call('deleteAccountPermanently', $this->cuenta->id);

    // Dos pasos separados a propósito: nadie destruye un histórico de un clic.
    expect(Account::find($this->cuenta->id))->not->toBeNull()
        ->and(Trade::count())->toBe(2);
});

it('borra de verdad la cuenta archivada y sus operaciones', function () {
    Trade::factory()->count(3)->create(['account_id' => $this->cuenta->id]);

    Livewire::test(AccountPage::class)
        ->call('deleteAccount', $this->cuenta->id)
        ->call('deleteAccountPermanently', $this->cuenta->id);

    expect(Account::withTrashed()->find($this->cuenta->id))->toBeNull()
        ->and(Trade::count())->toBe(0);
});

it('no deja borrar definitivamente la cuenta de otro usuario', function () {
    $this->ajena->delete();

    Livewire::test(AccountPage::class)
        ->call('deleteAccountPermanently', $this->ajena->id)
        ->assertForbidden();

    expect(Account::withTrashed()->find($this->ajena->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// EFECTOS COLATERALES DEL SCOPE GLOBAL
// ---------------------------------------------------------------------------

it('no cuenta las cuentas archivadas para el límite del plan', function () {
    // El límite gratuito son 3. Con la cuenta del beforeEach y dos más, está lleno.
    config()->set('billing.accounts.free', 3);
    Account::factory()->count(2)->create(['user_id' => $this->jordi->id]);

    expect($this->jordi->fresh()->canCreateAccount())->toBeFalse();

    Livewire::test(AccountPage::class)->call('deleteAccount', $this->cuenta->id);

    expect($this->jordi->fresh()->canCreateAccount())->toBeTrue();
});

it('detecta que el login MT5 pertenece a una cuenta archivada', function () {
    // El índice único de mt5_login no entiende de archivadas: sin mirar las
    // borradas, la comprobación de alta pasaba y el INSERT reventaba contra la
    // base con un error que no le dice nada al usuario.
    $this->cuenta->update(['mt5_login' => '55512345']);

    Livewire::test(AccountPage::class)->call('deleteAccount', $this->cuenta->id);

    expect(Account::where('mt5_login', '55512345')->exists())->toBeFalse()
        ->and(Account::withTrashed()->where('mt5_login', '55512345')->first()?->trashed())->toBeTrue();
});
