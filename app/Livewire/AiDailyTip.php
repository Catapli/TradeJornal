<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive; // Importante
use App\Models\Trade;
use App\WithAiLimits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $trades = Trade::select('trades.*')
            ->join('accounts', 'accounts.id', '=', 'trades.account_id')
            ->where('accounts.user_id', Auth::id())
            ->where('accounts.status', '!=', 'burned')
            ->when(
                !empty($this->selectedAccounts) && !in_array('all', $this->selectedAccounts),
                fn($q) => $q->whereIn('trades.account_id', $this->selectedAccounts)
            )
            ->orderBy('trades.exit_time', 'desc')
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

        try {
            $response = Http::when(app()->isLocal(), fn($http) => $http->withoutVerifying())
                ->retry(3, 3000, function (\Throwable $exception, \Illuminate\Http\Client\PendingRequest $request) {
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return in_array($exception->response->status(), [429, 503]);
                    }
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }, throw: false)
                ->withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'       => 'llama-3.3-70b-versatile', // Gratis, rápido y potente
                    'temperature' => 0.5,
                    'max_tokens'  => 350,
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->successful()) {
                $json         = $response->json();
                $finishReason = $json['choices'][0]['finish_reason'] ?? 'unknown';
                $content      = $json['choices'][0]['message']['content'] ?? null;

                if ($finishReason === 'length') {
                    Log::warning('AI Tip cortado por max_tokens en Groq.');
                    $this->tip = '⚠️ La respuesta fue cortada. Reintenta.';
                    $this->isLoading = false;
                    return;
                }

                if ($finishReason === 'stop' && $content) {
                    $this->tip = trim($content);
                    $this->consumeAiCredit();
                }
            } else {
                $statusCode = $response->status();
                $errorMsg   = $response->json()['error']['message'] ?? 'Error desconocido';

                Log::warning("AI Tip Groq error {$statusCode}: {$errorMsg}");

                $this->tip = match ($statusCode) {
                    429     => '⏳ Límite de peticiones alcanzado. Reintenta en unos segundos.',
                    503     => '🌐 El servicio está saturado. Reintenta más tarde.',
                    default => "⚠️ No se pudo generar el tip ({$statusCode}). Reintenta.",
                };
            }
        } catch (\Throwable $e) {
            Log::error("Error AI Tip Groq: " . $e->getMessage());
            $this->tip = '⚠️ Error inesperado al conectar con el servicio de IA.';
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
