<?php

declare(strict_types=1);

use App\Actions\Trades\BuildTradeAuditContext;
use App\Models\Account;
use App\Models\ImprovementGoal;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\Carbon;

/**
 * La memoria del mentor dentro de la auditoría (Fase 6 · P5).
 *
 * Antes de esto la IA juzgaba cada operación como si fuera la primera del
 * usuario: N veredictos sueltos, ninguno capaz de decir «otra vez lo mismo».
 * El bloque de perfil es lo que lo cambia, y si desaparece del contexto nadie
 * se entera: la auditoría sigue saliendo, solo que sin memoria.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-08-15 10:00:00');

    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'active']);
    $this->activo = TradeAsset::factory()->create();
    $this->contexto = app(BuildTradeAuditContext::class);

    $this->error = Mistake::create(['slug' => 'memoria_venganza', 'name' => 'Operar por venganza', 'color' => 'red', 'weight' => 5]);
});

afterEach(fn () => Carbon::setTestNow());

/** Operación cerrada del usuario, marcada o no. */
function opDeMemoria(string $fecha, float $pnl, bool $conError = false): Trade
{
    $trade = Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon::parse($fecha . ' 09:00:00'),
        'exit_time' => Carbon::parse($fecha . ' 10:00:00'),
        'duration_minutes' => 60,
        'chart_data_path' => null,
    ]);

    if ($conError) {
        $trade->mistakes()->attach(test()->error->id);
    }

    return $trade;
}

/** Doce marcas repartidas por julio: la muestra mínima del perfil. */
function historialConError(): void
{
    for ($i = 1; $i <= 12; $i++) {
        opDeMemoria('2026-07-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), -120, true);
    }
}

it('mete los errores recurrentes en el contexto de la auditoría', function () {
    historialConError();
    $trade = opDeMemoria('2026-08-14', -90);

    $contexto = $this->contexto->execute($trade->load('account', 'tradeAsset'));

    expect($contexto)->toContain(__('ai.labels.profile'))
        ->and($contexto)->toContain('Operar por venganza');
});

it('mete también el objetivo del mes cuando hay uno', function () {
    historialConError();
    opDeMemoria('2026-08-03', -90, true);
    $trade = opDeMemoria('2026-08-14', -90);

    ImprovementGoal::create([
        'user_id' => $this->jordi->id,
        'mistake_id' => $this->error->id,
        'month' => '2026-08-01',
        'statement' => 'Bajar de 12 a 6',
        'baseline' => 12,
        'target' => 6,
        'sample' => 20,
    ]);

    $contexto = $this->contexto->execute($trade->load('account', 'tradeAsset'));

    // La cifra de lo que lleva gastado del objetivo tiene que viajar: es lo que
    // permite que el veredicto diga «vas por la segunda de seis».
    expect($contexto)->toContain(__('ai.labels.profile_goal', [
        'name' => 'Operar por venganza',
        'baseline' => 12,
        'target' => 6,
        'so_far' => 1,
    ]));
});

it('dice que no hay historial en vez de inventarse un perfil', function () {
    $trade = opDeMemoria('2026-08-14', -90);

    expect($this->contexto->execute($trade->load('account', 'tradeAsset')))
        ->toContain(__('ai.labels.profile_none'));
});

it('no se cae con una operación sin cuenta', function () {
    // Los tests del contexto —y cualquier operación aún sin guardar— pasan por
    // aquí sin cuenta detrás. Tiene que salir «sin historial», no un error.
    $suelta = Trade::factory()->make(['chart_data_path' => null]);

    expect($this->contexto->execute($suelta))->toContain(__('ai.labels.profile_none'));
});
