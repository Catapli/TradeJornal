<?php

use App\Jobs\RecalculateStrategyStatsJob;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\Trade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

/**
 * `TradeObserver` es lo que mantiene coherente lo que ve el usuario tras un sync
 * del .exe: invalida la caché de 5 minutos de las estadísticas y del gráfico de la
 * cuenta, y encola el recálculo de las estadísticas de la estrategia.
 */

beforeEach(function () {
    Cache::flush();
    Queue::fake();

    $this->account = Account::factory()->balance(100000)->create();
});

/** Rellena las claves de caché de una cuenta para comprobar luego si se han ido. */
function warmAccountCache(Account $account): void
{
    Cache::put("account_stats_{$account->id}", ['totalTrades' => 99], now()->addMinutes(5));

    foreach (['1h', '24h', '7d', 'all'] as $timeframe) {
        Cache::put("account_chart_{$account->id}_{$timeframe}", ['stale'], now()->addMinutes(5));
    }
}

/** ¿Queda alguna clave cacheada de esta cuenta? */
function accountCacheKeysPresent(Account $account): array
{
    $keys = ["account_stats_{$account->id}"];

    foreach (['1h', '24h', '7d', 'all'] as $timeframe) {
        $keys[] = "account_chart_{$account->id}_{$timeframe}";
    }

    return array_values(array_filter($keys, fn($key) => Cache::has($key)));
}

// ---------------------------------------------------------------------------
// INVALIDACIÓN DE CACHÉ
// ---------------------------------------------------------------------------

it('invalida las estadísticas y el gráfico de la cuenta al crear un trade', function () {
    // Este era el agujero: el .exe insertaba trades y el dashboard seguía
    // enseñando hasta 5 minutos de datos viejos porque nadie limpiaba la caché.
    warmAccountCache($this->account);

    Trade::factory()->for($this->account)->create();

    expect(accountCacheKeysPresent($this->account))->toBeEmpty();
});

it('invalida la caché al borrar un trade', function () {
    $trade = Trade::factory()->for($this->account)->create();
    warmAccountCache($this->account);

    $trade->delete();

    expect(accountCacheKeysPresent($this->account))->toBeEmpty();
});

it('invalida la caché al corregir el PnL de un trade', function () {
    $trade = Trade::factory()->for($this->account)->create();
    warmAccountCache($this->account);

    $trade->update(['pnl' => 1234.56]);

    expect(accountCacheKeysPresent($this->account))->toBeEmpty();
});

it('invalida la caché al mover un trade en el tiempo', function () {
    // `exit_time` manda en el gráfico de balance y `entry_time` en los días operados.
    $trade = Trade::factory()->for($this->account)->create();
    warmAccountCache($this->account);

    $trade->update(['exit_time' => now()->subDays(10)]);

    expect(accountCacheKeysPresent($this->account))->toBeEmpty();
});

it('no invalida nada por un cambio que no afecta a las estadísticas', function () {
    // Editar la nota de un trade no cambia ni una cifra: tirar la caché aquí solo
    // añade consultas.
    $trade = Trade::factory()->for($this->account)->create();
    warmAccountCache($this->account);

    $trade->update(['notes' => 'Revisar el contexto de esta entrada']);

    expect(accountCacheKeysPresent($this->account))->toHaveCount(5);
});

it('invalida las dos cuentas cuando un trade cambia de cuenta', function () {
    $destino = Account::factory()->balance(100000)->create();
    $trade = Trade::factory()->for($this->account)->create();

    warmAccountCache($this->account);
    warmAccountCache($destino);

    $trade->update(['account_id' => $destino->id]);

    expect(accountCacheKeysPresent($this->account))->toBeEmpty()
        ->and(accountCacheKeysPresent($destino))->toBeEmpty();
});

it('no toca la caché de una cuenta ajena al trade', function () {
    $otra = Account::factory()->balance(100000)->create();
    warmAccountCache($this->account);
    warmAccountCache($otra);

    Trade::factory()->for($this->account)->create();

    expect(accountCacheKeysPresent($this->account))->toBeEmpty()
        ->and(accountCacheKeysPresent($otra))->toHaveCount(5);
});

// ---------------------------------------------------------------------------
// RECÁLCULO DE ESTRATEGIA
// ---------------------------------------------------------------------------

it('encola el recálculo de la estrategia al crear un trade que la usa', function () {
    $strategy = Strategy::factory()->create();

    Trade::factory()->for($this->account)->create(['strategy_id' => $strategy->id]);

    Queue::assertPushed(
        RecalculateStrategyStatsJob::class,
        fn($job) => $job->strategy->is($strategy),
    );
});

it('no encola nada cuando el trade no tiene estrategia asignada', function () {
    Trade::factory()->for($this->account)->create(['strategy_id' => null]);

    Queue::assertNothingPushed();
});

it('recalcula las dos estrategias al mover un trade de una a otra', function () {
    // Si solo se recalculara la nueva, la antigua se quedaría contando un trade
    // que ya no le pertenece.
    $antigua = Strategy::factory()->create();
    $nueva = Strategy::factory()->create();
    $trade = Trade::factory()->for($this->account)->create(['strategy_id' => $antigua->id]);

    Queue::fake();
    $trade->update(['strategy_id' => $nueva->id]);

    Queue::assertPushed(RecalculateStrategyStatsJob::class, 2);
    Queue::assertPushed(RecalculateStrategyStatsJob::class, fn($job) => $job->strategy->is($nueva));
    Queue::assertPushed(RecalculateStrategyStatsJob::class, fn($job) => $job->strategy->is($antigua));
});

it('recalcula la estrategia al cambiar el PnL de uno de sus trades', function () {
    $strategy = Strategy::factory()->create();
    $trade = Trade::factory()->for($this->account)->create(['strategy_id' => $strategy->id]);

    Queue::fake();
    $trade->update(['pnl' => -500]);

    Queue::assertPushed(RecalculateStrategyStatsJob::class, 1);
});

it('recalcula la estrategia al borrar uno de sus trades', function () {
    $strategy = Strategy::factory()->create();
    $trade = Trade::factory()->for($this->account)->create(['strategy_id' => $strategy->id]);

    Queue::fake();
    $trade->delete();

    Queue::assertPushed(RecalculateStrategyStatsJob::class, 1);
});

it('no recalcula la estrategia por un cambio que no altera sus cifras', function () {
    $strategy = Strategy::factory()->create();
    $trade = Trade::factory()->for($this->account)->create(['strategy_id' => $strategy->id]);

    Queue::fake();
    $trade->update(['notes' => 'Sin impacto en las estadísticas']);

    Queue::assertNothingPushed();
});
