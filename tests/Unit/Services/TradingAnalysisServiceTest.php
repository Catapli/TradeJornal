<?php

use App\Models\Trade;
use App\Services\TradingAnalysisService;
use Illuminate\Support\Collection;

// tests/Pest.php solo enlaza TestCase con Feature. Aquí hace falta el contenedor
// de Laravel (casting de modelos, fake()) pero NO base de datos: sin RefreshDatabase.
uses(Tests\TestCase::class);

/**
 * Cálculo puro sobre colecciones de trades: sin BD, sin Livewire, sin HTTP.
 *
 * Es el código que le dice al usuario si su sistema es viable y qué probabilidad
 * tiene de quemar la cuenta, y llevaba dos bugs matemáticos confirmados. Los casos
 * límite (cero pérdidas, cero ganancias, muestra mínima) son donde falla.
 */
beforeEach(function () {
    $this->service = new TradingAnalysisService;
});

/** Trade en memoria: nada toca la base de datos en este fichero. */
function fakeTrade(float $pnl, string $entry = '2026-08-20 10:00:00', array $extra = []): Trade
{
    return new Trade(array_merge([
        'ticket' => 'T' . fake()->unique()->numberBetween(1000, 99999),
        'direction' => 'long',
        'pnl' => $pnl,
        'entry_price' => 1.10000,
        'exit_price' => 1.10100,
        'size' => 1.0,
        'duration_minutes' => 60,
        'entry_time' => Carbon\Carbon::parse($entry),
        'exit_time' => Carbon\Carbon::parse($entry)->addHour(),
    ], $extra));
}

/** @param array<float> $pnls */
function tradesWith(array $pnls): Collection
{
    return collect($pnls)->map(fn ($pnl) => fakeTrade($pnl))->values();
}

// ─────────────────────────────────────────────────────────────────────────
// analyzeRiskOfRuin — el bug que motivó este fichero
// ─────────────────────────────────────────────────────────────────────────

it('no da riesgo de ruina a un trader que nunca ha perdido', function () {
    // Antes: el `?? 1` asumía 1 $ de pérdida media y devolvía 24,57%.
    $result = $this->service->analyzeRiskOfRuin(tradesWith(array_fill(0, 12, 142.50)), 100000);

    expect($result['risk_of_ruin'])->toBe(0.0)
        ->and($result['win_rate'])->toBe(100.0)
        ->and($result['payoff'])->toBeNull()
        ->and($result['edge'])->toBeNull();
});

it('mantiene todas las claves del array aunque no haya pérdidas', function () {
    // La vista lee riskData['streak_prob']['3'] sin guardas: si falta la clave, peta.
    $result = $this->service->analyzeRiskOfRuin(tradesWith(array_fill(0, 12, 100.0)), 50000);

    expect($result)->toHaveKeys(['win_rate', 'payoff', 'risk_of_ruin', 'streak_prob', 'edge'])
        ->and($result['streak_prob'])->toHaveKeys(['3', '5', '8', '10']);
});

it('devuelve 100% de riesgo de ruina cuando no hay ventaja estadística', function () {
    // 6 pérdidas de 100 contra 6 ganancias de 50: expectativa claramente negativa.
    $pnls = array_merge(array_fill(0, 6, 50.0), array_fill(0, 6, -100.0));

    expect($this->service->analyzeRiskOfRuin(tradesWith($pnls), 10000)['risk_of_ruin'])->toBe(100.0);
});

it('da un riesgo de ruina bajo a un sistema con ventaja clara y cuenta grande', function () {
    $pnls = array_merge(array_fill(0, 9, 300.0), array_fill(0, 3, -100.0));
    $result = $this->service->analyzeRiskOfRuin(tradesWith($pnls), 100000);

    expect($result['edge'])->toBeGreaterThan(0)
        ->and($result['risk_of_ruin'])->toBeLessThan(1.0);
});

it('exige al menos 10 trades para hablar de riesgo de ruina', function () {
    expect($this->service->analyzeRiskOfRuin(tradesWith(array_fill(0, 9, 100.0)), 10000))->toBeNull();
});

it('nunca devuelve un riesgo de ruina fuera del rango 0-100', function () {
    $pnls = array_merge(array_fill(0, 11, 1000.0), [-0.01]);
    $result = $this->service->analyzeRiskOfRuin(tradesWith($pnls), 1000000);

    expect($result['risk_of_ruin'])->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(100.0);
});

// ─────────────────────────────────────────────────────────────────────────
// calculateSystemHealth
// ─────────────────────────────────────────────────────────────────────────

it('no reporta profit factor cero cuando no hay pérdidas', function () {
    // Cero se leía como "el peor sistema posible"; lo correcto es indefinido.
    expect($this->service->calculateSystemHealth(tradesWith(array_fill(0, 8, 100.0)))['profit_factor'])
        ->toBeNull();
});

it('calcula el profit factor como ganancia bruta entre pérdida bruta', function () {
    $pnls = array_merge(array_fill(0, 3, 100.0), array_fill(0, 3, -50.0)); // 300 / 150 = 2.0

    expect($this->service->calculateSystemHealth(tradesWith($pnls))['profit_factor'])->toBe(2.0);
});

it('exige al menos 5 trades para calcular la salud del sistema', function () {
    expect($this->service->calculateSystemHealth(tradesWith([100.0, -50.0, 20.0, 30.0])))->toBeNull();
});

it('devuelve SQN cero cuando todos los trades dan lo mismo (sin dispersión)', function () {
    // Desviación típica 0: la fórmula dividiría por cero.
    expect($this->service->calculateSystemHealth(tradesWith(array_fill(0, 6, 100.0)))['sqn'])->toBe(0.0);
});

it('cuenta como perdedores los trades con PnL exactamente cero', function () {
    $pnls = array_merge(array_fill(0, 5, 100.0), array_fill(0, 5, 0.0));

    expect($this->service->calculateSystemHealth(tradesWith($pnls))['win_rate'])->toBe(50.0);
});

// ─────────────────────────────────────────────────────────────────────────
// analyzeDistribution — el histograma perdía el mejor trade
// ─────────────────────────────────────────────────────────────────────────

it('incluye el mejor trade en el último tramo del histograma', function () {
    // Con `< $high` en el último bucket, el máximo caía fuera y no se contaba nunca.
    $trades = tradesWith([-100.0, 0.0, 50.0, 500.0]);
    $result = $this->service->analyzeDistribution($trades);

    expect(array_sum($result['data']))->toBe($trades->count());
});

it('devuelve vacío si todos los trades tienen el mismo PnL', function () {
    expect($this->service->analyzeDistribution(tradesWith([100.0, 100.0, 100.0])))->toBe([]);
});

// ─────────────────────────────────────────────────────────────────────────
// analyzeTraderProfile
// ─────────────────────────────────────────────────────────────────────────

it('puntúa el ratio R:R al máximo cuando no hay pérdidas', function () {
    expect($this->service->analyzeTraderProfile(tradesWith(array_fill(0, 6, 100.0)))['Ratio R:R'])
        ->toBe(100);
});

it('puntúa el ratio R:R a cero cuando no hay ganancias', function () {
    expect($this->service->analyzeTraderProfile(tradesWith(array_fill(0, 6, -100.0)))['Ratio R:R'])
        ->toBe(0);
});

it('mantiene todas las puntuaciones del radar entre 0 y 100', function () {
    $pnls = array_merge(array_fill(0, 20, 5000.0), [-1.0]);

    foreach ($this->service->analyzeTraderProfile(tradesWith($pnls)) as $valor) {
        expect($valor)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
    }
});

// ─────────────────────────────────────────────────────────────────────────
// Agregaciones por tiempo
// ─────────────────────────────────────────────────────────────────────────

it('agrupa el PnL por hora de entrada devolviendo las 24 franjas', function () {
    $trades = collect([
        fakeTrade(100.0, '2026-08-20 09:30:00'),
        fakeTrade(50.0, '2026-08-20 09:45:00'),
        fakeTrade(-30.0, '2026-08-20 15:00:00'),
    ]);

    $byHour = collect($this->service->analyzeByHour($trades))->keyBy('hour');

    expect($byHour)->toHaveCount(24)
        ->and($byHour['09:00']['pnl'])->toBe(150.0)
        ->and($byHour['15:00']['pnl'])->toBe(-30.0);
});

it('asigna cada trade a su sesión de mercado', function () {
    $trades = collect([
        fakeTrade(10.0, '2026-08-20 03:00:00'),  // Asia
        fakeTrade(20.0, '2026-08-20 09:00:00'),  // Londres
        fakeTrade(40.0, '2026-08-20 15:00:00'),  // Nueva York
    ]);

    $bySession = collect($this->service->analyzeBySession($trades))->keyBy('session');

    expect($bySession['Asia']['pnl'])->toBe(10.0)
        ->and($bySession['Londres']['pnl'])->toBe(20.0)
        ->and($bySession['Nueva York']['pnl'])->toBe(40.0);
});

it('acumula la curva de capital día a día', function () {
    $trades = collect([
        fakeTrade(100.0, '2026-08-20 10:00:00'),
        fakeTrade(-40.0, '2026-08-21 10:00:00'),
    ]);

    $curve = $this->service->calculateEquityCurve($trades);

    expect($curve[0]['y'])->toBe(100.0)
        ->and($curve[1]['y'])->toBe(60.0);
});

// ─────────────────────────────────────────────────────────────────────────
// Colecciones vacías: ningún método debe reventar
// ─────────────────────────────────────────────────────────────────────────

it('tolera una colección vacía en todos los métodos públicos', function () {
    $vacio = collect();

    expect($this->service->calculateEquityCurve($vacio))->toBe([])
        ->and($this->service->calculateSystemHealth($vacio))->toBeNull()
        ->and($this->service->analyzeByHour($vacio))->toBe([])
        ->and($this->service->analyzeBySession($vacio))->toBe([])
        ->and($this->service->analyzeDurationScatter($vacio))->toBe([])
        ->and($this->service->analyzeDistribution($vacio))->toBe([])
        ->and($this->service->analyzeTradeEfficiency($vacio))->toBe([])
        ->and($this->service->analyzeTraderProfile($vacio))->toBeNull()
        ->and($this->service->analyzeRiskOfRuin($vacio, 10000))->toBeNull()
        ->and($this->service->analyzeMistakes($vacio))->toBe([]);
});
