<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use App\Services\Import\ImportPreset;
use App\Services\Import\TradeFileReader;
use App\Services\Import\TradeImporter;
use App\Services\Import\TradeRowMapper;

/**
 * @return array{0: Account, 1: array<int, array<string, string>>, 2: TradeRowMapper}
 */
function importSetup(string $fixture = 'ctrader.csv', float $initialBalance = 50000): array
{
    $account = Account::factory()->create([
        'user_id' => User::factory()->create()->id,
        'initial_balance' => $initialBalance,
        'current_balance' => $initialBalance,
    ]);

    $path = base_path('tests/Fixtures/import/' . $fixture);
    $parsed = (new TradeFileReader)->read($path, $fixture);
    $preset = ImportPreset::detect($parsed['headers']);

    $mapper = new TradeRowMapper($preset, $preset->guessMapping($parsed['headers']));

    return [$account, $parsed['rows'], $mapper];
}

it('importa un histórico de cTrader entero', function () {
    [$account, $rows, $mapper] = importSetup();

    $report = app(TradeImporter::class)->import($account, $rows, $mapper);

    expect($report->imported)->toBe(3)
        ->and($report->skipped)->toBe(0)
        ->and($report->failedCount)->toBe(0)
        ->and(Trade::where('account_id', $account->id)->count())->toBe(3);
});

it('no duplica nada al reimportar el mismo fichero', function () {
    [$account, $rows, $mapper] = importSetup();

    app(TradeImporter::class)->import($account, $rows, $mapper);
    $second = app(TradeImporter::class)->import($account, $rows, $mapper);

    expect($second->imported)->toBe(0)
        ->and($second->skipped)->toBe(3)
        ->and(Trade::where('account_id', $account->id)->count())->toBe(3);
});

it('deduplica también sin identificador del broker', function () {
    // El CSV genérico no trae ni ticket ni position id: la clave la sintetiza
    // el importador a partir de la propia operación.
    [$account, $rows, $mapper] = importSetup('generico.csv');

    app(TradeImporter::class)->import($account, $rows, $mapper);
    $second = app(TradeImporter::class)->import($account, $rows, $mapper);

    expect($second->skipped)->toBe(2)
        ->and(Trade::where('account_id', $account->id)->count())->toBe(2);
});

it('crea los símbolos que no existían', function () {
    [$account, $rows, $mapper] = importSetup();

    $report = app(TradeImporter::class)->import($account, $rows, $mapper);

    expect($report->newSymbols)->toContain('EURUSD', 'XAUUSD', 'NAS100')
        ->and(TradeAsset::where('symbol', 'XAUUSD')->exists())->toBeTrue();
});

it('reutiliza un símbolo ya existente en vez de duplicarlo', function () {
    TradeAsset::factory()->create(['symbol' => 'EURUSD']);

    [$account, $rows, $mapper] = importSetup();
    app(TradeImporter::class)->import($account, $rows, $mapper);

    expect(TradeAsset::where('symbol', 'EURUSD')->count())->toBe(1);
});

it('recalcula el balance de la cuenta con lo importado', function () {
    [$account, $rows, $mapper] = importSetup();

    app(TradeImporter::class)->import($account, $rows, $mapper);

    // 170,00 + 168,00 − 45,00 = 293,00
    expect((float) $account->fresh()->current_balance)->toBe(50293.00);
});

it('deja el balance en paz si se le pide', function () {
    [$account, $rows, $mapper] = importSetup();

    app(TradeImporter::class)->import($account, $rows, $mapper, null, recalculateBalance: false);

    expect((float) $account->fresh()->current_balance)->toBe(50000.00);
});

it('separa las filas que no se pueden interpretar de las que sí', function () {
    [$account, $rows, $mapper] = importSetup();
    $rows[] = ['Symbol' => 'EURUSD', 'Direction' => 'lo que sea', 'Entry time' => 'ayer'];

    $report = app(TradeImporter::class)->import($account, $rows, $mapper);

    expect($report->imported)->toBe(3)
        ->and($report->failedCount)->toBe(1)
        ->and($report->failed[0]['errors'])->not->toBeEmpty();
});

it('lee los negativos entre paréntesis de DXtrade', function () {
    [$account, $rows, $mapper] = importSetup('dxtrade.csv');

    app(TradeImporter::class)->import($account, $rows, $mapper);

    $losing = Trade::where('account_id', $account->id)->where('pnl', '<', 0)->first();

    expect((float) $losing->pnl)->toBe(-25.0);
});

it('importa el informe HTML de MetaTrader', function () {
    $account = Account::factory()->create([
        'user_id' => User::factory()->create()->id,
        'initial_balance' => 10000,
    ]);

    $parsed = (new TradeFileReader)->read(base_path('tests/Fixtures/import/mt4-statement.html'), 'mt4-statement.html');

    $mapping = [];
    foreach (array_keys(ImportPreset::FIELDS) as $field) {
        $mapping[$field] = in_array($field, $parsed['headers'], true) ? $field : null;
    }

    $mapper = new TradeRowMapper(ImportPreset::find('metatrader'), $mapping);
    $report = app(TradeImporter::class)->import($account, $parsed['rows'], $mapper);

    expect($report->imported)->toBe(2);

    $trade = Trade::where('ticket', '7712345')->first();

    expect($trade)->not->toBeNull()
        ->and($trade->direction)->toBe('long')
        ->and((float) $trade->pnl)->toBe(170.0)
        ->and($trade->duration_minutes)->toBe(147);
});

it('un ticket repetido en otra cuenta ya no rompe la importación', function () {
    // Antes de la migración de la Fase 2, trades.ticket era único global y esto
    // reventaba con una violación de unicidad.
    [$first, $rows, $mapper] = importSetup();

    $second = Account::factory()->create([
        'user_id' => User::factory()->create()->id,
        'initial_balance' => 25000,
    ]);

    app(TradeImporter::class)->import($first, $rows, $mapper);
    $report = app(TradeImporter::class)->import($second, $rows, $mapper);

    expect($report->imported)->toBe(3)
        ->and($report->failedCount)->toBe(0);
});
