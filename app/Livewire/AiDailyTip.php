<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive; // Importante
use App\Models\Trade;
use App\Services\AiService;
use App\WithAiLimits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AiDailyTip extends Component
{
    // Recibimos las cuentas del padre en tiempo real
    use WithAiLimits; // <--- 2. Usar el Trait
    #[Reactive]
    public $selectedAccounts = [];

    public $tip = null;
    public $isLoading = false;

    public function mount($selectedAccounts = [])
    {
        $this->selectedAccounts = $selectedAccounts;
        $this->loadTipFromCache();
    }

    private function getCacheKey()
    {
        // Hacemos una copia para no alterar el orden visual en el componente
        $accounts = $this->selectedAccounts;

        if (empty($accounts) || in_array('all', $accounts)) {
            $accountsKey = 'all';
        } else {
            // sort() ordena el array $accounts in-situ y devuelve true/false
            sort($accounts);
            $accountsKey = implode('-', $accounts);
        }

        return 'ai_daily_tip_' . Auth::id() . '_' . $accountsKey . '_' . Carbon::today()->format('Y-m-d');
    }

    public function loadTipFromCache()
    {
        $this->tip = Cache::get($this->getCacheKey());
    }

    // Cada vez que el padre cambia las cuentas, Livewire llama a esto (gracias a #[Reactive])
    // Lo usamos para intentar cargar un tip si ya existía para esa combinación
    public function updatedSelectedAccounts()
    {
        $this->loadTipFromCache();
    }

    public function generateTip(): void
    {
        $this->isLoading = true;

        if (!$this->checkAiLimit()) {
            $this->isLoading = false;
            $this->dispatch('notify', __('labels.limit_ai_reached'));
            return;
        }

        $trades = Trade::forUserActiveAccounts()
            ->when(
                !empty($this->selectedAccounts) && !in_array('all', $this->selectedAccounts),
                fn($q) => $q->whereIn('account_id', $this->selectedAccounts)
            )
            ->orderBy('exit_time', 'desc')
            ->take(20)
            ->with('tradeAsset')
            ->get();

        if ($trades->count() < 5) {
            $this->tip = __('labels.need_min_5_trades');
            $this->isLoading = false;
            return;
        }

        $dataStr = $trades->map(function ($t) {
            $hour = $t->exit_time->hour;
            $session = ($hour >= 8 && $hour < 16) ? 'LON' : (($hour >= 13 && $hour < 22) ? 'NY' : 'ASIA');
            return "{$t->exit_time->format('d/m H:i')}|{$t->tradeAsset->name}|{$session}|" . strtoupper($t->direction) . "|PnL:{$t->pnl}";
        })->join("\n");

        $prompt = __('ai.daily_tip', ['datos' => $dataStr]);

        $result = app(AiService::class)->complete($prompt, temperature: 0.5, maxTokens: 350);

        if ($result->ok) {
            $this->tip = $result->content;
            // Persistir el tip del día para que loadTipFromCache() lo recupere
            Cache::put($this->getCacheKey(), $this->tip, now()->endOfDay());
            $this->consumeAiCredit();
        } else {
            $this->tip = $result->userMessage();
        }

        $this->isLoading = false;
    }

    public function closeTip()
    {
        $this->tip = null;
        Cache::forget($this->getCacheKey());
    }

    public function render()
    {
        return view('livewire.ai-daily-tip');
    }
}
