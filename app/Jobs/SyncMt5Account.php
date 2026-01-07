<?php

namespace App\Jobs;

use App\Events\AccountSynced;
use App\Models\Account;
use App\Services\Mt5Gateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMt5Account implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    public function __construct(public Account $account) {}

    public function handle(Mt5Gateway $gateway)
    {
        try {
            $gateway->syncAccount($this->account);

            // ÉXITO: Actualizamos todo al final
            $this->account->update([
                'sync_error' => false,
                'last_sync' => now(),
            ]);
            Log::info('✅ Job: Sync finalizado correctamente');
        } catch (Throwable $e) {
            Log::error('❌ Job: Error detectado', ['error' => $e->getMessage()]);

            // ERROR: Forzamos la actualización de los campos de error
            $this->account->fill([
                'sync_error' => true,
                'sync_error_message' => $e->getMessage(),
            ])->save();

            // Forzamos un toque al timestamp para que Livewire lo vea
            $this->account->touch();
        }
    }
}
