<?php

declare(strict_types=1);

use App\Services\Import\ImportPreset;
use App\Services\Import\TradeRowMapper;

function mapper(array $mapping = [], bool $feesIncluded = true, string $decimal = 'auto', string $preset = 'generic'): TradeRowMapper
{
    return new TradeRowMapper(
        ImportPreset::find($preset),
        array_merge([
            'symbol' => 'sym',
            'direction' => 'dir',
            'entry_time' => 'in',
            'exit_time' => 'out',
            'entry_price' => 'pin',
            'exit_price' => 'pout',
            'size' => 'vol',
            'pnl' => 'res',
        ], $mapping),
        $feesIncluded,
        $decimal,
    );
}

function baseRow(array $overrides = []): array
{
    return array_merge([
        'sym' => 'eurusd',
        'dir' => 'buy',
        'in' => '2026-03-10 09:15',
        'out' => '2026-03-10 11:42',
        'pin' => '1.08420',
        'pout' => '1.08760',
        'vol' => '0.50',
        'res' => '170.00',
    ], $overrides);
}

it('normaliza una fila correcta', function () {
    $result = mapper()->map(baseRow());

    expect($result['errors'])->toBe([])
        ->and($result['data']['symbol'])->toBe('EURUSD')
        ->and($result['data']['direction'])->toBe('long')
        ->and($result['data']['pnl'])->toBe(170.0)
        ->and($result['data']['duration_minutes'])->toBe(147);
});

it('entiende los decimales europeos con miles', function () {
    $result = mapper()->map(baseRow(['res' => '1.234,56', 'pin' => '2.380,50']));

    expect($result['data']['pnl'])->toBe(1234.56)
        ->and($result['data']['entry_price'])->toBe(2380.50);
});

it('entiende los decimales anglosajones con miles', function () {
    $result = mapper()->map(baseRow(['res' => '1,234.56']));

    expect($result['data']['pnl'])->toBe(1234.56);
});

it('respeta el separador forzado a coma', function () {
    // «1.234» con separador forzado a coma es mil doscientos treinta y cuatro,
    // no uno coma doscientos treinta y cuatro.
    $result = mapper(decimal: 'comma')->map(baseRow(['res' => '1.234']));

    expect($result['data']['pnl'])->toBe(1234.0);
});

it('lee los negativos entre paréntesis', function () {
    $result = mapper()->map(baseRow(['res' => '(25.00)']));

    expect($result['data']['pnl'])->toBe(-25.0);
});

it('quita el símbolo de divisa pegado al importe', function () {
    $result = mapper()->map(baseRow(['res' => '$ 170.00']));

    expect($result['data']['pnl'])->toBe(170.0);
});

it('reconoce las variantes de dirección', function (string $value, string $expected) {
    $result = mapper()->map(baseRow(['dir' => $value]));

    expect($result['data']['direction'])->toBe($expected);
})->with([
    ['buy', 'long'],
    ['Sell', 'short'],
    ['LONG', 'long'],
    ['compra', 'long'],
    ['venta', 'short'],
    ['Buy Limit', 'long'],
    ['sell_stop', 'short'],
]);

it('acepta varios formatos de fecha', function (string $value) {
    $result = mapper()->map(baseRow(['in' => $value]));

    expect($result['errors'])->toBe([])
        ->and($result['data']['entry_time']->format('Y-m-d'))->toBe('2026-03-10');
})->with([
    '2026-03-10 09:15:00',
    '2026.03.10 09:15:00',
    '10/03/2026 09:15:00',
    '10.03.2026 09:15',
]);

it('resta comisión y swap cuando el P&L es bruto', function () {
    $result = mapper(['commission' => 'com', 'swap' => 'swp'], feesIncluded: false)
        ->map(baseRow(['com' => '-3.50', 'swp' => '-1.20']));

    expect($result['data']['pnl'])->toBe(165.30);
});

it('no toca el P&L cuando ya es neto', function () {
    $result = mapper(['commission' => 'com', 'swap' => 'swp'], feesIncluded: true)
        ->map(baseRow(['com' => '-3.50', 'swp' => '-1.20']));

    expect($result['data']['pnl'])->toBe(170.0);
});

it('devuelve errores legibles en vez de reventar', function () {
    $result = mapper()->map(baseRow(['dir' => 'lo que sea', 'in' => 'ayer', 'res' => 'nada']));

    expect($result['data'])->toBeNull()
        ->and($result['errors'])->toHaveCount(3);
});

it('rechaza una salida anterior a la entrada', function () {
    $result = mapper()->map(baseRow(['out' => '2026-03-09 10:00']));

    expect($result['data'])->toBeNull()
        ->and($result['errors'][0])->toBe(__('import.errors.exit_before_entry'));
});

it('guarda el volumen siempre en positivo', function () {
    $result = mapper()->map(baseRow(['vol' => '-0.50']));

    expect($result['data']['size'])->toBe(0.5);
});
