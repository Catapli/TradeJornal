<?php

namespace App\Livewire;

use App\LogActions;
use App\Models\JournalEntry;
use App\Models\Trade;
use App\Models\TradingObjective;
use App\WithAiLimits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;

class JournalPage extends Component
{

    use WithAiLimits; // <--- 2. Usar el Trait
    use LogActions;

    #[Url(keep: true)]
    public $date;
    public $entry; // El modelo JournalEntry

    // --- CAMPOS PRE-MARKET ---
    public $pre_market_mood;
    public $pre_market_notes;
    public $daily_objectives = []; // Array: [['done' => false, 'text' => '']]

    // --- CAMPOS SESIÓN (Compartidos con Dashboard) ---
    public $content; // Notas generales

    // --- DATOS SOLO LECTURA ---
    public $dayTrades = [];
    public $dayTradesIds = [];
    public $dayPnL = 0;
    public $mistakesSummary = [];

    // Propiedad para controlar qué mes estamos viendo en el mini-calendario
    public $calendarRef;

    public function mount(?string $date = null): void
    {
        try {
            $this->date = $date ?? Carbon::today()->format('Y-m-d');
            $this->calendarRef = Carbon::parse($this->date);
            $this->loadData();
        } catch (\Exception $e) {
            $this->logError($e, 'mount', 'JournalPage', 'Error al montar el componente Journal');
            // Estado mínimo seguro para que Blade no explote
            $this->dayTrades      = collect();
            $this->daily_objectives = [];
            $this->mistakesSummary = collect();
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_loading_journal'));
        }
    }

    // ✅ DESPUÉS
    public function loadData(): void
    {
        try {
            // 1. Buscar entrada existente
            $this->entry = JournalEntry::where('user_id', Auth::id())
                ->where('date', $this->date)
                ->first();

            if ($this->entry) {
                // CASO A: El día YA existe
                $this->pre_market_mood  = $this->entry->pre_market_mood;
                $this->pre_market_notes = $this->entry->pre_market_notes;
                $this->content        = $this->entry->content;

                $dbObjs = $this->entry->daily_objectives;
                $this->daily_objectives = is_string($dbObjs)
                    ? json_decode($dbObjs, true)
                    : ($dbObjs ?? []);
            } else {
                // CASO B: Día nuevo — cargamos plantilla
                $this->entry = new JournalEntry([
                    'user_id' => Auth::id(),
                    'date'    => $this->date,
                ]);
                $this->pre_market_mood  = null;
                $this->pre_market_notes = '';
                $this->content        = '';

                $templates = TradingObjective::where('user_id', Auth::id())
                    ->where('is_active', true)
                    ->get();

                $this->daily_objectives = $templates->count() > 0
                    ? $templates->map(fn($rule) => ['text' => $rule->text, 'done' => false])->toArray()
                    : [['text' => __('labels.set_tour_rules'), 'done' => false]];
            }

            // 3. Cargar Trades — MEJORA 18 incluida: eager load tradeAsset
            $trades = Trade::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                ->whereDate('exit_time', $this->date)
                ->with(['mistakes', 'tradeAsset'])  // ← tradeAsset añadido (Mejora 18)
                ->orderBy('exit_time', 'asc')
                ->get();

            $this->dayTrades    = $trades;
            $this->dayPnL       = $trades->sum('pnl');
            $this->dayTradesIds  = $trades->pluck('id')->toArray(); // ← (Mejora 16: evita query duplicada)

            // 4. Resumen de errores
            $this->mistakesSummary = $trades
                ->pluck('mistakes')
                ->flatten()
                ->groupBy('name')
                ->map->count();
        } catch (\Exception $e) {
            $this->logError($e, 'loadData', 'JournalPage', "Error cargando datos del día {$this->date}");
            // Retorno seguro: colecciones vacías para que Blade no falle
            $this->entry           = new JournalEntry();
            $this->dayTrades       = collect();
            $this->dayTradesIds     = [];
            $this->dayPnL          = 0;
            $this->daily_objectives = [];
            $this->mistakesSummary = collect();
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_loading_journal'));
        }
    }


    /**
     * Abre el detalle de un trade con contexto de paginación
     * Los IDs de contexto son los trades de la página actual visible
     */
    public function openTradeDetail(int $tradeId): void
    {
        try {
            // MEJORA 16: Usamos $dayTradeIds ya calculado en loadData() → 0 queries extra
            $this->dispatch(
                'open-trade-detail',
                tradeId: $tradeId,
                tradeIds: $this->dayTradesIds
            );
        } catch (\Exception $e) {
            $this->logError($e, 'openTradeDetail', 'JournalPage', "Error abriendo trade {$tradeId}");
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_opening_trade'));
        }
    }




    // ✅ DESPUÉS
    public function removeObjective(int $index): void
    {
        try {
            unset($this->daily_objectives[$index]);
            $this->daily_objectives = array_values($this->daily_objectives);
        } catch (\Exception $e) {
            $this->logError($e, 'removeObjective', 'JournalPage', "Error eliminando objetivo índice {$index}");
        }
    }


    // --- NAVEGACIÓN ---
    public function prevDay()
    {
        return redirect()->route('journal', ['date' => Carbon::parse($this->date)->subDay()->format('Y-m-d')]);
    }
    public function nextDay()
    {
        return redirect()->route('journal', ['date' => Carbon::parse($this->date)->addDay()->format('Y-m-d')]);
    }

    public $showRulesModal = false; // Controlar el modal
    public $newRuleText = ''; // Input para nueva regla
    public $userRules = []; // Lista para mostrar en el modal

    // ✅ DESPUÉS
    public function openRulesManager(): void
    {
        try {
            $this->userRules      = TradingObjective::where('user_id', Auth::id())->get();
            $this->showRulesModal = true;
        } catch (\Exception $e) {
            $this->logError($e, 'openRulesManager', 'JournalPage', 'Error cargando el gestor de reglas');
            $this->userRules = collect();
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_loading_rules'));
        }
    }


    // ✅ DESPUÉS
    public function addMasterRule(string $text): void
    {
        try {
            $text = trim($text);

            if (empty($text) || mb_strlen($text) > 200) return;

            TradingObjective::create([
                'user_id'   => Auth::id(),
                'text'      => $text,
                'is_active' => true,
            ]);

            $this->openRulesManager();

            if (! $this->entry->exists) {
                $this->loadData();
            }
        } catch (\Exception $e) {
            $this->logError($e, 'addMasterRule', 'JournalPage', 'Error creando nueva regla maestra');
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_saving_rules'));
        }
    }



    // Borrar/Desactivar regla maestra
    // ✅ DESPUÉS
    public function deleteMasterRule(int $id): void
    {
        try {
            TradingObjective::where('id', $id)
                ->where('user_id', Auth::id())
                ->delete();

            $this->openRulesManager();
        } catch (\Exception $e) {
            $this->logError($e, 'deleteMasterRule', 'JournalPage', "Error eliminando regla {$id}");
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_deleting_rule'));
        }
    }


    // Toggle Activo/Inactivo
    // ✅ DESPUÉS
    public function toggleMasterRule(int $id): void
    {
        try {
            $rule = TradingObjective::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (! $rule) return;

            $rule->is_active = ! $rule->is_active;
            $rule->save();

            $this->openRulesManager();
        } catch (\Exception $e) {
            $this->logError($e, 'toggleMasterRule', 'JournalPage', "Error toggling regla {$id}");
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_toggling_rule'));
        }
    }


    public function calculateDiscipline()
    {
        $score = 0;

        // ---------------------------------------------------
        // 1. PREPARACIÓN (Max 2 Puntos)
        // ---------------------------------------------------
        // Si rellenó el Mood Pre-market: +1 punto
        if (!empty($this->pre_market_mood)) {
            $score += 1;
        }
        // Si definió al menos un objetivo: +1 punto
        if (!empty($this->daily_objectives) && count($this->daily_objectives) > 0) {
            $score += 1;
        }

        // ---------------------------------------------------
        // 2. CUMPLIMIENTO DE OBJETIVOS (Max 2 Puntos)
        // ---------------------------------------------------
        $totalObjs = count($this->daily_objectives);
        if ($totalObjs > 0) {
            // Contamos cuántos tienen 'done' => true
            $completed = collect($this->daily_objectives)->where('done', true)->count();

            // Regla de 3: Si Total es 2 ptos, ¿cuánto es mi porcentaje?
            // Ejemplo: 3 objetivos, 2 cumplidos (66%) -> 2 * 0.66 = 1.32 ptos
            $score += ($completed / $totalObjs) * 2;
        } else {
            // Si no definió objetivos, no le damos estos puntos (incentivo a planificar)
            // Opcional: podrías darle los puntos gratis si prefieres, pero mejor ser estricto.
        }

        // ---------------------------------------------------
        // 3. EJECUCIÓN TÉCNICA (Max 4 Puntos) - MISTAKE TRACKER
        // ---------------------------------------------------
        // Empezamos con la puntuación máxima de ejecución
        $executionScore = 4;

        // Recuperamos los trades del día con sus errores
        // Nota: Asegúrate de que $this->dayTrades tenga cargada la relación 'mistakes'
        $totalPenalty = 0;

        foreach ($this->dayTrades as $trade) {
            foreach ($trade->mistakes as $mistake) {
                // Si tienes columna 'weight' en la tabla mistakes, úsala. Si no, resta 1 por defecto.
                // $weight = $mistake->weight ?? 1; 

                // Por simplificar ahora: Cada error resta 1 punto de disciplina
                $totalPenalty += 1;
            }
        }

        // Restamos la penalización (No puede bajar de 0)
        $executionScore = max(0, $executionScore - $totalPenalty);
        $score += $executionScore;

        // ---------------------------------------------------
        // 4. REFLEXIÓN POST-MERCADO (Max 2 Puntos)
        // ---------------------------------------------------
        $wordCount = str_word_count($this->content ?? '');

        if ($wordCount > 50) {
            $score += 2; // Reflexión profunda (>50 palabras)
        } elseif ($wordCount > 5) {
            $score += 1; // Reflexión básica
        }
        // Si está vacío o es muy corto, 0 puntos.

        // ---------------------------------------------------
        // RESULTADO FINAL
        // ---------------------------------------------------
        return round($score, 1); // Devolvemos con 1 decimal (Ej: 8.5)
    }

    // --- GUARDADO ---
    // ✅ DESPUÉS — save() completo con try-catch + DB::transaction
    public function save(): void
    {
        try {
            $calculatedScore = $this->calculateDiscipline();

            DB::transaction(function () use ($calculatedScore) {
                $this->entry = JournalEntry::updateOrCreate(
                    // Clave de búsqueda — identifica el registro único
                    [
                        'user_id' => Auth::id(),
                        'date'    => $this->date,
                    ],
                    // Valores a crear/actualizar
                    [
                        'pre_market_mood'  => $this->pre_market_mood,
                        'pre_market_notes' => $this->pre_market_notes,
                        'daily_objectives' => $this->daily_objectives,
                        'content'          => $this->content,
                        'discipline_score' => $calculatedScore,
                    ]
                );
            });

            $this->insertLog(
                action: 'save',
                form: 'JournalPage',
                description: "Journal guardado para la fecha {$this->date}",
                type: 'info'
            );

            $this->dispatch('show-alert', type: 'success', message: __('labels.journal_updated'));
        } catch (\Exception $e) {
            $this->logError($e, 'save', 'JournalPage', "Error guardando journal del día {$this->date}");
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_saving_journal'));
        }
    }


    public function showAlert($type, $message)
    {
        $this->dispatch('show-alert', [
            'type' => $type,
            'message' => $message
        ]);
    }


    // ✅ DESPUÉS
    public function prevMonth(): void
    {
        try {
            $this->calendarRef->subMonth();
        } catch (\Exception $e) {
            $this->logError($e, 'prevMonth', 'JournalPage', 'Error navegando mes anterior');
        }
    }

    public function nextMonth(): void
    {
        try {
            $this->calendarRef->addMonth();
        } catch (\Exception $e) {
            $this->logError($e, 'nextMonth', 'JournalPage', 'Error navegando mes siguiente');
        }
    }


    // Propiedad Computada del Calendario (Optimizada)
    // ✅ DESPUÉS
    public function getMiniCalendarProperty(): array
    {
        try {
            $start = $this->calendarRef->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $end   = $this->calendarRef->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

            $entries = JournalEntry::where('user_id', Auth::id())
                ->whereBetween('date', [$start, $end])
                ->whereNotNull('content')
                ->pluck('date')
                ->toArray();

            // Normalizar fechas a string Y-m-d para comparación segura
            $entries = array_map(fn($d) => substr($d, 0, 10), $entries);

            $days = [];
            $curr = $start->copy();

            while ($curr <= $end) {
                $dateStr = $curr->format('Y-m-d');
                $days[]  = [
                    'date'            => $dateStr,
                    'day'             => $curr->day,
                    'is_current_month' => $curr->month === $this->calendarRef->month,
                    'is_today'        => $curr->isToday(),
                    'is_selected'     => $dateStr === $this->date,
                    'has_entry'       => in_array($dateStr, $entries),
                ];
                $curr->addDay();
            }

            return $days;
        } catch (\Exception $e) {
            $this->logError($e, 'getMiniCalendar', 'JournalPage', 'Error generando mini-calendario');
            return []; // Blade itera sobre array vacío — sin crash
        }
    }


    // MÉTODO NUEVO: Cambiar día sin recarga
    // ✅ DESPUÉS
    public function selectDate(string $date): void
    {
        try {
            $this->date        = $date;
            $this->calendarRef = Carbon::parse($date);
            $this->loadData();
        } catch (\Exception $e) {
            $this->logError($e, 'selectDate', 'JournalPage', "Error seleccionando fecha {$date}");
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_loading_journal'));
        }
    }



    // Añade esto en JournalPage.php
    // Asegúrate de importar: use Illuminate\Support\Facades\Http; use Illuminate\Support\Facades\Log;

    public function generateAiDraft()
    {
        // 1. Validar si hay datos mínimos
        if (count($this->dayTrades) === 0 && empty($this->pre_market_mood)) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.empty_operations_ai_draft')]);
            return;
        }

        // ----------------------------------------------------
        // 2. VALIDACIÓN DE LÍMITE (NUEVO)
        // ----------------------------------------------------
        if (!$this->checkAiLimit()) {
            $this->dispatch('show-alert', __('labels.limit_ai_reached'));
            return; // Detener ejecución
        }

        // 2. Preparar Contexto de Datos
        // Usamos las etiquetas traducidas de ai.labels
        $moodLabel = __('ai.labels.mood');
        $pnlLabel = __('ai.labels.total_result');
        $opsLabel = __('ai.labels.total_ops');

        // El valor del mood lo traducimos si es estático, o lo dejamos tal cual
        $moodValue = $this->pre_market_mood ? ucfirst($this->pre_market_mood) : __('labels.no_registered');
        $pnlValue = number_format($this->dayPnL, 2);
        $totalTrades = count($this->dayTrades);

        $dataContext = "
        - $moodLabel: $moodValue
        - $pnlLabel: $pnlValue $
        - $opsLabel: $totalTrades
    ";

        // 3. Preparar Resumen de Trades (Trade Breakdown)
        $tradesContext = collect($this->dayTrades)->map(function ($t) {
            // Aseguramos que 'Mistakes' y 'Clean Execution' estén traducidos
            $mistakesList = $t->mistakes->pluck('name')->join(', ');

            $errorStr = $mistakesList
                ? "(" . __('ai.labels.mistakes') . ": $mistakesList)"
                : __('ai.labels.clean_execution');

            // Profit/Loss traducido
            $resultLabel = $t->pnl >= 0 ? __('ai.labels.profit') : __('ai.labels.loss');

            return "- {$t->exit_time->format('H:i')}: {$t->tradeAsset->name} ({$t->direction}) | $resultLabel {$t->pnl}$ | $errorStr";
        })->join("\n");

        // 4. Generar el Prompt Final
        // Inyectamos las dos partes: datos generales (:context) y lista de trades (:trades)
        $prompt = __('ai.draft_prompt', [
            'context' => $dataContext,
            'trades' => $tradesContext
        ]);

        // 4. Llamada a Gemini
        try {
            $apiKey = env('GEMINI_API_KEY');
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.7] // Un poco más creativo para redactar
                ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'];

                // LIMPIEZA AGRESIVA
                $text = str_replace(['```html', '```'], '', $text);

                // Eliminar saltos de línea que la IA mete a veces entre etiquetas para "que se vea bonito"
                // Esto junta todo el HTML en una línea para que el editor lo renderice bien
                $text = preg_replace('/>\s+</', '><', $text);

                // Si ya había texto, lo añadimos al final, si no, reemplazamos
                if (!empty($this->content)) {
                    $this->content .= "\n\n---\n**🤖 Borrador IA:**\n" . $text;
                } else {
                    $this->content = $text;
                }

                // ----------------------------------------------------
                // 2. CONSUMIR CRÉDITO (NUEVO)
                // Solo restamos si la IA respondió bien.
                // ----------------------------------------------------
                $this->consumeAiCredit();

                // ENVÍO SIMPLE:
                $this->dispatch('editor-content-updated', $this->content);
                $this->dispatch('show-alert', ['type' => 'success', 'message' => __('labels.draft_generated_ok')]);
            }
        } catch (\Exception $e) {
            $this->logError($e, 'generateAiDraft', 'JournalPage', 'Error llamada a Gemini para borrador IA');
            $this->dispatch('show-alert', type: 'error', message: __('labels.error_conect_IA'));
        }
    }


    public function render()
    {
        return view('livewire.journal-page');
    }
}
