<?php

use App\Livewire\DashboardPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

/**
 * El dashboard tenía su propio panel de detalle de trade: 493 líneas de Blade y
 * cuatro métodos que duplicaban TradeDetailModal. Las dos copias divergieron y el
 * mismo trade daba veredictos de IA distintos según desde dónde se abriera.
 *
 * Ahora `selectTrade` solo despacha al componente global. Estos tests fijan ese
 * contrato — incluida la comprobación de que el trade pertenece al día visible.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id]);

    $this->actingAs($this->jordi);
});

/** Trade cerrado en la fecha indicada, en la cuenta del usuario del test. */
function tradeDelDia(string $fecha, ?Account $cuenta = null): Trade
{
    $cuenta ??= test()->cuenta;

    return Trade::factory()->create([
        'account_id' => $cuenta->id,
        'entry_time' => $fecha . ' 09:00:00',
        'exit_time' => $fecha . ' 10:00:00',
    ]);
}

it('despacha el trade al modal global en lugar de cargarlo en su propio estado', function () {
    $trade = tradeDelDia('2026-08-20');

    Livewire::test(DashboardPage::class)
        ->call('openDayDetails', '2026-08-20')
        ->call('selectTrade', $trade->id)
        ->assertDispatched('open-trade-detail', tradeId: $trade->id);
});

it('pasa los ids del día para que el modal pueda navegar entre trades', function () {
    $primero = tradeDelDia('2026-08-20');
    $segundo = tradeDelDia('2026-08-20');

    Livewire::test(DashboardPage::class)
        ->call('openDayDetails', '2026-08-20')
        ->call('selectTrade', $primero->id)
        ->assertDispatched('open-trade-detail', function ($evento, $params) use ($primero, $segundo) {
            return in_array($primero->id, $params['tradeIds'], true)
                && in_array($segundo->id, $params['tradeIds'], true);
        });
});

it('rechaza un trade que no pertenece al día que se está viendo', function () {
    tradeDelDia('2026-08-20');
    $otroDia = tradeDelDia('2026-08-15');

    Livewire::test(DashboardPage::class)
        ->call('openDayDetails', '2026-08-20')
        ->call('selectTrade', $otroDia->id)
        ->assertNotDispatched('open-trade-detail');
});

it('rechaza un trade de otro usuario aunque esté en la fecha visible', function () {
    tradeDelDia('2026-08-20');

    $ajena = Account::factory()->create(['user_id' => User::factory()->create()->id]);
    $tradeAjeno = tradeDelDia('2026-08-20', $ajena);

    Livewire::test(DashboardPage::class)
        ->call('openDayDetails', '2026-08-20')
        ->call('selectTrade', $tradeAjeno->id)
        ->assertNotDispatched('open-trade-detail');
});

it('rechaza ids no numéricos o negativos sin reventar', function () {
    Livewire::test(DashboardPage::class)
        ->call('openDayDetails', '2026-08-20')
        ->call('selectTrade', -1)
        ->assertNotDispatched('open-trade-detail')
        ->call('selectTrade', 'abc')
        ->assertNotDispatched('open-trade-detail');
});

it('ya no expone el estado del panel de detalle duplicado', function () {
    // Si estas propiedades reaparecen es que alguien ha vuelto a duplicar el modal.
    $componente = Livewire::test(DashboardPage::class);

    foreach (['selectedTrade', 'uploadedScreenshot', 'currentScreenshot', 'isAnalyzingTrade'] as $propiedad) {
        expect(property_exists(DashboardPage::class, $propiedad))->toBeFalse(
            "DashboardPage no debería declarar \${$propiedad}: eso lo gestiona TradeDetailModal."
        );
    }

    $componente->assertOk();
});
