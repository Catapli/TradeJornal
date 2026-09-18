<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Actions\Accounts\GenerateBalanceChartData;
use App\Jobs\StoreTradeChartJob;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use App\Notifications\NewTradeNotification;
use App\Services\StorageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Mt5SyncController extends Controller
{
    public function __construct(private StorageService $storage) {}

    /**
     * Alta/actualización de los trades de una cuenta desde el .exe de MT5.
     *
     * Puntos delicados de este método, para quien lo toque después:
     *
     * - Los trades se procesan dentro de una transacción con un SAVEPOINT por trade
     *   (`DB::transaction()` anidado). En PostgreSQL un error deja la transacción en
     *   estado abortado, así que sin savepoints un solo trade malo tumbaría todos los
     *   siguientes con "current transaction is aborted".
     * - El bucle corre con `Trade::withoutEvents()`. El TradeObserver invalida la caché
     *   de estadísticas y gráfico por cada trade (5 `Cache::forget`), lo que con
     *   `CACHE_STORE=database` son 5 DELETE por trade. Se invalida una sola vez al final.
     * - El JSON de velas se sube a R2 en un job, no aquí: ver StoreTradeChartJob.
     */
    public function sync(Request $request)
    {
        try {
            $data = $request->validate([
                'sync_token' => 'required|string',
                'account_login' => 'required|string',
                'broker' => 'required|string',
                'balance' => 'required|numeric',
                'trades' => 'array',

                // Antes solo se validaba que `trades` fuera un array y se accedía a los
                // campos a pelo: un `direction` fuera del enum reventaba contra la BD y
                // se contabilizaba como "error del trade" en vez de como payload inválido.
                'trades.*.position_id' => 'required',
                'trades.*.ticket' => 'required|string',
                'trades.*.trade_asset_symbol' => 'required|string|max:50',
                'trades.*.direction' => 'required|in:long,short',
                'trades.*.entry_price' => 'required|numeric',
                'trades.*.exit_price' => 'required|numeric',
                'trades.*.size' => 'required|numeric',
                'trades.*.pnl' => 'required|numeric',
                'trades.*.duration_minutes' => 'required|integer|min:0',
                'trades.*.entry_time' => 'required|date',
                'trades.*.exit_time' => 'required|date',
                // El comentario del bróker es opcional: una versión antigua del
                // agente no lo manda y su operación tiene que entrar igual.
                'trades.*.notes' => 'nullable|string',
                'trades.*.mae_price' => 'nullable|numeric',
                'trades.*.mfe_price' => 'nullable|numeric',
                'trades.*.pips_traveled' => 'nullable|numeric',
                'trades.*.executions_data' => 'nullable|array',
                'trades.*.chart_data' => 'nullable|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('MT5 sync con payload inválido', [
                'ip' => $request->ip(),
                'errors' => $e->errors(),
            ]);

            return response()->json(['error' => 'Datos inválidos', 'details' => $e->errors()], 422);
        }

        // 1. USUARIO Y PERMISOS
        $user = User::where('sync_token', $data['sync_token'])->first();
        if (!$user) {
            Log::warning('MT5 sync con token inválido', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Token de usuario inválido.'], 404);
        }

        if (!$user->hasProAccess()) {
            return response()->json(['error' => 'Usuario sin suscripción activa.'], 403);
        }

        // 2. CUENTA — vía la relación, así que el mt5_login es forzosamente suyo
        $account = $user->accounts()
            ->where('mt5_login', $data['account_login'])
            ->first();

        if (!$account) {
            // Una cuenta archivada existe pero el scope global la esconde. Sin
            // este aviso, el terminal de un usuario que acaba de archivarla
            // recibiría «no existe» y se buscaría el problema donde no está.
            $archived = $user->accounts()->onlyTrashed()
                ->where('mt5_login', $data['account_login'])
                ->exists();

            return response()->json([
                'error' => $archived
                    ? "La cuenta {$data['account_login']} está archivada: restáurala en TradeForge para volver a sincronizarla."
                    : "La cuenta {$data['account_login']} no existe o no pertenece a este usuario.",
            ], 404);
        }

        $trades = $data['trades'] ?? [];

        // Ids ya notificados: una sola consulta en vez de una por trade dentro del bucle.
        $notifiedTradeIds = $user->notifications()
            ->where('type', NewTradeNotification::class)
            ->latest()
            ->take(50)
            ->pluck('data')
            ->map(fn ($payload) => data_get($payload, 'trade_id'))
            ->filter()
            ->all();

        // Símbolos resueltos de golpe: `firstOrCreate` por trade era una consulta por trade
        // aunque todos operen el mismo par.
        $assetIds = $this->resolveAssetIds($trades);

        $inserted = 0;
        $errors = [];
        $pending = [];   // [tradeId, ticket, chartData] para subir a R2 fuera del request
        $notify = [];   // trades recién cerrados que merecen notificación

        DB::transaction(function () use (
            $trades, $account, $assetIds, $notifiedTradeIds, $data,
            &$inserted, &$errors, &$pending, &$notify
        ) {
            Trade::withoutEvents(function () use (
                $trades, $account, $assetIds, $notifiedTradeIds,
                &$inserted, &$errors, &$pending, &$notify
            ) {
                foreach ($trades as $index => $tradeData) {
                    try {
                        // Transacción anidada = SAVEPOINT: si este trade falla, se
                        // deshace solo él y el resto del lote sigue.
                        DB::transaction(function () use (
                            $tradeData, $account, $assetIds, $notifiedTradeIds,
                            &$inserted, &$pending, &$notify
                        ) {
                            $initialBalance = (float) $account->initial_balance ?: 1;

                            $trade = Trade::updateOrCreate(
                                [
                                    'account_id' => $account->id,
                                    'position_id' => $tradeData['position_id'],
                                ],
                                [
                                    'ticket' => $tradeData['ticket'],
                                    'trade_asset_id' => $assetIds[$tradeData['trade_asset_symbol']],
                                    'direction' => $tradeData['direction'],
                                    'entry_price' => $tradeData['entry_price'],
                                    'exit_price' => $tradeData['exit_price'],
                                    'size' => $tradeData['size'],
                                    'pnl' => $tradeData['pnl'],
                                    'pnl_percentage' => ($tradeData['pnl'] / $initialBalance) * 100,
                                    'duration_minutes' => $tradeData['duration_minutes'],
                                    'entry_time' => $tradeData['entry_time'],
                                    'exit_time' => $tradeData['exit_time'],
                                    'mae_price' => $tradeData['mae_price'] ?? null,
                                    'mfe_price' => $tradeData['mfe_price'] ?? null,
                                    'executions_data' => $tradeData['executions_data'] ?? [],
                                    'pips_traveled' => $tradeData['pips_traveled'] ?? null,
                                ]
                            );

                            // El comentario del bróker solo se copia al crear: en una
                            // resincronización machacaría lo que el usuario haya
                            // escrito en la operación.
                            if ($trade->wasRecentlyCreated && filled($tradeData['notes'] ?? null)) {
                                $trade->forceFill(['notes' => $tradeData['notes']])->save();
                            }

                            if (!empty($tradeData['chart_data'])) {
                                $pending[] = [$trade->id, $tradeData['ticket'], $tradeData['chart_data']];
                            }

                            $recienCerrado = Carbon::parse($tradeData['exit_time'])
                                ->greaterThan(now()->subHour());

                            if ($recienCerrado && !in_array($trade->id, $notifiedTradeIds)) {
                                $notify[] = $trade->id;
                            }

                            $inserted++;
                        });
                    } catch (\Throwable $e) {
                        Log::error("MT5 sync — trade {$index} descartado: {$e->getMessage()}");

                        $errors[] = [
                            'index' => $index,
                            'position_id' => $tradeData['position_id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            });

            $account->update([
                'last_sync' => now(),
                'current_balance' => $data['balance'],
                'sync_error' => count($errors) > 0,
                'sync_error_message' => count($errors) > 0
                    ? implode("\n", array_map(fn ($e) => "Trade {$e['position_id']}: {$e['error']}", $errors))
                    : null,
            ]);
        });

        // Fuera de la transacción: nada de esto debe poder tumbar el guardado.
        $this->dispatchChartUploads($pending, $user->id);
        $this->sendTradeNotifications($user, $notify);

        // El bucle corrió con withoutEvents, así que la invalidación va aquí, una vez.
        CalculateAccountStatistics::clearCache($account->id);
        GenerateBalanceChartData::clearCache($account->id);

        Log::info('MT5 sync completado', [
            'user_id' => $user->id,
            'account' => $account->mt5_login,
            'inserted' => $inserted,
            'errors' => count($errors),
        ]);

        return response()->json([
            'status' => 'ok',
            'inserted' => $inserted,
            'errors' => $errors,
            'account' => $account->mt5_login,
            'balance' => $data['balance'],
        ]);
    }

    /**
     * Resuelve (creando los que falten) los ids de los símbolos del lote en dos consultas,
     * en vez de un `firstOrCreate` por trade.
     *
     * @return array<string, int> símbolo => id
     */
    private function resolveAssetIds(array $trades): array
    {
        $symbols = collect($trades)->pluck('trade_asset_symbol')->filter()->unique();

        if ($symbols->isEmpty()) {
            return [];
        }

        $existing = TradeAsset::whereIn('symbol', $symbols)->pluck('id', 'symbol');

        foreach ($symbols->diff($existing->keys()) as $symbol) {
            $existing[$symbol] = TradeAsset::create(['symbol' => $symbol, 'name' => $symbol])->id;
        }

        return $existing->all();
    }

    /** Encola las subidas del JSON de velas a R2. */
    private function dispatchChartUploads(array $pending, int $userId): void
    {
        foreach ($pending as [$tradeId, $ticket, $chartData]) {
            StoreTradeChartJob::dispatch($tradeId, $userId, $ticket, $chartData);
        }
    }

    /** Notifica los trades recién cerrados. Nunca debe romper el sync. */
    private function sendTradeNotifications(User $user, array $tradeIds): void
    {
        if (empty($tradeIds)) {
            return;
        }

        try {
            Trade::with('tradeAsset')->findMany($tradeIds)
                ->each(fn (Trade $trade) => $user->notify(new NewTradeNotification($trade)));
        } catch (\Throwable $e) {
            Log::warning("MT5 sync — fallo al notificar: {$e->getMessage()}");
        }
    }

    public function resetSync(Request $request)
    {
        Log::info('🧨 RESET SYNC solicitado');

        try {
            $request->validate([
                'sync_token' => 'required|string',
                'account_login' => 'required|string',
            ]);

            $user = User::where('sync_token', $request->sync_token)->first();
            if (!$user) {
                return response()->json(['error' => 'Token inválido'], 404);
            }

            if (!$user->hasProAccess()) {
                Log::warning("⚠️ Usuario sin suscripción PRO activa: ID={$user->id}");

                return response()->json(['error' => 'No tienes el plan PRO activo.'], 403);
            }

            // La búsqueda mediante la relación $user->accounts() garantiza que
            // la cuenta con ese mt5_login pertenece al usuario del sync_token.
            $account = $user->accounts()
                ->where('mt5_login', $request->account_login)
                ->first();

            if (!$account) {
                return response()->json(['error' => 'Cuenta no encontrada o no pertenece a este usuario'], 404);
            }

            $deletedCount = $account->trades()->count();

            // Solo las sincronizadas tienen carpeta en R2: las escritas a mano o
            // importadas no llevan ticket, y pedir su carpeta reventaba el reset.
            $account->trades()
                ->whereNotNull('ticket')
                ->pluck('ticket')
                ->each(fn (string $ticket) => $this->storage->deleteTradeFiles($user->id, $ticket));

            $account->trades()->delete();

            Log::info("✅ RESET completado: {$deletedCount} trades eliminados");

            return response()->json(['status' => 'ok', 'message' => 'Cuenta reseteada correctamente']);
        } catch (\Throwable $e) {
            // Throwable y no Exception: un error de tipo se colaba entero y el
            // agente recibía una página de error en vez de una respuesta JSON.
            Log::error("❌ ERROR EN RESET — {$e->getMessage()}");

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function refreshCharts(Request $request)
    {
        Log::info('🔄 REFRESH CHARTS solicitado');

        try {
            $validated = $request->validate([
                'sync_token' => 'required|string',
                'account_login' => 'required|string',
                'days_back' => 'nullable|integer|min:1|max:365',
                'position_ids' => 'nullable|array',
            ]);

            $user = User::where('sync_token', $validated['sync_token'])->first();
            if (!$user) {
                return response()->json(['error' => 'Token inválido.'], 404);
            }

            $account = $user->accounts()
                ->where('mt5_login', $validated['account_login'])
                ->first();

            if (!$account) {
                return response()->json(['error' => 'Cuenta no encontrada'], 404);
            }

            $query = $account->trades();

            if (isset($validated['position_ids'])) {
                $query->whereIn('position_id', $validated['position_ids']);
            } else {
                $query->where('exit_time', '>=', now()->subDays($validated['days_back'] ?? 7));
            }

            $tradesToRefresh = $query->get(['id', 'position_id', 'ticket', 'chart_data_path']);

            if ($tradesToRefresh->isEmpty()) {
                return response()->json(['status' => 'ok', 'message' => 'No hay trades para refrescar']);
            }

            // Borrar JSONs antiguos de R2
            foreach ($tradesToRefresh as $trade) {
                if ($trade->chart_data_path) {
                    $this->storage->delete($trade->chart_data_path);
                    Log::info("  🗑️ JSON borrado de R2: {$trade->chart_data_path}");
                }
            }

            return response()->json([
                'status' => 'ok',
                'trades_to_refresh' => $tradesToRefresh->map(fn ($t) => [
                    'position_id' => $t->position_id,
                    'ticket' => $t->ticket,
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN REFRESH CHARTS', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateChart(Request $request)
    {
        try {
            $validated = $request->validate([
                'sync_token' => 'required|string',
                'account_login' => 'required|string',
                'position_id' => 'required|string',
                'chart_data' => 'required|array',
            ]);

            $user = User::where('sync_token', $validated['sync_token'])->first();
            if (!$user) {
                return response()->json(['error' => 'Token inválido'], 401);
            }

            $account = $user->accounts()
                ->where('mt5_login', $validated['account_login'])
                ->first();

            if (!$account) {
                return response()->json(['error' => 'Cuenta no encontrada'], 404);
            }

            $trade = $account->trades()
                ->where('position_id', $validated['position_id'])
                ->first();

            if (!$trade) {
                return response()->json(['error' => 'Trade no encontrado'], 404);
            }

            $path = $this->storage->tradeChartPath($user->id, $trade->ticket);
            $this->storage->putJson($path, $validated['chart_data']);
            $trade->update(['chart_data_path' => $path]);

            Log::info("✅ Chart actualizado en R2: position_id={$validated['position_id']}");

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN UPDATE CHART', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
