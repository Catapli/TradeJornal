<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Trade;
use App\Services\Mt5Gateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Necesario para limitar el texto del error
use Throwable;

class SyncMt5Account implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 400;
    public $tries = 2;

    public function __construct(public Account $account) {}

    public function handle(Mt5Gateway $gateway)
    {
        try {
            $apiData = $gateway->syncAccount($this->account);

            $localPnL = Trade::where('account_id', $this->account->id)->sum('pnl');
            $apiBalance = (float)$apiData['balance'];
            $calculatedBalance = (float)$this->account->initial_balance + (float)$localPnL;

            Log::info("Balance Inicial: {$this->account->initial_balance}");
            Log::info("Balance Local: {$localPnL}");
            Log::info("Balance Calculado: {$calculatedBalance}");
            Log::info("Balance API: {$apiBalance}");

            // 1. Auditoría de descuadre (lo que ya teníamos)
            if (abs($calculatedBalance - $apiBalance) > 0.01) {
                Log::warning("⚠️ Descuadre detectado en cuenta {$this->account->id}. Calculado: $calculatedBalance, API: $apiBalance. Re-sincronizando todo.");
                $apiData = $gateway->syncAccount($this->account, true);
                $apiBalance = (float)$apiData['balance'];
            }

            // 2. LÓGICA DE CUENTA QUEMADA
            $newStatus = $this->account->status;
            if ($apiBalance <= 0) {
                $newStatus = 'burned';
                Log::error("💀 Cuenta {$this->account->id} quemada. Balance: $apiBalance");
            }

            // 3. Guardar todo
            $this->account->forceFill([
                'current_balance' => $apiBalance,
                'status' => $newStatus, // Actualizamos el status
                'sync_error' => false,
                'sync_error_message' => null,
                'funded_date' => $apiData['first_trade_date'],
                'last_sync' => now(),
            ])->save();
        } catch (Throwable $e) {
            Log::error('❌ Job Error: ' . $e->getMessage());
            $this->account->forceFill([
                'sync_error' => true,
                'sync_error_message' => Str::limit($e->getMessage(), 250),
            ])->save();
            $this->account->touch();
        }
    }
}
