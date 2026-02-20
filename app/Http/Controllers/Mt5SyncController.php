<?php

namespace App\Http\Controllers;

use App\Events\AccountSyncCompleted;
use Carbon\Carbon;
use App\Notifications\NewTradeNotification;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Mt5SyncController extends Controller
{
    public function sync(Request $request)
    {
        // ========== LOGGING DETALLADO ==========
        Log::info('========================================');
        Log::info('🔵 MT5 SYNC INICIADO');
        Log::info('IP: ' . $request->ip());
        Log::info('Content-Length: ' . $request->header('Content-Length'));
        Log::info('Payload size: ' . strlen($request->getContent()) . ' bytes');
        Log::info('========================================');

        try {
            // Validación
            $data = $request->validate([
                'sync_token' => 'required|string',
                'account_login' => 'required|string',
                'broker' => 'required|string',
                'balance' => 'numeric',
                'trades' => 'array',
            ]);

            Log::info("✅ Validación OK");
            Log::info("Token recibido: " . substr($data['sync_token'], 0, 10) . '...');
            Log::info("Account login: " . $data['account_login']);
            Log::info("Trades count: " . count($data['trades'] ?? []));
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("❌ ERROR DE VALIDACIÓN");
            Log::error(json_encode($e->errors()));
            return response()->json(['error' => 'Datos inválidos', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("❌ ERROR EN VALIDACIÓN");
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            return response()->json(['error' => 'Error procesando request'], 500);
        }

        try {
            // 1. BUSCAR USUARIO
            Log::info("🔍 Buscando usuario por token...");
            $user = User::where('sync_token', $data['sync_token'])->first();

            if (!$user) {
                Log::error("❌ Token inválido: " . $data['sync_token']);
                return response()->json(['error' => 'Token de usuario inválido.'], 404);
            }

            Log::info("✅ Usuario encontrado: ID={$user->id}, Email={$user->email}");
        } catch (\Exception $e) {
            Log::error("❌ ERROR BUSCANDO USUARIO");
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            return response()->json(['error' => 'Error buscando usuario'], 500);
        }

        try {
            // 2. BUSCAR CUENTA
            Log::info("🔍 Buscando cuenta mt5_login={$data['account_login']}...");
            $account = $user->accounts()
                ->where('mt5_login', $data['account_login'])
                ->first();

            if (!$account) {
                Log::error("❌ Cuenta no encontrada: {$data['account_login']} para usuario {$user->id}");
                return response()->json(['error' => "La cuenta {$data['account_login']} no existe o no pertenece a este usuario."], 404);
            }

            Log::info("✅ Cuenta encontrada: ID={$account->id}");
        } catch (\Exception $e) {
            Log::error("❌ ERROR BUSCANDO CUENTA");
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            return response()->json(['error' => 'Error buscando cuenta'], 500);
        }

        $inserted = 0;
        $errors = [];

        try {
            Log::info("📦 Procesando " . count($data['trades'] ?? []) . " trades...");

            foreach ($data['trades'] ?? [] as $index => $tradeData) {
                try {
                    Log::info("  → Trade {$index}: position_id={$tradeData['position_id']}, ticket={$tradeData['ticket']}");

                    // BUSCAR/CREAR ASSET
                    $asset = TradeAsset::firstOrCreate(
                        ['symbol' => $tradeData['trade_asset_symbol']],
                        ['name' => $tradeData['trade_asset_symbol']]
                    );

                    // GUARDAR JSON (chart_data)
                    $chartDataPath = null;
                    if (!empty($tradeData['chart_data'])) {
                        try {
                            $jsonContent = json_encode($tradeData['chart_data']);
                            $fileNameJson = 'accounts/' . $account->id . '/charts/' . $tradeData['ticket'] . '.json';
                            Storage::disk('public')->put($fileNameJson, $jsonContent);
                            $chartDataPath = $fileNameJson;
                            Log::info("    ✓ Chart JSON guardado: {$fileNameJson}");
                        } catch (\Exception $e) {
                            Log::error("    ❌ Error guardando JSON: " . $e->getMessage());
                        }
                    }

                    // Calcular porcentaje
                    $accountBalance = $account->initial_balance > 0 ? $account->initial_balance : 1;
                    $percentage = ($tradeData['pnl'] / $accountBalance) * 100;

                    // GUARDAR TRADE
                    $trade = Trade::updateOrCreate(
                        [
                            'account_id' => $account->id,
                            'position_id' => $tradeData['position_id']
                        ],
                        [
                            'ticket' => $tradeData['ticket'],
                            'trade_asset_id' => $asset->id,
                            'direction' => $tradeData['direction'],
                            'entry_price' => $tradeData['entry_price'],
                            'exit_price' => $tradeData['exit_price'],
                            'size' => $tradeData['size'],
                            'pnl' => $tradeData['pnl'],
                            'pnl_percentage' => $percentage,
                            'duration_minutes' => $tradeData['duration_minutes'],
                            'entry_time' => $tradeData['entry_time'],
                            'exit_time' => $tradeData['exit_time'],
                            'mae_price' => $tradeData['mae_price'] ?? null,
                            'mfe_price' => $tradeData['mfe_price'] ?? null,
                            'executions_data' => $tradeData['executions_data'] ?? [],
                            'chart_data_path' => $chartDataPath ?? null,
                            'pips_traveled' => $tradeData['pips_traveled'] ?? null,
                        ]
                    );

                    Log::info("    ✅ Trade guardado: ID={$trade->id}");

                    // NOTIFICACIÓN
                    try {
                        $exitTime = Carbon::parse($tradeData['exit_time']);
                        $now = now();
                        $diffInMinutes = $exitTime->diffInMinutes($now);

                        $alreadyNotified = $user->notifications()
                            ->where('type', 'App\\Notifications\\NewTradeNotification')
                            ->latest()
                            ->take(10)
                            ->get()
                            ->contains(function ($notification) use ($trade) {
                                return isset($notification->data['trade_id']) &&
                                    $notification->data['trade_id'] == $trade->id;
                            });

                        if (!$alreadyNotified && $diffInMinutes < 60) {
                            $user->notify(new NewTradeNotification($trade));
                            Log::info("    🔔 Notificación enviada");
                        }
                    } catch (\Exception $e) {
                        Log::warning("    ⚠️ Error en notificación (no crítico): " . $e->getMessage());
                    }

                    $inserted++;
                } catch (\Exception $e) {
                    Log::error("    ❌ ERROR procesando trade {$index}");
                    Log::error("       Mensaje: " . $e->getMessage());
                    Log::error("       Línea: " . $e->getLine());
                    Log::error("       Archivo: " . $e->getFile());
                    $errors[] = [
                        'index' => $index,
                        'position_id' => $tradeData['position_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("❌ ERROR EN BUCLE DE TRADES");
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            Log::error("Trace: " . $e->getTraceAsString());
            return response()->json(['error' => 'Error procesando trades', 'message' => $e->getMessage()], 500);
        }

        try {
            // Actualizar cuenta
            $account->last_sync = now();
            $account->current_balance = $data['balance'];
            $account->save();

            Log::info("✅ Cuenta actualizada: balance={$data['balance']}");
        } catch (\Exception $e) {
            Log::error("❌ ERROR ACTUALIZANDO CUENTA");
            Log::error("Mensaje: " . $e->getMessage());
        }

        Log::info("========================================");
        Log::info("✅ SYNC COMPLETADO");
        Log::info("   Trades procesados: {$inserted}");
        Log::info("   Errores: " . count($errors));
        Log::info("========================================");

        return response()->json([
            'status' => 'ok',
            'inserted' => $inserted,
            'errors' => $errors,
            'account' => $account->mt5_login,
            'balance' => $data['balance']
        ]);
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
                Log::error('❌ Token inválido en reset');
                return response()->json(['error' => 'Token inválido'], 404);
            }

            $account = $user->accounts()->where('mt5_login', $request->account_login)->first();
            if (!$account) {
                Log::error('❌ Cuenta no encontrada en reset');
                return response()->json(['error' => 'Cuenta no encontrada'], 404);
            }

            $deletedCount = $account->trades()->count();
            $account->trades()->delete();
            Storage::disk('public')->deleteDirectory('accounts/' . $account->id . '/charts');

            Log::info("✅ RESET completado: {$deletedCount} trades eliminados");

            return response()->json(['status' => 'ok', 'message' => 'Cuenta reseteada correctamente']);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN RESET');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Línea: ' . $e->getLine());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Mt5SyncController.php
    public function refreshCharts(Request $request)
    {
        Log::info('🔄 REFRESH CHARTS solicitado');

        try {
            $validated = $request->validate([
                'sync_token' => 'required|string',
                'account_login' => 'required|string',
                'days_back' => 'nullable|integer|min:1|max:365', // Default: 7 días
                'position_ids' => 'nullable|array', // Opcional: refrescar trades específicos
            ]);

            // Buscar usuario y cuenta
            $user = User::where('sync_token', $validated['sync_token'])->first();
            if (!$user) {
                return response()->json(['error' => 'Token de usuario inválido.'], 404);
            }

            $account = $user->accounts()
                ->where('mt5_login', $validated['account_login'])
                ->first();

            if (!$account) {
                return response()->json(['error' => 'Cuenta no encontrada'], 404);
            }

            // Construir query de trades a refrescar
            $query = $account->trades();

            if (isset($validated['position_ids'])) {
                // Refrescar trades específicos
                $query->whereIn('position_id', $validated['position_ids']);
            } else {
                // Refrescar trades de los últimos N días
                $daysBack = $validated['days_back'] ?? 7;
                $query->where('exit_time', '>=', now()->subDays($daysBack));
            }

            $tradesToRefresh = $query->get(['id', 'position_id', 'ticket', 'chart_data_path']);

            if ($tradesToRefresh->isEmpty()) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'No hay trades para refrescar'
                ]);
            }

            Log::info("📊 Trades a refrescar: {$tradesToRefresh->count()}");

            // Borrar JSONs antiguos
            foreach ($tradesToRefresh as $trade) {
                if ($trade->chart_data_path) {
                    Storage::disk('public')->delete($trade->chart_data_path);
                    Log::info("  🗑️ JSON borrado: {$trade->chart_data_path}");
                }
            }

            // Retornar lista de trades para que el agente envíe nuevos charts
            return response()->json([
                'status' => 'ok',
                'trades_to_refresh' => $tradesToRefresh->map(fn($t) => [
                    'position_id' => $t->position_id,
                    'ticket' => $t->ticket,
                ])->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN REFRESH CHARTS', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
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

            // Buscar trade
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

            // Guardar nuevo JSON
            $jsonContent = json_encode($validated['chart_data']);
            $fileNameJson = 'accounts/' . $account->id . '/charts/' . $trade->ticket . '.json';
            Storage::disk('public')->put($fileNameJson, $jsonContent);

            // Actualizar path en BD
            $trade->chart_data_path = $fileNameJson;
            $trade->save();

            Log::info("✅ Chart actualizado: position_id={$validated['position_id']}");

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('❌ ERROR EN UPDATE CHART', [
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
