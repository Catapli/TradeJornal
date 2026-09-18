<?php

declare(strict_types=1);

use App\Services\Import\ImportPreset;
use App\Services\Import\TradeFileReader;

function fixture(string $name): string
{
    return base_path('tests/Fixtures/import/' . $name);
}

function readFixture(string $name): array
{
    return (new TradeFileReader)->read(fixture($name), $name);
}

it('detecta el punto y coma como delimitador', function () {
    $parsed = readFixture('ctrader.csv');

    expect($parsed['format'])->toBe('csv')
        ->and($parsed['headers'])->toContain('Position ID')
        ->and($parsed['rows'])->toHaveCount(3)
        ->and($parsed['rows'][0]['Symbol'])->toBe('EURUSD');
});

it('lee un CSV con cabeceras en español y acentos', function () {
    $parsed = readFixture('generico.csv');

    expect($parsed['headers'])->toContain('Símbolo')
        ->and($parsed['rows'][0]['Dirección'])->toBe('largo');
});

it('extrae las operaciones cerradas de un informe de MetaTrader 4', function () {
    $parsed = readFixture('mt4-statement.html');

    expect($parsed['format'])->toBe('metatrader_html')
        ->and($parsed['rows'])->toHaveCount(2);

    $first = $parsed['rows'][0];

    expect($first['ticket'])->toBe('7712345')
        ->and($first['symbol'])->toBe('eurusd')
        ->and($first['direction'])->toBe('buy')
        ->and($first['size'])->toBe('0.50')
        ->and($first['entry_price'])->toBe('1.08420')
        ->and($first['exit_price'])->toBe('1.08760')
        ->and($first['commission'])->toBe('-3.50')
        ->and($first['swap'])->toBe('-1.20')
        ->and($first['pnl'])->toBe('170.00');
});

it('extrae las operaciones de un informe de MetaTrader 5, donde el símbolo va antes del tipo', function () {
    $parsed = readFixture('mt5-statement.html');
    $first = $parsed['rows'][0];

    expect($parsed['rows'])->toHaveCount(1)
        ->and($first['ticket'])->toBe('880011')
        ->and($first['symbol'])->toBe('eurusd')
        ->and($first['size'])->toBe('0.50')
        ->and($first['entry_price'])->toBe('1.08420')
        ->and($first['exit_price'])->toBe('1.08760')
        ->and($first['commission'])->toBe('-3.50')
        ->and($first['swap'])->toBe('-1.20')
        ->and($first['pnl'])->toBe('170.00');
});

it('ignora las filas de resumen del informe', function () {
    // El fixture de MT4 lleva una fila «Closed P/L:» que no es una operación.
    expect(readFixture('mt4-statement.html')['rows'])->toHaveCount(2);
});

it('desambigua las cabeceras repetidas', function () {
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.csv';
    file_put_contents($path, "Time,Price,Time,Price\n1,2,3,4\n");

    $parsed = (new TradeFileReader)->read($path, 'dup.csv');
    unlink($path);

    expect($parsed['headers'])->toBe(['Time', 'Price', 'Time (2)', 'Price (2)']);
});

it('reconoce la plataforma por sus cabeceras', function (string $file, string $expected) {
    $parsed = readFixture($file);

    expect(ImportPreset::detect($parsed['headers'])->key)->toBe($expected);
})->with([
    ['ctrader.csv', 'ctrader'],
    ['dxtrade.csv', 'dxtrade'],
    ['generico.csv', 'generic'],
]);

it('empareja cada cabecera con un solo campo', function () {
    $parsed = readFixture('ctrader.csv');
    $mapping = ImportPreset::detect($parsed['headers'])->guessMapping($parsed['headers']);

    $used = array_filter($mapping);

    expect($mapping['symbol'])->toBe('Symbol')
        ->and($mapping['entry_price'])->toBe('Entry price')
        ->and($mapping['exit_price'])->toBe('Closing price')
        ->and($mapping['position_id'])->toBe('Position ID')
        ->and($used)->toHaveCount(count(array_unique($used)));
});

it('protesta con un fichero vacío', function () {
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.csv';
    file_put_contents($path, '');

    expect(fn () => (new TradeFileReader)->read($path, 'vacio.csv'))
        ->toThrow(RuntimeException::class);

    unlink($path);
});
