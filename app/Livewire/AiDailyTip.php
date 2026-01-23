<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive; // Importante
use App\Models\Trade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiDailyTip extends Component
{
    // Recibimos las cuentas del padre en tiempo real
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

    public function generateTip()
    {
        $this->isLoading = true;

        // 1. QUERY (Igual que tenías)
        $query = Trade::whereHas('account', function ($q) {
            $q->where('user_id', Auth::id())
                ->where('status', '!=', 'burned');

            if (!empty($this->selectedAccounts) && !in_array('all', $this->selectedAccounts)) {
                $q->whereIn('id', $this->selectedAccounts);
            }
        });

        $trades = $query->orderBy('exit_time', 'desc')
            ->take(50)
            ->with('tradeAsset')
            ->get();

        if ($trades->count() < 5) {
            $this->tip = "Necesito al menos 5 operaciones recientes para analizarte.";
            $this->isLoading = false;
            return;
        }

        // 2. PREPARAR DATOS (Igual)
        $dataStr = $trades->map(function ($t) {
            $hour = Carbon::parse($t->exit_time)->hour;
            $session = ($hour >= 8 && $hour < 16) ? 'LONDRES' : (($hour >= 13 && $hour < 22) ? 'NY' : 'ASIA');
            $efficiency = ($t->mae_price && $t->mfe_price && $t->pnl > 0) ? "| Eff: OK" : "";
            return "{$t->exit_time->format('Y-m-d H:i')} | {$t->tradeAsset->name} | {$session} | " . strtoupper($t->direction) . " | PnL: {$t->pnl} $efficiency";
        })->join("\n");

        // 3. PROMPT CON JERARQUÍA DE ERRORES
        $prompt = "
            Actúa como un Psico-Trading Coach experto. Analiza estos trades buscando patrones destructivos.
            
            DATOS:
            $dataStr

            INSTRUCCIONES DE PRIORIDAD (Sigue este orden estricto):
            1. 🚨 PRIMERO busca SOBREOPERATIVA/TILT: Si ves múltiples operaciones (más de 3-4) en el mismo día o sesión con pérdidas, IGNORA la dirección (Long/Short) y ataca la cantidad. El problema es el volumen, no el setup.
            2. 🕒 SEGUNDO busca HORARIO: Si pierde siempre a la misma hora.
            3. 📉 TERCERO busca DIRECCIÓN: Solo si la conducta es disciplinada (pocos trades), mira si falla en Longs/Shorts.

            REGLAS DE RESPUESTA:
            - Dame UNA SOLA frase imperativa y dura.
            - Máximo 20 palabras.
            - Empieza con emoji.
            
            Ejemplos correctos:
            '🔥 Estás en racha destructiva: apaga el ordenador tras 2 pérdidas o quemarás la cuenta.' (Prioriza conducta)
            '🛑 Tu obsesión por operar la apertura de Nueva York te está costando cara; espera 30 minutos.' (Prioriza horario)
        ";

        try {
            $apiKey = env('GEMINI_API_KEY');

            // 👇 CAMBIO 2: withoutVerifying() para evitar errores de SSL local
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.7]
                ]);

            if ($response->successful()) {
                $content = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                $this->tip = $content;
                Cache::put($this->getCacheKey(), $content, Carbon::now()->endOfDay());
            } else {
                // 👇 CAMBIO 3: Mostrar el error real en pantalla para depurar
                $errorMsg = $response->json()['error']['message'] ?? 'Error desconocido de Google';
                $this->tip = "⚠️ Error API: " . $errorMsg;
                Log::error('Gemini Error Body: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error AI Tip: " . $e->getMessage());
            $this->tip = "Error de conexión: " . $e->getMessage();
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
