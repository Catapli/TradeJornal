<?php

declare(strict_types=1);

namespace App\Livewire;

use App\LogActions;
use App\Models\Account;
use App\Support\Demo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Estado de la sincronización con MetaTrader.
 *
 * La sincronización es la funcionalidad estrella y hasta ahora era una caja
 * negra: si el `.exe` dejaba de empujar, el usuario no tenía forma de saber si
 * el problema era suyo o nuestro. Aquí se ve el token, cuándo llegó el último
 * dato de cada cuenta y el error exacto si lo hubo.
 */
class SyncSettings extends Component
{
    use LogActions;

    public bool $tokenVisible = false;

    #[Computed]
    public function accounts()
    {
        return Account::where('user_id', Auth::id())
            ->where('is_sample', false)
            ->orderByDesc('sync')
            ->orderBy('name')
            ->get(['id', 'name', 'mt5_login', 'mt5_server', 'broker_name', 'sync', 'last_sync', 'sync_error', 'sync_error_message']);
    }

    public function token(): string
    {
        return (string) Auth::user()->sync_token;
    }

    public function maskedToken(): string
    {
        $token = $this->token();

        return mb_substr($token, 0, 6) . str_repeat('•', 20) . mb_substr($token, -4);
    }

    public function toggleToken(): void
    {
        $this->tokenVisible = !$this->tokenVisible;
    }

    public function regenerate(): void
    {
        if (Demo::active()) {
            return;
        }

        Auth::user()->regenerateSyncToken();

        $this->tokenVisible = false;
        $this->insertLog(action: 'Token de sincronización regenerado', form: 'SyncSettings', type: 'warning');

        $this->dispatch('show-alert', ['type' => 'success', 'message' => __('sync.token_regenerated')]);
    }

    public function render()
    {
        return view('livewire.sync-settings');
    }
}
