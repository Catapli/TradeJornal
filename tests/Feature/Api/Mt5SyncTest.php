<?php

use App\Jobs\StoreTradeChartJob;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

/**
 * `/api/mt5-sync` es la puerta por la que entran TODOS los datos del proyecto y no
 * tenía ni un test. Estos fijan el contrato: quién puede entrar, qué payload se acepta,
 * y que un trade malo no se lleve por delante el lote entero.
 */
beforeEach(function () {
    Cache::flush();
    Queue::fake();

    // Ojo: `User::booted()` sobrescribe el sync_token al crear, así que no se puede
    // fijar uno desde el test — hay que leer el que genera el modelo.
    $this->user = User::factory()->create();
    $this->token = $this->user->sync_token;
    $this->account = Account::factory()
        ->balance(100000)
        ->create(['user_id' => $this->user->id, 'mt5_login' => '5000123']);

    // El endpoint exige plan PRO.
    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_' . $this->user->id,
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
});

/** Payload mínimo válido de un trade. */
function tradePayload(array $overrides = []): array
{
    return array_merge([
        'position_id' => '900001',
        'ticket' => 'T900001',
        'trade_asset_symbol' => 'EURUSD',
        'direction' => 'long',
        'entry_price' => 1.08500,
        'exit_price' => 1.08900,
        'size' => 0.50,
        'pnl' => 200,
        'duration_minutes' => 45,
        'entry_time' => now()->subDays(2)->startOfDay()->addHours(9)->toDateTimeString(),
        'exit_time' => now()->subDays(2)->startOfDay()->addHours(10)->toDateTimeString(),
    ], $overrides);
}

/** Cuerpo completo de la petición de sync. */
function syncPayload(array $trades, array $overrides = []): array
{
    return array_merge([
        'sync_token' => test()->token,
        'account_login' => '5000123',
        'broker' => 'FTMO-Demo',
        'balance' => 100200,
        'trades' => $trades,
    ], $overrides);
}

// ---------------------------------------------------------------------------
// AUTENTICACIÓN Y PERMISOS
// ---------------------------------------------------------------------------

it('rechaza un sync_token que no existe', function () {
    $this->postJson('/api/mt5-sync', syncPayload([tradePayload()], ['sync_token' => 'inventado']))
        ->assertStatus(404);

    expect(Trade::count())->toBe(0);
});

it('rechaza a un usuario sin suscripción activa', function () {
    $this->user->subscriptions()->delete();

    $this->postJson('/api/mt5-sync', syncPayload([tradePayload()]))
        ->assertStatus(403);

    expect(Trade::count())->toBe(0);
});

it('no deja sincronizar contra una cuenta de otro usuario', function () {
    // El mt5_login existe, pero no es de quien manda el token.
    $ajena = Account::factory()->balance(50000)->create(['mt5_login' => '9999999']);

    $this->postJson('/api/mt5-sync', syncPayload([tradePayload()], ['account_login' => '9999999']))
        ->assertStatus(404);

    expect($ajena->trades()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// VALIDACIÓN DEL PAYLOAD
// ---------------------------------------------------------------------------

it('rechaza el lote entero si un trade trae una dirección fuera del enum', function () {
    // Antes `trades` solo se validaba como array: esto llegaba a la BD, reventaba
    // contra la columna enum y se contaba como "error del trade", no como payload malo.
    $this->postJson('/api/mt5-sync', syncPayload([
        tradePayload(),
        tradePayload(['position_id' => '900002', 'ticket' => 'T900002', 'direction' => 'sideways']),
    ]))
        ->assertStatus(422)
        ->assertJsonPath('error', 'Datos inválidos');

    expect(Trade::count())->toBe(0);
});

it('rechaza un trade sin position_id', function () {
    $sin = tradePayload();
    unset($sin['position_id']);

    $this->postJson('/api/mt5-sync', syncPayload([$sin]))->assertStatus(422);
});

it('rechaza una fecha de salida que no es una fecha', function () {
    $this->postJson('/api/mt5-sync', syncPayload([tradePayload(['exit_time' => 'ayer por la tarde'])]))
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// INSERCIÓN
// ---------------------------------------------------------------------------

it('da de alta los trades y actualiza el balance de la cuenta', function () {
    $this->postJson('/api/mt5-sync', syncPayload([
        tradePayload(),
        tradePayload(['position_id' => '900002', 'ticket' => 'T900002', 'pnl' => -50]),
    ]))
        ->assertOk()
        ->assertJsonPath('inserted', 2)
        ->assertJsonPath('errors', []);

    expect($this->account->trades()->count())->toBe(2)
        ->and((float) $this->account->fresh()->current_balance)->toBe(100200.0)
        ->and($this->account->fresh()->sync_error)->toBeFalse();
});

it('reenviar el mismo trade lo actualiza en vez de duplicarlo', function () {
    // El .exe reenvía el histórico completo en cada sync: sin esto, cada pasada
    // duplicaría todas las operaciones y las métricas se irían al garete.
    $this->postJson('/api/mt5-sync', syncPayload([tradePayload()]))->assertOk();
    $this->postJson('/api/mt5-sync', syncPayload([tradePayload(['pnl' => 250])]))->assertOk();

    expect($this->account->trades()->count())->toBe(1)
        ->and((float) $this->account->trades()->first()->pnl)->toBe(250.0);
});

it('reutiliza el símbolo en vez de crear uno por trade', function () {
    $this->postJson('/api/mt5-sync', syncPayload([
        tradePayload(),
        tradePayload(['position_id' => '900002', 'ticket' => 'T900002']),
        tradePayload(['position_id' => '900003', 'ticket' => 'T900003', 'trade_asset_symbol' => 'GBPUSD']),
    ]))->assertOk();

    expect(App\Models\TradeAsset::whereIn('symbol', ['EURUSD', 'GBPUSD'])->count())->toBe(2);
});

it('acepta un sync sin trades y solo refresca el balance', function () {
    $this->postJson('/api/mt5-sync', syncPayload([], ['balance' => 99000]))
        ->assertOk()
        ->assertJsonPath('inserted', 0);

    expect((float) $this->account->fresh()->current_balance)->toBe(99000.0);
});

// ---------------------------------------------------------------------------
// GRÁFICO Y CACHÉ
// ---------------------------------------------------------------------------

it('encola la subida del gráfico a R2 en vez de hacerla dentro del request', function () {
    $this->postJson('/api/mt5-sync', syncPayload([
        tradePayload(['chart_data' => [['t' => 1, 'o' => 1.08, 'h' => 1.09, 'l' => 1.07, 'c' => 1.085]]]),
    ]))->assertOk();

    Queue::assertPushed(StoreTradeChartJob::class, 1);
});

it('no encola nada cuando el trade no trae datos de gráfico', function () {
    $this->postJson('/api/mt5-sync', syncPayload([tradePayload()]))->assertOk();

    Queue::assertNotPushed(StoreTradeChartJob::class);
});

it('invalida la caché de estadísticas y gráfico de la cuenta', function () {
    Cache::put("account_stats_{$this->account->id}", ['totalTrades' => 99], now()->addMinutes(5));
    Cache::put("account_chart_{$this->account->id}_all", ['viejo'], now()->addMinutes(5));

    $this->postJson('/api/mt5-sync', syncPayload([tradePayload()]))->assertOk();

    expect(Cache::has("account_stats_{$this->account->id}"))->toBeFalse()
        ->and(Cache::has("account_chart_{$this->account->id}_all"))->toBeFalse();
});
