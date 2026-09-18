<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Actions\Accounts\GenerateBalanceChartData;
use App\Actions\Strategy\RecalculateStrategyStats;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\Trade;
use App\Models\TradeAsset;
use Illuminate\Support\Facades\DB;

/**
 * Inserta en una cuenta las filas ya mapeadas de un fichero.
 *
 * Tres cosas que hace y conviene no perder de vista:
 *
 *  1. **Deduplica.** Volver a subir el mismo histórico es el caso normal, no el
 *     raro. Se omite lo que ya está por `position_id` o por `ticket` dentro de la
 *     cuenta, y también los repetidos dentro del propio fichero.
 *  2. **Resuelve símbolos de golpe.** Un `firstOrCreate` por fila serían dos
 *     consultas por operación; con 2.000 filas eso es media importación en la BD.
 *  3. **Inserta sin eventos** y rehace al final lo que haría `TradeObserver`
 *     (caché de la cuenta y estadísticas de estrategia), en vez de encolar un
 *     recálculo por cada fila.
 */
class TradeImporter
{
    public function __construct(
        private readonly RecalculateStrategyStats $recalculateStrategy,
    ) {}

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function import(
        Account $account,
        array $rows,
        TradeRowMapper $mapper,
        ?int $strategyId = null,
        bool $recalculateBalance = true,
    ): ImportReport {
        $report = new ImportReport;

        $mapped = [];
        $rowNumber = 1; // la 1 es la cabecera

        foreach ($rows as $row) {
            $rowNumber++;
            $result = $mapper->map($row);

            if ($result['data'] === null) {
                $report->fail($rowNumber, $result['errors']);

                continue;
            }

            $mapped[] = $result['data'];
        }

        if ($mapped === []) {
            return $report;
        }

        $assetIds = $this->resolveAssets($mapped, $report);
        [$existingPositions, $existingTickets] = $this->existingKeys($account);

        $toInsert = [];
        $seenPositions = [];
        $seenTickets = [];
        $now = now();
        $initialBalance = (float) $account->initial_balance ?: 1.0;

        foreach ($mapped as $index => $data) {
            // Sin identificador del broker se sintetiza uno estable a partir de la
            // propia operación: así reimportar el mismo fichero tampoco duplica.
            $positionId = $data['position_id'] ?? $this->syntheticId($account, $data);

            if (isset($existingPositions[$positionId]) || isset($seenPositions[$positionId])) {
                $report->skipped++;

                continue;
            }

            $ticket = $data['ticket'];

            if ($ticket !== null && (isset($existingTickets[$ticket]) || isset($seenTickets[$ticket]))) {
                $report->skipped++;

                continue;
            }

            $seenPositions[$positionId] = true;

            if ($ticket !== null) {
                $seenTickets[$ticket] = true;
            }

            $toInsert[] = [
                'account_id' => $account->id,
                'trade_asset_id' => $assetIds[$data['symbol']],
                'strategy_id' => $strategyId,
                'ticket' => $ticket,
                'position_id' => $positionId,
                'direction' => $data['direction'],
                'entry_price' => $data['entry_price'],
                'exit_price' => $data['exit_price'],
                'size' => $data['size'],
                'pnl' => $data['pnl'],
                'pnl_percentage' => round($data['pnl'] / $initialBalance * 100, 4),
                'duration_minutes' => $data['duration_minutes'],
                'entry_time' => $data['entry_time'],
                'exit_time' => $data['exit_time'],
                'mae_price' => $data['mae_price'],
                'mfe_price' => $data['mfe_price'],
                'notes' => $data['notes'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            unset($mapped[$index]);
        }

        if ($toInsert !== []) {
            DB::transaction(function () use ($toInsert, &$report) {
                Trade::withoutEvents(function () use ($toInsert, &$report) {
                    foreach (array_chunk($toInsert, 200) as $chunk) {
                        Trade::insert($chunk);
                        $report->imported += count($chunk);
                    }
                });
            });

            $this->refreshDerivedData($account, $strategyId, $recalculateBalance);
        }

        return $report;
    }

    /**
     * Crea los símbolos que falten y devuelve símbolo => id.
     *
     * @param  array<int, array<string, mixed>>  $mapped
     * @return array<string, int>
     */
    private function resolveAssets(array $mapped, ImportReport $report): array
    {
        $symbols = array_values(array_unique(array_column($mapped, 'symbol')));

        $existing = TradeAsset::whereIn('symbol', $symbols)->pluck('id', 'symbol')->all();
        $missing = array_diff($symbols, array_keys($existing));

        if ($missing !== []) {
            $now = now();

            TradeAsset::insert(array_map(static fn (string $symbol) => [
                'symbol' => $symbol,
                'name' => $symbol,
                'created_at' => $now,
                'updated_at' => $now,
            ], array_values($missing)));

            $report->newSymbols = array_values($missing);
            $existing = TradeAsset::whereIn('symbol', $symbols)->pluck('id', 'symbol')->all();
        }

        return $existing;
    }

    /**
     * Claves ya presentes en la cuenta, para no volver a insertarlas.
     *
     * @return array{0: array<string, true>, 1: array<string, true>}
     */
    private function existingKeys(Account $account): array
    {
        $rows = Trade::where('account_id', $account->id)
            ->select(['position_id', 'ticket'])
            ->get();

        $positions = [];
        $tickets = [];

        foreach ($rows as $row) {
            if ($row->position_id !== null) {
                $positions[(string) $row->position_id] = true;
            }

            if ($row->ticket !== null) {
                $tickets[(string) $row->ticket] = true;
            }
        }

        return [$positions, $tickets];
    }

    /** @param array<string, mixed> $data */
    private function syntheticId(Account $account, array $data): string
    {
        return 'imp-' . substr(md5(implode('|', [
            $account->id,
            $data['symbol'],
            $data['direction'],
            $data['entry_time']->toDateTimeString(),
            $data['exit_time']->toDateTimeString(),
            (string) $data['entry_price'],
            (string) $data['exit_price'],
            (string) $data['size'],
        ])), 0, 24);
    }

    /**
     * Lo que normalmente haría TradeObserver, pero una sola vez por importación.
     *
     * El balance se rehace desde las operaciones porque en una cuenta que no
     * sincroniza nadie más lo mantiene: quedarse con el balance inicial después
     * de importar un año de historial deja el panel diciendo que no has ganado
     * ni perdido nada.
     */
    private function refreshDerivedData(Account $account, ?int $strategyId, bool $recalculateBalance): void
    {
        if ($recalculateBalance) {
            $total = (float) Trade::where('account_id', $account->id)->sum('pnl');
            $balance = round((float) $account->initial_balance + $total, 2);

            $account->forceFill([
                'current_balance' => $balance,
                'current_equity' => $balance,
            ])->save();
        }

        CalculateAccountStatistics::clearCache($account->id);
        GenerateBalanceChartData::clearCache($account->id);

        if ($strategyId !== null && ($strategy = Strategy::find($strategyId))) {
            $this->recalculateStrategy->execute($strategy);
        }
    }
}
