<?php

use App\Actions\Accounts\GenerateBalanceChartData;
use App\Models\Account;
use App\Models\Trade;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->action = new GenerateBalanceChartData();
    $this->account = Account::factory()->balance(100000)->create();
});

/** Crea un trade cerrado con PnL y fecha de salida concretos. */
function closedTrade(Account $account, float $pnl, string $exitTime): Trade
{
    $exit = Carbon\Carbon::parse($exitTime);

    return Trade::factory()->for($account)->create([
        'pnl' => $pnl,
        'entry_time' => $exit->copy()->subHour(),
        'exit_time' => $exit,
        'duration_minutes' => 60,
        'mae_price' => null,
        'mfe_price' => null,
    ]);
}

/** Devuelve la serie de balance real del resultado. */
function balanceSeries(array $chart): array
{
    foreach ($chart['series'] as $serie) {
        if ($serie['name'] === __('labels.balance_real')) {
            return $serie['data'];
        }
    }

    return [];
}

it('dibuja una línea plana en el balance inicial cuando no hay trades', function () {
    $chart = $this->action->execute($this->account);

    expect(balanceSeries($chart))->toBe([100000.0, 100000.0])
        ->and($chart['categories'][0])->toBe('Inicio');
});

it('devuelve siempre las tres series del gráfico', function () {
    $chart = $this->action->execute($this->account);

    expect($chart['series'])->toHaveCount(3)
        ->and($chart)->toHaveKeys(['categories', 'series']);
});

it('acumula el PnL sobre el balance inicial', function () {
    closedTrade($this->account, 500, '2026-03-01 10:00:00');
    closedTrade($this->account, -200, '2026-03-02 10:00:00');
    closedTrade($this->account, 700, '2026-03-03 10:00:00');

    $balances = balanceSeries($this->action->execute($this->account));

    // Inicio + un punto por día, acumulando.
    expect($balances)->toBe([100000.0, 100500.0, 100300.0, 101000.0]);
});

it('agrupa en un solo punto los trades del mismo día', function () {
    closedTrade($this->account, 100, '2026-03-01 09:00:00');
    closedTrade($this->account, 200, '2026-03-01 18:00:00');

    $balances = balanceSeries($this->action->execute($this->account));

    // Inicio + un único punto con la suma del día (300).
    expect($balances)->toBe([100000.0, 100300.0]);
});

it('mantiene el mismo número de puntos en las tres series', function () {
    closedTrade($this->account, 100, '2026-03-01 10:00:00');
    closedTrade($this->account, -50, '2026-03-02 10:00:00');

    $chart = $this->action->execute($this->account);
    $longitudes = array_map(fn($s) => count($s['data']), $chart['series']);

    expect(array_unique($longitudes))->toHaveCount(1)
        ->and($longitudes[0])->toBe(count($chart['categories']));
});

it('no mezcla los trades de otras cuentas', function () {
    closedTrade($this->account, 500, '2026-03-01 10:00:00');
    closedTrade(Account::factory()->create(), 9999, '2026-03-01 10:00:00');

    expect(balanceSeries($this->action->execute($this->account)))
        ->toBe([100000.0, 100500.0]);
});

it('parte del balance inicial de la cuenta, no de una cifra fija', function () {
    $cuenta = Account::factory()->balance(25000)->create();
    closedTrade($cuenta, 1000, '2026-03-01 10:00:00');

    expect(balanceSeries($this->action->execute($cuenta)))
        ->toBe([25000.0, 26000.0]);
});

it('el timeframe 24h descarta lo anterior pero lo arrastra al balance de partida', function () {
    // Fuera de ventana: no sale como punto, pero sí suma al balance inicial.
    closedTrade($this->account, 5000, now()->subDays(10)->format('Y-m-d H:i:s'));
    // Dentro de ventana.
    closedTrade($this->account, 300, now()->subHours(2)->format('Y-m-d H:i:s'));

    $balances = balanceSeries($this->action->execute($this->account, '24h'));

    expect($balances[0])->toBe(105000.0)
        ->and(end($balances))->toBe(105300.0);
});

it('cachea por cuenta y timeframe', function () {
    closedTrade($this->account, 100, '2026-03-01 10:00:00');
    $this->action->execute($this->account, 'all');

    closedTrade($this->account, 900, '2026-03-02 10:00:00');

    // 'all' sigue cacheado...
    $cacheado = balanceSeries($this->action->execute($this->account, 'all'));
    expect(end($cacheado))->toBe(100100.0);

    // ...y forzar el refresco lo recalcula.
    $refrescado = $this->action->execute($this->account, 'all', forceRefresh: true);
    $serie = balanceSeries($refrescado);
    expect(end($serie))->toBe(101000.0);
});

it('clearCache invalida todos los timeframes de la cuenta', function () {
    closedTrade($this->account, 100, '2026-03-01 10:00:00');

    foreach (['1h', '24h', '7d', 'all'] as $tf) {
        $this->action->execute($this->account, $tf);
    }

    closedTrade($this->account, 900, '2026-03-02 10:00:00');
    GenerateBalanceChartData::clearCache($this->account->id);

    $tras = balanceSeries($this->action->execute($this->account, 'all'));
    expect(end($tras))->toBe(101000.0);
});
