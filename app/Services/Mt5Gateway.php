<?php
// app/Services/Mt5Gateway.php

namespace App\Services;

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\json;

class Mt5Gateway
{
    public function syncAccount($account)
    {
        Log::info($account);

        $password = decrypt($account->mt5_password);
        $response = Http::timeout(30)->post('http://185.116.236.222:5000/sync-account', [
            'login' => $account->mt5_login,
            'password' => $password,
            'server' => $account->mt5_server,
            // Opcional: desde última sincronización
            'date_start' => $account->last_sync
        ]);

        if ($response->failed()) {
            Log::error('MT5 Sync failed', [
                'account_id' => $account->id,
                'error' => $response->json('error', 'Unknown error')
            ]);
            return false;
        }

        $data = $response->json();
        Log::info('MT5 Sync data received', [
            'account_id' => $account->id,
            'data' => $data
        ]);

        Log::info('Trades', [
            'trades' => $data['trades']
        ]);

        // Importar trades a DB
        $this->importTrades($account, $data['trades']);

        // Actualizar cuenta
        $account->update([
            'current_balance' => $data['balance'],
            'equity' => $data['equity'],
            'margin_free' => $data['margin_free'],
            'last_sync' => now()
        ]);

        Log::info('✅ Sync OK', [
            'account_id' => $account->id,
            'last_sync' => $account->fresh()->last_sync // ← Verifica
        ]);

        return true;
    }

    private function importTrades($account, $trades)
    {

        $positions = collect($trades)
            ->groupBy('position_id')
            ->filter(fn($deals) => count($deals) >= 2);  // Min 2 deals

        // $netPNL = 0;

        foreach ($positions as $pos_id => $deals) {
            $sortedDeals = $deals->sortBy('time');
            $entry = $sortedDeals->first();
            $exit = $sortedDeals->last();

            // Suma TOTAL PnL de TODOS los deals (cierres parciales + final)
            $totalPnL = $deals->sum('net_pnl');  // O 'pnl' si net_pnl no es el correcto

            $asset = TradeAsset::firstOrCreate([
                'symbol' => $entry['symbol']
            ]);

            $data = [
                'account_id' => $account->id,
                'trade_asset_id' => $asset->id,
                'strategy_id' => $entry['magic'] ?: null,
                'ticket' => $entry['ticket'],
                'direction' => $entry['type'] === 'BUY' ? 'long' : 'short',
                'entry_price' => $entry['price'],
                'size' => $entry['volume'],
                'pnl' => $totalPnL,
                'duration_minutes' => (int) round(
                    Carbon::parse($entry['time'])->diffInMinutes(Carbon::parse($exit['time']))
                ),
                'entry_time' => $entry['time'],
                'exit_time' => $exit['time'],
                'notes' => $exit['comment']
            ];
            Log::info('Importando trade', $data);

            Trade::create([
                'account_id' => $account->id,
                'trade_asset_id' => $asset->id,
                'strategy_id' => $entry['magic'] ?: null,
                'ticket' => $entry['ticket'],
                'direction' => $entry['type'] === 'BUY' ? 'long' : 'short',
                'entry_price' => $entry['price'],
                'size' => $entry['volume'],
                'pnl' => $totalPnL,
                'duration_minutes' => (int) round(
                    Carbon::parse($entry['time'])->diffInMinutes(Carbon::parse($exit['time']))
                ),
                'entry_time' => $entry['time'],
                'exit_time' => $exit['time'],
                'notes' => $exit['comment']
            ]);
        }

        Log::info("Importadas " . $positions->count() . " operaciones");
    }
}
