<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Account;
use App\Models\Trade;
use App\Models\TradingObjective;
use App\Models\TradingPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Guía de puesta en marcha: crear cuenta → meter operaciones → fijar objetivos.
 *
 * Los tres pasos **no se guardan**: se deducen del estado real de los datos. Así
 * no hay forma de que la guía diga «pendiente» sobre algo que el usuario ya hizo,
 * ni al revés, y funciona igual si llegó por importación, por el `.exe` o a mano.
 *
 * Desaparece sola al completarse, y el usuario puede cerrarla antes.
 */
class OnboardingChecklist extends Component
{
    /** @var array<string, bool> */
    public array $done = [];

    /**
     * Ya no se puede ocultar la guía desde la pantalla.
     *
     * El botón se retiró el 2026-09-18: el bloque entero se pliega, así que un
     * cierre definitivo —que además no se podía deshacer— sobraba. La marca se
     * sigue leyendo para **no resucitarle la guía** a quien la ocultó cuando el
     * botón existía; lo que ya no hay es forma nueva de escribirla.
     */
    public bool $dismissed = false;

    public function mount(): void
    {
        $this->dismissed = Auth::user()?->onboarding_dismissed_at !== null;
        $this->refreshSteps();
    }

    public function completed(): int
    {
        return count(array_filter($this->done));
    }

    public function shouldShow(): bool
    {
        return !$this->dismissed && $this->completed() < count($this->done);
    }

    private function refreshSteps(): void
    {
        $userId = Auth::id();

        // Las cuentas y operaciones de ejemplo no cuentan: el objetivo de la guía
        // es que el usuario tenga *sus* datos dentro.
        $realAccounts = Account::where('user_id', $userId)->where('is_sample', false);

        $hasAccount = (clone $realAccounts)->exists();
        $accountIds = (clone $realAccounts)->pluck('id');

        $this->done = [
            'account' => $hasAccount,
            'trades' => $accountIds->isNotEmpty() && Trade::whereIn('account_id', $accountIds)->exists(),
            'goals' => TradingObjective::where('user_id', $userId)->exists()
                || TradingPlan::whereIn('account_id', $accountIds)->exists(),
        ];
    }

    public function render()
    {
        return view('livewire.onboarding-checklist');
    }
}
