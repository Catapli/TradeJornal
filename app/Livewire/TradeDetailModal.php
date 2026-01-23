<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TradeDetailModal extends Component
{
    // Ya no usamos $isOpen aquí, lo controla AlpineJS
    public $selectedTrade = null;

    // Navegación
    public $nextTradeId = null;
    public $prevTradeId = null;

    // Estado para la IA
    public $isAnalyzingTrade = false;

    /**
     * Este método es llamado por AlpineJS justo después de abrir el modal visualmente.
     */
    #[On('load-trade-data')]
    public function loadTradeData($tradeId)
    {
        // 1. Reseteamos para mostrar el esqueleto de carga si cambiamos de trade
        $this->selectedTrade = null;
        $this->prevTradeId = null;
        $this->nextTradeId = null;
        $this->isAnalyzingTrade = false;

        // 2. Cargamos los datos reales
        $this->loadTrade($tradeId);
    }

    public function loadTrade($tradeId)
    {
        // Cargamos relaciones necesarias
        $this->selectedTrade = Trade::with(['account', 'tradeAsset', 'mistakes'])->find($tradeId);

        if ($this->selectedTrade) {
            // Calcular navegación (Siguiente/Anterior)
            $this->calculateNavigation();

            // Disparar evento para que el Gráfico JS se pinte
            // El 'dispatch' ocurre después de que el HTML se actualice
            $this->dispatch(
                'trade-selected',
                path: $this->selectedTrade->chart_data_path,
                entry: $this->selectedTrade->entry_price,
                exit: $this->selectedTrade->exit_price,
                direction: $this->selectedTrade->direction
            );
        }
    }

    private function calculateNavigation()
    {
        $currentDate = $this->selectedTrade->exit_time->format('Y-m-d');

        // ANTERIOR: Mismo día, salida < actual
        $prev = Trade::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
            ->whereDate('exit_time', $currentDate)
            ->where(function ($q) {
                $q->where('exit_time', '<', $this->selectedTrade->exit_time)
                    ->orWhere(function ($q2) {
                        $q2->where('exit_time', $this->selectedTrade->exit_time)
                            ->where('id', '<', $this->selectedTrade->id);
                    });
            })
            ->orderBy('exit_time', 'desc')
            ->orderBy('id', 'desc')
            ->select('id') // Solo necesitamos el ID para optimizar
            ->first();

        // SIGUIENTE: Mismo día, salida > actual
        $next = Trade::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
            ->whereDate('exit_time', $currentDate)
            ->where(function ($q) {
                $q->where('exit_time', '>', $this->selectedTrade->exit_time)
                    ->orWhere(function ($q2) {
                        $q2->where('exit_time', $this->selectedTrade->exit_time)
                            ->where('id', '>', $this->selectedTrade->id);
                    });
            })
            ->orderBy('exit_time', 'asc')
            ->orderBy('id', 'asc')
            ->select('id')
            ->first();

        $this->prevTradeId = $prev?->id;
        $this->nextTradeId = $next?->id;
    }

    public function goToPrev()
    {
        if ($this->prevTradeId) $this->loadTrade($this->prevTradeId);
    }

    public function goToNext()
    {
        if ($this->nextTradeId) $this->loadTrade($this->nextTradeId);
    }

    // Lógica de IA
    public function analyzeIndividualTrade()
    {
        if (!$this->selectedTrade) return;
        $this->isAnalyzingTrade = true;
        $trade = $this->selectedTrade;

        $contextoDatos = "
            DATOS DEL TRADE:
            - Activo: {$trade->tradeAsset->name}
            - Tipo: " . strtoupper($trade->direction) . "
            - Entrada: {$trade->entry_price} | Salida: {$trade->exit_price}
            - Resultado: {$trade->pnl} (Lotes: {$trade->size})
            - Duración: {$trade->duration_minutes} min
            - Eficiencia: MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}
        ";

        $prompt = "
            Realiza una auditoría técnica y psicológica breve de este trade.
            DATOS: $contextoDatos
            FORMATO REQUERIDO:
            - **🎯 Calidad:** [Mala/Regular/Excelente] + Breve explicación técnica.
            - **🧠 Gestión:** Miedo o Codicia detectados (basado en cierre vs MFE).
            - **⚖️ Veredicto:** ¿Ejecución profesional?
            - **💡 Consejo:** Acción de mejora concreta.
        ";

        try {
            $parts = [['text' => $prompt]];

            if ($trade->screenshot && Storage::disk('public')->exists($trade->screenshot)) {
                $imageContent = Storage::disk('public')->get($trade->screenshot);
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => 'image/png',
                        'data' => base64_encode($imageContent)
                    ]
                ];
            }

            $apiKey = env('GEMINI_API_KEY');
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => ['temperature' => 0.4],
                ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                $trade->update(['ai_analysis' => $text]);
                $this->selectedTrade->ai_analysis = $text;
            }
        } catch (\Exception $e) {
            Log::error("Error AI Trade: " . $e->getMessage());
        }

        $this->isAnalyzingTrade = false;
    }

    public function render()
    {
        return view('livewire.trade-detail-modal');
    }
}
