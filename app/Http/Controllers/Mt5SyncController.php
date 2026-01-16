<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            Trade::updateOrCreate(
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
                ]
            );
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
