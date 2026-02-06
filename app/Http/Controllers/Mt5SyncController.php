<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Notifications\NewTradeNotification;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Mt5SyncController extends Controller
{
    public function sync(Request $request)
    {
        $data = $request->validate([
            'sync_token' => 'required|string',
            'account_login' => 'required|string',
            'broker' => 'required|string',
            'balance' => 'numeric',
            'trades' => 'array',
        ]);

        Log::info("Iniciando Sync. Datos recibidos:", [
            'token_recibido' => $request->sync_token,
            'login_recibido' => $data['account_login'],
        ]);


        // 1. BUSCAR USUARIO
        $user = User::where('sync_token', $request->sync_token)->first();

        if (!$user) {
            Log::error("❌ Sync Fallido: Token inválido: " . $request->sync_token);
            return response()->json(['error' => 'Token de usuario inválido.'], 404);
        }

        // 2. BUSCAR CUENTA
        $account = $user->accounts()
            ->where('mt5_login', $data['account_login'])
            ->first();

        if (!$account) {
            Log::error("❌ Sync Fallido: Cuenta {$data['account_login']} no encontrada para usuario {$user->id}");
            return response()->json(['error' => "La cuenta {$data['account_login']} no existe o no pertenece a este usuario."], 404);
        }


        $inserted = 0;
        foreach ($data['trades'] ?? [] as $tradeData) {
            $asset = TradeAsset::firstOrCreate(
                ['symbol' => $tradeData['trade_asset_symbol']],
                ['name' => $tradeData['trade_asset_symbol']]
            );

            // LOGICA DE IMAGEN ELIMINADA AQUÍ --
            // El usuario subirá su propia imagen manualmente desde el modal más adelante.

            // 1. GUARDAR JSON (Datos para TradingView Web)
            $chartDataPath = null;
            if (!empty($tradeData['chart_data'])) {
                try {
                    // Guardamos el array como archivo .json
                    $jsonContent = json_encode($tradeData['chart_data']);
                    $fileNameJson = 'accounts/' . $account->id . '/charts/' . $tradeData['ticket'] . '.json';

                    Storage::disk('public')->put($fileNameJson, $jsonContent);
                    $chartDataPath = $fileNameJson;
                } catch (\Exception $e) {
                    Log::error("Error guardando JSON trade {$tradeData['ticket']}: " . $e->getMessage());
                }
            }

            // Evitamos división por cero
            $accountBalance = $account->initial_balance > 0 ? $account->initial_balance : 1;
            $percentage = ($tradeData['pnl'] / $accountBalance) * 100;


            // 2. GUARDADO
            // Usamos 'position_id' como clave única del trade completo
            $trade = Trade::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'position_id' => $tradeData['position_id']
                ],
                [
                    // Mantenemos el ticket original de entrada como referencia visual
                    'ticket' => $tradeData['ticket'],

                    'trade_asset_id' => $asset->id,
                    'direction' => $tradeData['direction'],
                    'entry_price' => $tradeData['entry_price'],
                    'exit_price' => $tradeData['exit_price'], // Precio promedio
                    'size' => $tradeData['size'], // Volumen total acumulado
                    'pnl' => $tradeData['pnl'], // PnL total acumulado
                    'pnl_percentage' => $percentage,

                    'duration_minutes' => $tradeData['duration_minutes'],
                    'entry_time' => $tradeData['entry_time'],
                    'exit_time' => $tradeData['exit_time'], // Se actualiza a la última salida

                    'mae_price' => $tradeData['mae_price'] ?? null,
                    'mfe_price' => $tradeData['mfe_price'] ?? null,

                    // GUARDAMOS EL HISTORIAL DE PARCIALES
                    'executions_data' => $tradeData['executions_data'] ?? [],

                    'chart_data_path' => $chartDataPath ?? null, // Solo si viene nuevo
                ]
            );
            // $trade = Trade::updateOrCreate(
            //     [
            //         'account_id' => $account->id,
            //         'ticket' => $tradeData['ticket']
            //     ],
            //     [
            //         'trade_asset_id' => $asset->id,
            //         'direction' => $tradeData['direction'],
            //         'entry_price' => $tradeData['entry_price'],
            //         'exit_price' => $tradeData['exit_price'],
            //         'size' => $tradeData['size'],
            //         'pnl' => $tradeData['pnl'],
            //         'duration_minutes' => $tradeData['duration_minutes'],
            //         'entry_time' => $tradeData['entry_time'],
            //         'exit_time' => $tradeData['exit_time'],
            //         'notes' => $tradeData['notes'] ?? '',
            //         "pnl_percentage" => $percentage,
            //         // NUEVOS CAMPOS
            //         'mae_price' => $tradeData['mae_price'] ?? null,
            //         'mfe_price' => $tradeData['mfe_price'] ?? null,
            //     ]
            // );

            // Solo guardamos el path del JSON, ya no hay screenshot automática
            if ($chartDataPath) {
                $trade->chart_data_path = $chartDataPath;
                $trade->save();
            }

            // =========================================================
            // 🔔 LÓGICA DE NOTIFICACIÓN BLINDADA (FIX POSTGRES)
            // =========================================================

            $exitTime = Carbon::parse($tradeData['exit_time']);
            $now = now();
            $diffInMinutes = $exitTime->diffInMinutes($now);

            // 3. COMPROBAR DUPLICADOS (FIX)
            $alreadyNotified = $user->notifications()
                ->where('type', 'App\Notifications\NewTradeNotification')
                ->latest()
                ->take(10)
                ->get()
                ->contains(function ($notification) use ($trade) {
                    return isset($notification->data['trade_id']) &&
                        $notification->data['trade_id'] == $trade->id;
                });

            // 4. CONDICIONES FINALES
            if (!$alreadyNotified && $diffInMinutes < 60) {
                $user->notify(new NewTradeNotification($trade));
                Log::info("🔔 Notificación enviada: Ticket {$trade->ticket}");
            }
            $inserted++;
        }

        $account->last_sync = now();
        $account->current_balance = $data['balance'];
        $account->save();

        return response()->json([
            'status' => 'ok',
            'inserted' => $inserted,
            'account' => $account->mt5_login,
            'balance' => $data['balance']
        ]);
    }

    // En Mt5SyncController.php

    public function resetSync(Request $request)
    {
        $request->validate([
            'sync_token' => 'required|string',
            'account_login' => 'required|string',
        ]);

        // 1. Validar Usuario
        $user = User::where('sync_token', $request->sync_token)->first();
        if (!$user) {
            return response()->json(['error' => 'Token inválido'], 404);
        }

        // 2. Validar Cuenta
        $account = $user->accounts()->where('mt5_login', $request->account_login)->first();
        if (!$account) {
            return response()->json(['error' => 'Cuenta no encontrada'], 404);
        }

        // 3. LIMPIEZA TOTAL (Hard Reset)
        // Borrar trades de la BD
        $account->trades()->delete();

        // Opcional: Borrar archivos JSON de charts si quieres ahorrar espacio
        Storage::disk('public')->deleteDirectory('accounts/' . $account->id . '/charts');

        Log::info("🧹 HARD RESET ejecutado para cuenta {$request->account_login} del usuario {$user->id}");

        return response()->json(['status' => 'ok', 'message' => 'Cuenta reseteada correctamente']);
    }
}
