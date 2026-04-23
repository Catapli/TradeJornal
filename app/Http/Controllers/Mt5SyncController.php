<?php

namespace App\Http\Controllers;

use App\Services\StorageService;
use Carbon\Carbon;
use App\Notifications\NewTradeNotification;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Mt5SyncController extends Controller
{
    public function __construct(private StorageService $storage) {}

    public function sync(Request $request)
    {
        Log::info('========================================');
        Log::info('🔵 MT5 SYNC INICIADO');
        Log::info('IP: ' . $request->ip());
        Log::info('Payload size: ' . strlen($request->getContent()) . ' bytes');
        Log::info('========================================');

        try {
            $data = $request->validate([
                'sync_token'   => 'required|string',
                'account_login' => 'required|string',
                'broker'       => 'required|string',
                'balance'      => 'numeric',
                'trades'       => 'array',
            ]);

            Log::info("✅ Validación OK — Trades: " . count($data['trades'] ?? []));
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("❌ ERROR DE VALIDACIÓN", $e->errors());
            return response()->json(['error' => 'Datos inválidos', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("❌ ERROR EN VALIDACIÓN — {$e->getMessage()}");
            return response()->json(['error' => 'Error procesando request'], 500);
        }

        // 1. BUSCAR USUARIO
        $user = User::where('sync_token', $data['sync_token'])->first();
        if (!$user) {
            Log::error("❌ Token inválido");
            return response()->json(['error' => 'Token de usuario inválido.'], 404);
        }

        if (!$user->subscribed('default')) {
            Log::warning("⚠️ Usuario sin suscripción activa: ID={$user->id}");
            return response()->json(['error' => 'Usuario sin suscripción activa.'], 403);
        }

        Log::info("✅ Usuario encontrado: ID={$user->id}");

        // 2. BUSCAR CUENTA
        $account = $user->accounts()
            ->where('mt5_login', $data['account_login'])
            ->first();

        if (!$account) {
            Log::error("❌ Cuenta no encontrada: {$data['account_login']}");
            return response()->json(['error' => "La cuenta {$data['account_login']} no existe o no pertenece a este usuario."], 404);
        }
        Log::info("✅ Cuenta encontrada: ID={$account->id}");

        // 3. PROCESAR TRADES
        $inserted = 0;
        $errors   = [];

        foreach ($data['trades'] ?? [] as $index => $tradeData) {
            try {
                Log::info("  → Trade {$index}: position_id={$tradeData['position_id']}");

                $asset = TradeAsset::firstOrCreate(
                    ['symbol' => $tradeData['trade_asset_symbol']],
                    ['name'   => $tradeData['trade_asset_symbol']]
                );

                // GUARDAR chart.json en R2
                $chartDataPath = null;
                if (!empty($tradeData['chart_data'])) {
                    try {
                        $path          = $this->storage->tradeChartPath($user->id, $tradeData['ticket']);
                        $chartDataPath = $this->storage->putJson($path, $tradeData['chart_data']);
                        Log::info("    ✓ Chart JSON guardado en R2: {$chartDataPath}");
                    } catch (\Exception $e) {
                        Log::error("    ❌ Error guardando JSON en R2: {$e->getMessage()}");
                    }
                }

                $accountBalance = $account->initial_balance > 0 ? $account->initial_balance : 1;
                $percentage     = ($tradeData['pnl'] / $accountBalance) * 100;

                $trade = Trade::updateOrCreate(
                    [
                        'account_id'  => $account->id,
                        'position_id' => $tradeData['position_id'],
                    ],
                    [
                        'ticket'          => $tradeData['ticket'],
                        'trade_asset_id'  => $asset->id,
                        'direction'       => $tradeData['direction'],
                        'entry_price'     => $tradeData['entry_price'],
                        'exit_price'      => $tradeData['exit_price'],
                        'size'            => $tradeData['size'],
                        'pnl'             => $tradeData['pnl'],
                        'pnl_percentage'  => $percentage,
                        'duration_minutes' => $tradeData['duration_minutes'],
                        'entry_time'      => $tradeData['entry_time'],
                        'exit_time'       => $tradeData['exit_time'],
                        'mae_price'       => $tradeData['mae_price'] ?? null,
                        'mfe_price'       => $tradeData['mfe_price'] ?? null,
                        'executions_data' => $tradeData['executions_data'] ?? [],
                        'chart_data_path' => $chartDataPath,
                        'pips_traveled'   => $tradeData['pips_traveled'] ?? null,
                    ]
                );

                Log::info("    ✅ Trade guardado: ID={$trade->id}");

                // NOTIFICACIÓN (solo trades recientes)
                try {
                    $diffInMinutes = Carbon::parse($tradeData['exit_time'])->diffInMinutes(now());

                    if ($diffInMinutes < 60) {
                        $alreadyNotified = $user->notifications()
                            ->where('type', NewTradeNotification::class)
                            ->latest()
                            ->take(10)
                            ->get()
                            ->contains(fn($n) => ($n->data['trade_id'] ?? null) == $trade->id);

                        if (!$alreadyNotified) {
                            $user->notify(new NewTradeNotification($trade));
                            Log::info("    🔔 Notificación enviada");
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("    ⚠️ Error en notificación (no crítico): {$e->getMessage()}");
                }

                $inserted++;
            } catch (\Exception $e) {
                Log::error("    ❌ ERROR trade {$index} — {$e->getMessage()} (línea {$e->getLine()})");
                $errors[] = [
                    'index'       => $index,
                    'position_id' => $tradeData['position_id'] ?? 'unknown',
                    'error'       => $e->getMessage(),
                ];
            }
        }

        // 4. ACTUALIZAR CUENTA
        $account->update([
            'last_sync'           => now(),
            'current_balance'     => $data['balance'],
            'sync_error'          => count($errors) > 0,
            'sync_error_message'  => count($errors) > 0
                ? implode("\n", array_map(fn($e) => "Trade {$e['position_id']}: {$e['error']}", $errors))
                : null,
        ]);

        Log::info("========================================");
        Log::info("✅ SYNC COMPLETADO — Insertados: {$inserted} | Errores: " . count($errors));
        Log::info("========================================");

        return response()->json([
            'status'   => 'ok',
            'inserted' => $inserted,
            'errors'   => $errors,
            'account'  => $account->mt5_login,
            'balance'  => $data['balance'],
        ]);
    }

    public function resetSync(Request $request)
    {
        Log::info('🧨 RESET SYNC solicitado');

        try {
            $request->validate([
                'sync_token'    => 'required|string',
                'account_login' => 'required|string',
            ]);

            $user = User::where('sync_token', $request->sync_token)->first();
            if (!$user) {
                return response()->json(['error' => 'Token inválido'], 404);
            }

            if (!$user->subscribed('default')) {
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

            // Borrar archivos de R2 trade por trade
            $trades       = $account->trades()->get(['ticket']);
            $deletedCount = $trades->count();

            foreach ($trades as $trade) {
                $this->storage->deleteTradeFiles($user->id, $trade->ticket);
            }

            $account->trades()->delete();

            Log::info("✅ RESET completado: {$deletedCount} trades eliminados");

            return response()->json(['status' => 'ok', 'message' => 'Cuenta reseteada correctamente']);
        } catch (\Exception $e) {
            Log::error("❌ ERROR EN RESET — {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function refreshCharts(Request $request)
    {
        Log::info('🔄 REFRESH CHARTS solicitado');

        try {
            $validated = $request->validate([
                'sync_token'   => 'required|string',
                'account_login' => 'required|string',
                'days_back'    => 'nullable|integer|min:1|max:365',
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
                'status'            => 'ok',
                'trades_to_refresh' => $tradesToRefresh->map(fn($t) => [
                    'position_id' => $t->position_id,
                    'ticket'      => $t->ticket,
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
                'sync_token'   => 'required|string',
                'account_login' => 'required|string',
                'position_id'  => 'required|string',
                'chart_data'   => 'required|array',
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
