<?php

use App\Actions\Dashboard\DashboardTradeQuery;
use App\Livewire\DashboardPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

/**
 * El selector de cuentas del panel decide qué historial existe.
 *
 * Por defecto («Todas») solo cuentan las cuentas vivas, que es lo que se espera
 * al abrir el panel. Pero una cuenta quemada o archivada guarda meses de
 * operaciones que siguen siendo del usuario, así que marcarlas a mano tiene que
 * traerlas de vuelta: si no, archivar equivale a perder los datos.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();

    $this->viva = Account::factory()->create(['user_id' => $this->jordi->id, 'name' => 'Viva']);
    $this->quemada = Account::factory()->create(['user_id' => $this->jordi->id, 'name' => 'Quemada', 'status' => 'burned']);
    $this->archivada = Account::factory()->create(['user_id' => $this->jordi->id, 'name' => 'Archivada']);

    Trade::factory()->create(['account_id' => $this->viva->id, 'pnl' => 100]);
    Trade::factory()->create(['account_id' => $this->quemada->id, 'pnl' => 500]);
    Trade::factory()->create(['account_id' => $this->archivada->id, 'pnl' => 900]);

    $this->archivada->delete();

    $this->actingAs($this->jordi);
});

/** PnL total que ve el panel con esta selección de cuentas. */
function pnlCon(array $seleccion): float
{
    return (float) (new DashboardTradeQuery($seleccion, '', '', test()->jordi->id))
        ->filtered()
        ->sum('pnl');
}

// ---------------------------------------------------------------------------
// QUÉ ENTRA EN CADA SELECCIÓN
// ---------------------------------------------------------------------------

it('por defecto solo suma las cuentas vivas', function () {
    expect(pnlCon(['all']))->toEqual(100.0);
});

it('trae la cuenta quemada cuando se elige a mano', function () {
    expect(pnlCon([test()->quemada->id]))->toEqual(500.0);
});

it('trae la cuenta archivada cuando se elige a mano', function () {
    // Es el caso que motivó el cambio: archivar dejaba el historial inalcanzable.
    expect(pnlCon([test()->archivada->id]))->toEqual(900.0);
});

it('suma varias cuentas de estados distintos a la vez', function () {
    expect(pnlCon([test()->viva->id, test()->archivada->id]))->toEqual(1000.0);
});

it('sigue sin dejar ver las cuentas de otro usuario aunque se pidan por id', function () {
    $ajena = Account::factory()->create(['user_id' => User::factory()->create()->id]);
    Trade::factory()->create(['account_id' => $ajena->id, 'pnl' => 9999]);
    $ajena->delete();

    // El filtro de seguridad por usuario no se relaja ni con selección explícita.
    expect(pnlCon([$ajena->id]))->toEqual(0.0);
});

// ---------------------------------------------------------------------------
// LO QUE OFRECE EL SELECTOR
// ---------------------------------------------------------------------------

it('ofrece las tres cuentas en el selector, con etiqueta en las que no están vivas', function () {
    $opciones = collect(Livewire::test(DashboardPage::class)->get('availableAccounts'));

    expect($opciones)->toHaveCount(3);

    $porNombre = $opciones->keyBy('name');

    expect($porNombre['Viva']['badge'])->toBeNull()
        ->and($porNombre['Quemada']['badge'])->toBe(__('labels.account_badge_burned'))
        ->and($porNombre['Archivada']['badge'])->toBe(__('labels.account_badge_archived'));
});

it('no ofrece cuentas de otro usuario en el selector', function () {
    Account::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Ajena']);

    $nombres = collect(Livewire::test(DashboardPage::class)->get('availableAccounts'))->pluck('name');

    expect($nombres)->not->toContain('Ajena');
});

it('arranca en «todas» y deja seleccionar una cuenta archivada sin vaciar el panel', function () {
    Livewire::test(DashboardPage::class)
        ->assertSet('selectedAccounts', ['all'])
        ->set('selectedAccounts', [(string) $this->archivada->id])
        ->assertSet('selectedAccounts', [(string) $this->archivada->id])
        ->assertHasNoErrors();
});
