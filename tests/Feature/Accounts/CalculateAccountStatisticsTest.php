<?php

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Models\Account;
use App\Models\Trade;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->action = new CalculateAccountStatistics();
    $this->account = Account::factory()->balance(100000)->create();
});

/** Crea trades cerrados con los PnL indicados. */
function tradesWithPnl(Account $account, array $pnls): void
{
    foreach ($pnls as $i => $pnl) {
        Trade::factory()->for($account)->create([
            'pnl' => $pnl,
            'entry_time' => now()->subDays(count($pnls) - $i),
            'exit_time' => now()->subDays(count($pnls) - $i)->addHour(),
            'duration_minutes' => 60,
        ]);
    }
}

it('devuelve ceros y no divide por cero cuando la cuenta no tiene trades', function () {
    $stats = $this->action->execute($this->account);

    expect($stats['totalTrades'])->toBe(0)
        ->and($stats['winRate'])->toBe(0)
        ->and($stats['profitFactor'])->toBe(0)
        ->and($stats['arr'])->toBe(0)
        ->and($stats['tradingDays'])->toBe(0)
        ->and($stats['topAsset'])->toBe('N/A');
});

it('calcula el winrate como porcentaje de trades ganadores', function () {
    // 3 ganadores de 5 => 60%
    tradesWithPnl($this->account, [100, 200, 300, -50, -50]);

    $stats = $this->action->execute($this->account);

    expect($stats['totalTrades'])->toBe(5)
        ->and($stats['winRate'])->toBe(60.0);
});

it('cuenta un trade de PnL cero como perdedor, no como ganador', function () {
    // El SQL usa `pnl > 0` para ganador: un breakeven no debe inflar el winrate.
    tradesWithPnl($this->account, [100, 0]);

    $stats = $this->action->execute($this->account);

    expect($stats['totalTrades'])->toBe(2)
        ->and($stats['winRate'])->toBe(50.0);
});

it('calcula el profit factor como beneficio bruto entre pérdida bruta', function () {
    // Bruto ganado 600, bruto perdido 300 => PF 2.0
    tradesWithPnl($this->account, [400, 200, -100, -200]);

    $stats = $this->action->execute($this->account);

    expect((float) $stats['grossProfit'])->toBe(600.0)
        ->and((float) $stats['grossLoss'])->toBe(300.0)
        ->and((float) $stats['profitFactor'])->toBe(2.0);
});

it('calcula la media de ganancia, de pérdida y el ratio entre ambas', function () {
    // Ganadores: 300 y 100 => media 200. Perdedores: 100 y 100 => media 100.
    tradesWithPnl($this->account, [300, 100, -100, -100]);

    $stats = $this->action->execute($this->account);

    expect((float) $stats['avgWinTrade'])->toBe(200.0)
        ->and((float) $stats['avgLossTrade'])->toBe(100.0)
        ->and((float) $stats['arr'])->toBe(2.0);
});

it('la base de datos solo admite operaciones cerradas', function () {
    // `trades.exit_time` es NOT NULL: una posición abierta no se puede guardar.
    //
    // Por eso se retiraron los 7 `whereNotNull('exit_time')` que había repartidos
    // por CalculateAccountStatistics, GenerateBalanceChartData,
    // RecalculateStrategyStats y DashboardPage: filtraban por algo imposible.
    //
    // Este test fija esa premisa. Si algún día se hace la columna nullable para
    // soportar posiciones abiertas, fallará — y avisará de que hay que repasar
    // todas esas consultas, que ahora dan por hecho que todo trade está cerrado.
    expect(fn() => Trade::factory()->for($this->account)->create(['exit_time' => null]))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('no cuenta los trades de otras cuentas', function () {
    tradesWithPnl($this->account, [100, 100]);
    tradesWithPnl(Account::factory()->create(), [500, 500, 500]);

    $stats = $this->action->execute($this->account);

    expect($stats['totalTrades'])->toBe(2)
        ->and((float) $stats['grossProfit'])->toBe(200.0);
});

it('cuenta los días de trading como fechas distintas, no como número de trades', function () {
    // Tres trades en dos días naturales.
    Trade::factory()->for($this->account)->enteredAt('2026-03-01 09:00:00')->create(['pnl' => 10]);
    Trade::factory()->for($this->account)->enteredAt('2026-03-01 15:00:00')->create(['pnl' => 10]);
    Trade::factory()->for($this->account)->enteredAt('2026-03-02 09:00:00')->create(['pnl' => 10]);

    $stats = $this->action->execute($this->account);

    expect($stats['totalTrades'])->toBe(3)
        ->and($stats['tradingDays'])->toBe(2);
});

it('cachea el resultado y lo recalcula solo si se fuerza', function () {
    tradesWithPnl($this->account, [100]);

    expect($this->action->execute($this->account)['totalTrades'])->toBe(1);

    // Un trade nuevo no debe verse mientras la caché siga viva...
    tradesWithPnl($this->account, [200]);
    expect($this->action->execute($this->account)['totalTrades'])->toBe(1);

    // ...pero sí al forzar el refresco.
    expect($this->action->execute($this->account, forceRefresh: true)['totalTrades'])->toBe(2);
});

it('invalida la caché al llamar a clearCache', function () {
    tradesWithPnl($this->account, [100]);
    $this->action->execute($this->account);

    tradesWithPnl($this->account, [200]);
    CalculateAccountStatistics::clearCache($this->account->id);

    expect($this->action->execute($this->account)['totalTrades'])->toBe(2);
});

it('cada cuenta tiene su propia entrada de caché', function () {
    $otra = Account::factory()->create();
    tradesWithPnl($this->account, [100]);
    tradesWithPnl($otra, [100, 200, 300]);

    expect($this->action->execute($this->account)['totalTrades'])->toBe(1)
        ->and($this->action->execute($otra)['totalTrades'])->toBe(3);
});

it('no inventa una pérdida máxima cuando la cuenta no tiene pérdidas', function () {
    // Regresión: con `MIN(pnl)` a secas, una cuenta solo con ganadores devolvía
    // como "pérdida máxima" su ganancia más pequeña (aquí, 50).
    tradesWithPnl($this->account, [50, 200, 300]);

    $stats = $this->action->execute($this->account);

    expect((float) $stats['maxWin'])->toBe(300.0)
        ->and((float) $stats['maxLoss'])->toBe(0.0);
});

it('no inventa una ganancia máxima cuando la cuenta no tiene ganancias', function () {
    // Regresión: con `MAX(pnl)` a secas, una cuenta solo con perdedores devolvía
    // como "ganancia máxima" la pérdida menos mala (aquí, -50).
    tradesWithPnl($this->account, [-50, -200, -300]);

    $stats = $this->action->execute($this->account);

    expect((float) $stats['maxWin'])->toBe(0.0)
        ->and((float) $stats['maxLoss'])->toBe(300.0);
});

it('devuelve el mayor ganador y el mayor perdedor cuando hay de ambos', function () {
    tradesWithPnl($this->account, [50, 400, -30, -250]);

    $stats = $this->action->execute($this->account);

    expect((float) $stats['maxWin'])->toBe(400.0)
        ->and((float) $stats['maxLoss'])->toBe(250.0);
});
