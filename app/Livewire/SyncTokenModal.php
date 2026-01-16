<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class SyncTokenModal extends Component
{
    public bool $showModal = false;
    public string $syncToken = '';

    public function mount()
    {
        $user = Auth::user();
        if ($user && $user->sync_token) {
            $this->syncToken = $user->sync_token;
        } else {
            // ✅ FIX: No asignar el retorno, solo llamar
            $this->generateToken();
        }
    }

    public function generateToken()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            // Si no hay user, solo asignamos localmente para evitar error
            $this->syncToken = Str::random(32);
            return;
        }

        $token = Str::random(32);
        $user->update([
            'sync_token' => $token,
            'sync_token_expires_at' => null,
        ]);

        $this->syncToken = $token;
        // No retorna nada (void)
    }

    public function copyToClipboard()
    {
        $this->dispatch('copy-to-clipboard', token: $this->syncToken);
    }

    public function render()
    {
        return view('livewire.sync-token-modal');
    }
}
