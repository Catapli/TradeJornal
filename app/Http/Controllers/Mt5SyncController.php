<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Mt5SyncController extends Controller
{
    //

    public function sync(Request $request)
    {
        $data = $request->validate([
            'sync_token' => 'required|string',
            'account_login' => 'required|string',
            'broker' => 'required|string',
            'balance' => 'numeric',
            'trades' => 'array',
        ]);

        Log::info("Iniciando Sync");

        // Buscar usuario por token
        // DESPUÉS (Token perpetuo)
        $user = User::where('sync_token', $request->sync_token)
            ->firstOrFail();

        // Buscar UNA account del usuario por login
        $account = $user->accounts()
            ->where('mt5_login', $data['account_login'])
            ->firstOrFail();

        $inserted = 0;
        foreach ($data['trades'] ?? [] as $tradeData) {
            $asset = TradeAsset::firstOrCreate(
                ['symbol' => $tradeData['trade_asset_symbol']],
                ['name' => $tradeData['trade_asset_symbol']]
            );

            // 1. GESTIÓN DE IMAGEN (Decodificar Base64)
            $screenshotPath = null;
            if (!empty($tradeData['screenshot'])) {
                try {
                    $imageContent = base64_decode($tradeData['screenshot']);
                    // Nombre: accounts/1/trades/123456_random.png
                    $fileName = 'accounts/' . $account->id . '/trades/' . $tradeData['ticket'] . '_' . Str::random(6) . '.png';

                    // Guardar en disco 'public'
                    Storage::disk('public')->put($fileName, $imageContent);
                    $screenshotPath = $fileName;
                } catch (\Exception $e) {
                    Log::error("Error guardando imagen trade {$tradeData['ticket']}: " . $e->getMessage());
                }
            }

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


            $trade = Trade::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'ticket' => $tradeData['ticket']
                ],
                [
                    'trade_asset_id' => $asset->id,
                    'direction' => $tradeData['direction'],
                    'entry_price' => $tradeData['entry_price'],
                    'exit_price' => $tradeData['exit_price'],
                    'size' => $tradeData['size'],
                    'pnl' => $tradeData['pnl'],
                    'duration_minutes' => $tradeData['duration_minutes'],
                    'entry_time' => $tradeData['entry_time'],
                    'exit_time' => $tradeData['exit_time'],
                    'notes' => $tradeData['notes'] ?? '',
                    // NUEVOS CAMPOS
                    'mae_price' => $tradeData['mae_price'] ?? null, // El precio más bajo/alto en contra
                    'mfe_price' => $tradeData['mfe_price'] ?? null, // El precio más alto/bajo a favor
                ]
            );

            // Actualizamos la foto solo si viene una nueva (para no borrar la existente si el script re-sincroniza)
            if ($screenshotPath) {
                $trade->screenshot = $screenshotPath;
                $trade->save();
            }

            if ($chartDataPath) {
                $trade->chart_data_path = $chartDataPath;
                $trade->save();
            }
            $inserted++;
        }

        return response()->json([
            'status' => 'ok',
            'inserted' => $inserted,
            'account' => $account->mt5_login,
            'balance' => $data['balance']
        ]);
    }
}
