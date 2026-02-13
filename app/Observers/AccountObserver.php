<?php

namespace App\Observers;

use App\Models\Account;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountObserver
{
    public function created(Account $account): void
    {
        $this->invalidateUserCache($account);
    }

    public function updated(Account $account): void
    {
        // Solo invalidar si cambiaron campos relevantes
        if ($account->wasChanged(['name', 'status', 'broker_name', 'login'])) {
            $this->invalidateUserCache($account);
        }
    }

    public function deleted(Account $account): void
    {
        $this->invalidateUserCache($account);
    }

    private function invalidateUserCache(Account $account): void
    {
        try {
            $userId = $account->user_id;

            // Limpiar caché de cuentas disponibles
            Cache::forget("accounts:user:{$userId}");

            Log::info("🔄 Caché de cuentas invalidada para user {$userId} tras cambio en Account {$account->id}");
        } catch (\Exception $e) {
            Log::error("Error al invalidar caché de cuentas: " . $e->getMessage());
        }
    }
}
