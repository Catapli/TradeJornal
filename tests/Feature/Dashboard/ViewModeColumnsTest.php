<?php

declare(strict_types=1);

use App\Livewire\DashboardPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Livewire\Livewire;

/**
 * El conmutador $ / % depende de columnas que las queries pueden dejarse fuera.
 *
 * Las tablas del panel usan `select()` explícitos por rendimiento, y olvidar una
 * columna ahí no rompe nada: simplemente pinta un cero en todas las filas. Este
 * test es el que avisa.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'status' => 'active',
        'is_sample' => false,
        'initial_balance' => 10000,
    ]);
    $this->activo = TradeAsset::factory()->create();
});

function tradeConPorcentaje(float $pnl, float $porcentaje, string $cierre): Trade
{
    return Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'pnl_percentage' => $porcentaje,
        'entry_time' => Carbon\Carbon::parse($cierre)->subHour(),
        'exit_time' => Carbon\Carbon::parse($cierre),
    ]);
}

it('las operaciones recientes traen el porcentaje, no un cero', function () {
    tradeConPorcentaje(214.91, 0.4298, '2026-08-26 10:00:00');
    tradeConPorcentaje(-129.32, -0.5173, '2026-08-26 12:00:00');

    $recientes = Livewire::actingAs($this->jordi)->test(DashboardPage::class)->instance()->recentTrades;

    expect($recientes)->toHaveCount(2)
        ->and($recientes->pluck('pnl_percentage')->filter(fn ($p) => $p !== null))->toHaveCount(2)
        ->and((float) $recientes->firstWhere('pnl', '214.91')->pnl_percentage)->toBe(0.4298);
});

it('el detalle del día trae el porcentaje de cada operación', function () {
    tradeConPorcentaje(300, 3.0, '2026-08-26 10:00:00');

    $component = Livewire::actingAs($this->jordi)
        ->test(DashboardPage::class)
        ->set('selectedDate', '2026-08-26');

    $delDia = $component->instance()->dayTrades;

    expect($delDia)->toHaveCount(1)
        ->and((float) $delDia->first()->pnl_percentage)->toBe(3.0);
});

it('una ganadora nunca se pinta como porcentaje negativo', function () {
    tradeConPorcentaje(214.91, 0.4298, '2026-08-26 10:00:00');

    $html = $this->actingAs($this->jordi)->get(route('dashboard'))->getContent();

    // Lo que llega a Alpine tiene que llevar el porcentaje real de la operación.
    expect($html)->toContain('viewMode.format(214.91, 0.4298)');
});
