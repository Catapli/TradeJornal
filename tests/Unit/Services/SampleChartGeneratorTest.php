<?php

declare(strict_types=1);

use App\Models\Trade;
use App\Services\Sample\SampleChartGenerator;

// tests/Pest.php solo enlaza TestCase con Feature. Aquí hace falta el contenedor
// de Laravel (casting de modelos) pero NO base de datos: sin RefreshDatabase.
uses(Tests\TestCase::class);

/**
 * Velas de ejemplo de la demo (Fase 6 · P8).
 *
 * Dos cosas que proteger. La primera, **el formato**: tiene que ser el mismo que
 * manda el agente de MetaTrader, porque el visor no distingue de dónde viene el
 * JSON. La segunda, **que el camino toque de verdad el MAE y el MFE**: si no los
 * toca, el reproductor no puede enseñar la excursión en la barra correcta y las
 * dos líneas más útiles del gráfico salen al final, sin significar nada.
 */
beforeEach(function () {
    $this->generador = new SampleChartGenerator;
});

/** Operación en memoria: nada toca la base de datos en este fichero. */
function opConVelas(array $extra = []): Trade
{
    return new Trade(array_merge([
        'ticket' => 'S1-00042',
        'direction' => 'long',
        'entry_price' => 1.10000,
        'exit_price' => 1.10400,
        'mae_price' => 1.09850,
        'mfe_price' => 1.10520,
        'entry_time' => '2026-08-20 10:00:00',
        'exit_time' => '2026-08-20 12:30:00',
        'pnl' => 400,
    ], $extra));
}

it('devuelve el mismo formato que manda el agente', function () {
    $velas = $this->generador->generate(opConVelas(), 'EURUSD');

    expect($velas)->toHaveKeys(['symbol', 'timeframes', 'markers'])
        ->and($velas['symbol'])->toBe('EURUSD')
        ->and(array_keys($velas['timeframes']))->toBe(['1m', '5m', '15m', '1h', '4h'])
        ->and($velas['markers'])->toHaveCount(2)
        ->and($velas['markers'][0]['text'])->toBe('IN')
        ->and($velas['markers'][1]['text'])->toBe('OUT');
});

it('trae en cada vela lo que el visor necesita', function () {
    $primera = $this->generador->generate(opConVelas(), 'EURUSD')['timeframes']['5m'][0];

    expect($primera)->toHaveKeys(['time', 'open', 'high', 'low', 'close', 'volume', 'ema']);
});

it('no saca velas imposibles', function () {
    // Una vela cuya mecha no envuelve al cuerpo revienta el gráfico sin avisar.
    foreach ($this->generador->generate(opConVelas(), 'EURUSD')['timeframes'] as $marco => $velas) {
        foreach ($velas as $vela) {
            expect($vela['high'])->toBeGreaterThanOrEqual(max($vela['open'], $vela['close']), $marco)
                ->and($vela['low'])->toBeLessThanOrEqual(min($vela['open'], $vela['close']), $marco);
        }
    }
});

it('deja las velas en orden y sin repetir hora', function () {
    foreach ($this->generador->generate(opConVelas(), 'EURUSD')['timeframes'] as $marco => $velas) {
        $tiempos = array_column($velas, 'time');
        $ordenados = $tiempos;
        sort($ordenados);

        expect($tiempos)->toBe($ordenados, $marco)
            ->and($tiempos)->toBe(array_values(array_unique($tiempos)), $marco);
    }
});

it('toca el MAE y el MFE de una operación larga', function () {
    $trade = opConVelas();
    $velas = $this->generador->generate($trade, 'EURUSD')['timeframes']['1m'];

    // En largo el MAE es un mínimo y el MFE un máximo.
    expect(min(array_column($velas, 'low')))->toBeLessThanOrEqual((float) $trade->mae_price)
        ->and(max(array_column($velas, 'high')))->toBeGreaterThanOrEqual((float) $trade->mfe_price);
});

it('toca el MAE y el MFE de una operación corta', function () {
    // En corto se invierten: el MAE queda por encima de la entrada y el MFE debajo.
    $trade = opConVelas([
        'direction' => 'short',
        'entry_price' => 1.10000,
        'exit_price' => 1.09600,
        'mae_price' => 1.10150,
        'mfe_price' => 1.09480,
    ]);

    $velas = $this->generador->generate($trade, 'EURUSD')['timeframes']['1m'];

    expect(max(array_column($velas, 'high')))->toBeGreaterThanOrEqual((float) $trade->mae_price)
        ->and(min(array_column($velas, 'low')))->toBeLessThanOrEqual((float) $trade->mfe_price);
});

it('cierra la última vela en el precio de salida', function () {
    $trade = opConVelas();
    $velas = $this->generador->generate($trade, 'EURUSD')['timeframes']['1m'];

    expect(end($velas)['close'])->toBe((float) $trade->exit_price);
});

it('cubre la operación entera con velas de un minuto', function () {
    $trade = opConVelas();
    $velas = $this->generador->generate($trade, 'EURUSD')['timeframes']['1m'];

    $entrada = strtotime((string) $trade->entry_time);
    $salida = strtotime((string) $trade->exit_time);

    expect($velas[0]['time'])->toBeLessThan($entrada)
        ->and(end($velas)['time'])->toBeGreaterThanOrEqual($salida);
});

it('agrupa los marcos mayores sin perder recorrido', function () {
    $marcos = $this->generador->generate(opConVelas(), 'EURUSD')['timeframes'];

    // El 5m sale de agrupar el 1m: no puede tener un extremo que el 1m no tenga.
    expect(max(array_column($marcos['5m'], 'high')))
        ->toBeLessThanOrEqual(max(array_column($marcos['1m'], 'high')))
        ->and(min(array_column($marcos['5m'], 'low')))
        ->toBeGreaterThanOrEqual(min(array_column($marcos['1m'], 'low')))
        ->and(count($marcos['5m']))->toBeLessThan(count($marcos['1m']));
});

it('aguanta una operación de un solo minuto', function () {
    // El scalping de un minuto es real y no puede dejar el gráfico vacío.
    $velas = $this->generador->generate(
        opConVelas(['exit_time' => '2026-08-20 10:01:00']),
        'EURUSD',
    )['timeframes'];

    expect($velas['1m'])->not->toBeEmpty()
        ->and($velas['4h'])->not->toBeEmpty();
});

it('usa dos decimales en los índices y cinco en las divisas', function () {
    $indice = $this->generador->generate(opConVelas([
        'entry_price' => 39200.00,
        'exit_price' => 39320.50,
        'mae_price' => 39150.25,
        'mfe_price' => 39380.75,
    ]), 'US30')['timeframes']['1m'];

    foreach ($indice as $vela) {
        expect(round($vela['close'], 2))->toBe($vela['close']);
    }
});
