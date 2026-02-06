<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Trade;
use App\WithAiLimits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class TradeDetailModal extends Component
{
    use WithFileUploads; // <--- IMPORTANTE: Usar el Trait
    use WithAiLimits; // <--- 2. Usar el Trait
    // Ya no usamos $isOpen aquí, lo controla AlpineJS
    public $selectedTrade = null;

    // Navegación
    public $nextTradeId = null;
    public $prevTradeId = null;

    // Estado para la IA
    public $isAnalyzingTrade = false;

    // NUEVO: Propiedad para editar la nota
    public $notes = '';
    public $isSavingNotes = false;

    // NUEVO: Aquí guardamos la "playlist" (lista de IDs de la tabla actual)
    public $contextTradeIds = [];

    // NUEVO: Propiedad para la subida de imagen temporal
    public $uploadedScreenshot;

    // NUEVO: Variable primitiva para controlar la vista de la imagen
    public $currentScreenshot = null;


    /**
     * Este método es llamado por AlpineJS justo después de abrir el modal visualmente.
     */
    #[On('load-trade-data')]
    public function loadTradeData($tradeId, $contextIds = [])
    {
        // 1. Reseteamos para mostrar el esqueleto de carga si cambiamos de trade
        $this->selectedTrade = null;
        $this->prevTradeId = null;
        $this->nextTradeId = null;
        $this->isAnalyzingTrade = false;
        $this->notes = ''; // Resetear notas
        $this->uploadedScreenshot = null; // Resetear input de archivo

        // 2. Guardamos el contexto (la lista de IDs de la tabla donde hiciste clic)
        // Si viene vacío, dejamos el array vacío.
        $this->contextTradeIds = $contextIds;

        // 2. Cargamos los datos reales
        $this->loadTrade($tradeId);

        // AÑADIR ESTA LÍNEA AL FINAL:
        // Avisamos al navegador: "Ya tengo los datos, renderiza el gráfico"
        $this->dispatch('trade-data-loaded');
    }

    public function loadTrade($tradeId)
    {
        // Cargamos relaciones necesarias
        $this->selectedTrade = Trade::with(['account', 'tradeAsset', 'mistakes'])->find($tradeId);
        if ($this->selectedTrade) {
            // Cargar la nota existente
            $this->notes = $this->selectedTrade->notes;

            // Calcular navegación (AQUÍ ESTÁ LA MAGIA)
            // Usará el contexto si existe, o la lógica SQL si no.
            $this->calculateNavigation();

            $this->currentScreenshot = $this->selectedTrade->screenshot;


            // Disparar evento para Gráfico JS
            $this->dispatch(
                'trade-selected',
                path: $this->selectedTrade->chart_data_path,
                entry: $this->selectedTrade->entry_price,
                exit: $this->selectedTrade->exit_price,
                direction: $this->selectedTrade->direction
            );
        }
    }

    /**
     * NUEVO: Se ejecuta automáticamente cuando 'uploadedScreenshot' cambia
     * (es decir, cuando el usuario suelta el archivo en el input).
     */
    public function updatedUploadedScreenshot()
    {
        $this->validate([
            'uploadedScreenshot' => 'image|max:10240', // 10MB
        ]);

        if ($this->selectedTrade) {
            // 1. Guardar archivo físico
            $path = $this->uploadedScreenshot->store('screenshots', 'public');

            // 2. Limpieza de archivo anterior
            if ($this->selectedTrade->screenshot && Storage::disk('public')->exists($this->selectedTrade->screenshot)) {
                Storage::disk('public')->delete($this->selectedTrade->screenshot);
            }

            // 3. Actualizar Base de Datos (Esto ya lo hacías bien)
            $this->selectedTrade->update([
                'screenshot' => $path
            ]);

            // ---------------------------------------------------------
            // EL CAMBIO CLAVE:
            // En lugar de refresh(), recargamos el objeto COMPLETO desde cero.
            // Esto obliga a PHP a traer el dato fresco y las relaciones.
            // ---------------------------------------------------------
            $this->selectedTrade = Trade::with(['account', 'tradeAsset', 'mistakes'])
                ->find($this->selectedTrade->id);
            // EL FIX: Actualizamos la variable primitiva manualmente
            $this->currentScreenshot = $path;

            // 4. Limpiar el input temporal
            $this->reset('uploadedScreenshot');

            // 5. Opcional: Forzar un evento de navegador para asegurar que Alpine se entere
            $this->dispatch('screenshot-updated');
        }
    }



    // NUEVO: Función para guardar notas
    public function saveNotes()
    {
        if ($this->selectedTrade) {
            $this->isSavingNotes = true;

            $this->selectedTrade->update([
                'notes' => $this->notes
            ]);

            // Despachar evento para actualizar dashboard si es necesario
            $this->dispatch('trade-updated');

            // Simular un pequeño delay para feedback visual
            usleep(200000);
            $this->isSavingNotes = false;
        }
    }

    /**
     * Analiza el JSON del gráfico para ver qué hizo el precio DESPUÉS de la salida.
     */
    private function analyzePostTradeContext($trade)
    {
        // Validación de archivo
        if (!$trade->chart_data_path || !Storage::disk('public')->exists($trade->chart_data_path)) {
            return "No hay datos de mercado disponibles posteriores al cierre para analizar.";
        }

        $jsonContent = Storage::disk('public')->get($trade->chart_data_path);
        $chartData = json_decode($jsonContent, true);

        // Intentar obtener velas de 5m, fallback a 1m
        $candles = $chartData['timeframes']['5m'] ?? ($chartData['timeframes']['1m'] ?? []);

        if (empty($candles)) return "Datos de velas insuficientes.";

        $exitTimestamp = Carbon::parse($trade->exit_time)->timestamp;
        $entryPrice = (float) $trade->entry_price;
        $isLong = in_array(strtoupper($trade->direction), ['LONG', 'BUY']);

        // Configuración de análisis futuro
        $lookForwardCandles = 30; // Mirar las siguientes 30 velas
        $maxFavorableAfterExit = 0;
        $foundExit = false;
        $candlesChecked = 0;

        foreach ($candles as $candle) {
            // Ignorar velas anteriores al cierre
            if ($candle['time'] < $exitTimestamp) continue;

            $foundExit = true;
            $candlesChecked++;

            // Calcular cuánto se movió a favor después de salir
            if ($isLong) {
                $distUp = $candle['high'] - $entryPrice;
                if ($distUp > $maxFavorableAfterExit) $maxFavorableAfterExit = $distUp;
            } else {
                $distDown = $entryPrice - $candle['low'];
                if ($distDown > $maxFavorableAfterExit) $maxFavorableAfterExit = $distDown;
            }

            if ($candlesChecked >= $lookForwardCandles) break;
        }

        if (!$foundExit) return "No hay datos posteriores al cierre.";

        // --- LÓGICA DE INTERPRETACIÓN PARA LA IA ---

        // Calculamos el MFE que el usuario SÍ capturó (o el máximo que llegó a ver antes de cerrar)
        $originalMfe = abs($trade->mfe_price - $trade->entry_price);

        // Umbral de tolerancia: Si se movió 50% más de lo que ya habías visto, es significativo
        $threshold = $originalMfe > 0 ? ($originalMfe * 1.5) : ($entryPrice * 0.0005); // Fallback si MFE es 0 (5 pips aprox)

        $pointsMoved = number_format($maxFavorableAfterExit, 5);

        if ($maxFavorableAfterExit > $threshold) {
            return "CRÍTICO: El mercado se movió fuertemente A FAVOR ({$pointsMoved} pts) después de sacarte. " .
                "Esto indica 'DIRECCIÓN CORRECTA, STOP LOSS INCORRECTO'. Hubo un barrido de liquidez.";
        } else {
            return "El mercado NO hizo movimientos significativos a favor después del cierre. El análisis de dirección probablemente era incorrecto o el momentum se perdió.";
        }
    }

    public function resetModal()
    {
        $this->reset([
            'selectedTrade',
            'prevTradeId',
            'nextTradeId',
            'notes',
            'isAnalyzingTrade',
            'contextTradeIds'
        ]);
    }

    private function calculateNavigation()
    {
        $currentId = $this->selectedTrade->id;

        // --- OPCIÓN A: NAVEGACIÓN POR CONTEXTO (PRIORIDAD) ---
        // Si tenemos una lista de IDs cargada, nos movemos por ella.
        if (!empty($this->contextTradeIds)) {
            // Buscamos en qué posición está el trade actual dentro de la lista
            $currentIndex = array_search($currentId, $this->contextTradeIds);

            if ($currentIndex !== false) {
                // CAMBIO AQUÍ: Invertimos la lógica.

                // Botón "Anterior" (Flecha Izquierda) -> Nos lleva al pasado (más abajo en la lista, índice mayor)
                $this->prevTradeId = $this->contextTradeIds[$currentIndex + 1] ?? null;

                // Botón "Siguiente" (Flecha Derecha) -> Nos lleva al futuro (más arriba en la lista, índice menor)
                $this->nextTradeId = $this->contextTradeIds[$currentIndex - 1] ?? null;

                return;
            }
        }


        // --- OPCIÓN B: NAVEGACIÓN FALLBACK (TU LÓGICA ANTIGUA SQL) ---
        // Si no hay contexto (ej: recarga de página o acceso directo), usamos tu lógica original

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
            ->select('id')
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

    public function analyzeIndividualTrade()
    {
        // 1. Validaciones
        if (!$this->selectedTrade) return;

        // ----------------------------------------------------
        // 1. VALIDACIÓN DE LÍMITE (NUEVO)
        // ----------------------------------------------------
        if (!$this->checkAiLimit()) {
            $this->isAnalyzingTrade = false; // Apagar spinner
            return; // Detener ejecución
        }

        $this->isAnalyzingTrade = true;
        $trade = $this->selectedTrade;

        // 1. OBTENER "VISIÓN DE FUTURO" (Lo que pasó después)
        $futureAnalysis = $this->analyzePostTradeContext($trade);


        // 2. Preparar los DATOS (Traducimos también las etiquetas: Activo, Tipo, etc.)
        // Usamos __('ai.labels.x') para que la data también esté en el idioma correcto
        $contextoDatos = "
        " . __('ai.labels.asset') . ": {$trade->tradeAsset->name}
        " . __('ai.labels.type') . ": " . strtoupper($trade->direction) . "
        " . __('ai.labels.entry') . ": {$trade->entry_price} | " . __('ai.labels.exit') . ": {$trade->exit_price}
        " . __('ai.labels.result') . ": {$trade->pnl} (Lots: {$trade->size})
        " . __('ai.labels.duration') . ": {$trade->duration_minutes} min
        " . __('ai.labels.efficiency') . ": MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}
        " . __('ai.labels.future') . ": {$futureAnalysis}
    ";

        // 3. Obtener el PROMPT traducido e inyectarle el contexto
        // Laravel sustituirá el marcador ':context' que pusimos en el archivo php por la variable $contextoDatos
        $prompt = __('ai.audit_prompt', ['context' => $contextoDatos]);
        // 3. Preparar el Payload para Gemini
        $parts = [
            ['text' => $prompt]
        ];

        // 4. Si hay imagen, la codificamos en Base64 y la adjuntamos
        if ($trade->screenshot && \Illuminate\Support\Facades\Storage::disk('public')->exists($trade->screenshot)) {

            // Obtenemos el contenido crudo del archivo
            $imageContent = \Illuminate\Support\Facades\Storage::disk('public')->get($trade->screenshot);
            $base64Image = base64_encode($imageContent);

            // Añadimos la parte de imagen al payload
            $parts[] = [
                'inline_data' => [
                    'mime_type' => 'image/png', // Asumimos PNG por el script Python
                    'data' => $base64Image
                ]
            ];
        }



        try {
            $apiKey = env('GEMINI_API_KEY');

            // Usamos gemini-3-flash-preview porque es Multimodal (acepta imágenes)
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['parts' => $parts]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4, // 0.4 es ideal para análisis técnico (bajo = más lógico/estricto)
                    ],
                ]);

            if ($response->successful()) {
                $analysisText = $response->json()['candidates'][0]['content']['parts'][0]['text'];

                // Guardamos en BD para no gastar API la próxima vez
                $trade->update(['ai_analysis' => $analysisText]);

                // ----------------------------------------------------
                // 2. CONSUMIR CRÉDITO (NUEVO)
                // Solo restamos si la IA respondió bien.
                // ----------------------------------------------------
                $this->consumeAiCredit();

                // Actualizamos la propiedad local para que se vea al instante
                $this->selectedTrade->ai_analysis = $analysisText;
            } else {
                // Si falla, mostramos error pero no guardamos en BD
                $this->dispatch('notify', 'Error en Gemini: ' . $response->body()); // O un toast simple
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
