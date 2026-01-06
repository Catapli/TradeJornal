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

class SyncMt5Account implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct(public Account $account) {}

    public function handle(Mt5Gateway $gateway)
    {
        Log::info('🔄 Job iniciado', ['account_id' => $this->account->id]);

        $success = $gateway->syncAccount($this->account);

        if ($success) {
            // 👇 IMPORTANTE: Forzamos la actualización del timestamp 'updated_at'
            // Esto le avisa a Livewire que hubo un cambio.
            $this->account->touch();

            Log::info('✅ Sync completado y DB actualizada');
        } else {
            Log::error('❌ Job falló');
        }
    }
}
