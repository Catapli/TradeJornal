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
    public function syncAccount($account, $forceAll = false)
    {
        $password = decrypt($account->mt5_password);
        $dateStart = $forceAll ? null : $account->last_sync;
        // Determinamos si necesitamos pedir la fecha inicial
        $needFirstDate = is_null($account->funded_date);

        $response = Http::timeout(60)->post('http://185.116.236.222:5000/sync-account', [
            'login' => $account->mt5_login,
            'password' => $password,
            'server' => $account->mt5_server,
            'date_start' => $dateStart,
            'need_first_date' => $needFirstDate // ← Enviamos el flag
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Sync API failed");
        }

        $data = $response->json();

        // 👇 PASAMOS el flag forceAll a importTrades
        $this->importTrades($account, $data['trades'], $forceAll);

        return $data;
    }

    private function importTrades($account, $trades, $isFullSync = false)
    {
        $positions = collect($trades)->groupBy('position_id')->filter(fn($deals) => count($deals) >= 2);

        // Lista para guardar los tickets que SI existen en MT5
        $validTickets = [];

        foreach ($positions as $pos_id => $deals) {
            $sortedDeals = $deals->sortBy('time');
            $entry = $sortedDeals->first();
            $exit = $sortedDeals->last();
            $totalPnL = $deals->sum('net_pnl');

            $trade = Trade::updateOrCreate(
                ['ticket' => $entry['ticket'], 'account_id' => $account->id],
                [
                    'trade_asset_id' => TradeAsset::firstOrCreate(['symbol' => $entry['symbol']])->id,
                    'direction' => $entry['type'] === 'BUY' ? 'long' : 'short',
                    'entry_price' => $entry['price'],
                    'size' => $entry['volume'],
                    'pnl' => $totalPnL,
                    'entry_time' => $entry['time'],
                    'exit_time' => $exit['time'],
                    'duration_minutes' => (int) round(Carbon::parse($entry['time'])->diffInMinutes(Carbon::parse($exit['time']))),
                ]
            );

            $validTickets[] = $trade->ticket;
        }

        // 🔥 LA SOLUCIÓN: Si es Sync Total, borramos los que NO están en la lista de la API
        if ($isFullSync) {
            Trade::where('account_id', $account->id)
                ->whereNotIn('ticket', $validTickets)
                ->delete();

            Log::info("🧹 Limpieza completada. Borrados trades que no están en MT5.");
        }
    }


    public function getSnapshot(Account $account)
    {
        $password = decrypt($account->mt5_password);

        // Llamamos al mismo endpoint de Python pero con el flag de velocidad
        $response = Http::timeout(10)->post('http://185.116.236.222:5000/sync-account', [
            'login' => $account->mt5_login,
            'password' => $password,
            'server' => $account->mt5_server,
            'snapshot_only' => true // <--- ESTO ES IMPORTANTE (Requiere el cambio en Python previo)
        ]);

        Log::info("Snapshot API Response for account {$account->id}: " . $response->body());

        if ($response->failed()) {
            Log::error("Fallo Snapshot API para cuenta {$account->id}");
            throw new \RuntimeException("Snapshot API failed for account {$account->id}");
        }

        return $response->json();
    }
}
