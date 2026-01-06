<?php

namespace App\Listeners;

use App\Events\AccountSynced;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;  // ← IMPORT CRÍTICO

class SyncAccountListener
{
    public function handle(AccountSynced $event): void
    {
        Log::info('🔥 Listener → Livewire dispatch', ['account_id' => $event->account->id]);

        // 🔥 LIVEWIRE DISPATCH (llega al browser)
        Livewire::getInstance()->getRoot()->dispatch('sync-finished', [
            'accountId' => $event->account->id
        ]);
    }
}
