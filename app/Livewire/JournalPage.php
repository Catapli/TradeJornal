<?php

namespace App\Livewire;

use App\Models\JournalEntry;
use App\Models\Trade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;

class JournalPage extends Component
{
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
    public $dayPnL = 0;
    public $mistakesSummary = [];

    // Propiedad para controlar qué mes estamos viendo en el mini-calendario
    public $calendarRef;

    public function mount($date = null)
    {
        // Si no hay fecha en URL, hoy.
        $this->date = $this->date ?? Carbon::today()->format('Y-m-d');
        // Inicializamos la referencia del calendario con la fecha seleccionada
        $this->calendarRef = Carbon::parse($this->date);

        $this->loadData();
    }

    public function loadData()
    {
        // 1. Buscamos si YA existe la entrada para este día
        $this->entry = JournalEntry::where('user_id', Auth::id())
            ->where('date', $this->date)
            ->first();

        if ($this->entry) {
            // CASO A: El día YA existía. Cargamos lo que había guardado (Historial)
            $this->pre_market_mood = $this->entry->pre_market_mood;
            $this->pre_market_notes = $this->entry->pre_market_notes;
            $this->content = $this->entry->content;

            // Convertimos JSON a Array si hace falta
            $dbObjs = $this->entry->daily_objectives;
            if (is_string($dbObjs)) $dbObjs = json_decode($dbObjs, true);

            $this->daily_objectives = $dbObjs ?? [];
        } else {
            // CASO B: Es un día NUEVO. Cargamos la PLANTILLA Global.

            // Creamos la instancia en memoria (sin guardar aún en BD para no ensuciar si no escribe nada)
            $this->entry = new JournalEntry([
                'user_id' => Auth::id(),
                'date' => $this->date
            ]);

            $this->pre_market_mood = null;
            $this->pre_market_notes = '';
            $this->content = '';

            // AQUÍ LA MAGIA: Traemos los objetivos activos del usuario
            $templates = \App\Models\TradingObjective::where('user_id', Auth::id())
                ->where('is_active', true)
                ->get();

            if ($templates->count() > 0) {
                // Formateamos para el JSON del día: ['text' => '...', 'done' => false]
                $this->daily_objectives = $templates->map(function ($rule) {
                    return ['text' => $rule->text, 'done' => false];
                })->toArray();
            } else {
                // Si el usuario no ha configurado reglas aún
                $this->daily_objectives = [['text' => 'Define tus reglas en configuración', 'done' => false]];
            }
        }

        // 3. Cargar Trades del día (CORREGIDO Y ORDENADO)
        $trades = Trade::whereHas('account', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->whereDate('exit_time', $this->date)
            ->with('mistakes')
            ->orderBy('exit_time', 'asc') // <--- CAMBIO: Orden Ascendente (Antigua -> Nueva)
            ->get();

        $this->dayTrades = $trades;
        $this->dayPnL = $trades->sum('pnl');

        // 4. Resumen de Errores (Mistakes)
        // Solo funcionará si ya implementaste la relación 'mistakes' en el modelo Trade
        $this->mistakesSummary = $trades->pluck('mistakes')->flatten()->groupBy('name')->map->count();
    }

    // --- GESTIÓN DE OBJETIVOS ---
    public function addObjective()
    {
        $this->daily_objectives[] = ['done' => false, 'text' => ''];
    }

    public function removeObjective($index)
    {
        unset($this->daily_objectives[$index]);
        $this->daily_objectives = array_values($this->daily_objectives); // Reindexar
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

    // Abrir modal y cargar reglas
    public function openRulesManager()
    {
        $this->userRules = \App\Models\TradingObjective::where('user_id', Auth::id())->get();
        $this->showRulesModal = true;
    }

    // Crear nueva regla maestra
    public function addMasterRule()
    {
        if (empty($this->newRuleText)) return;

        \App\Models\TradingObjective::create([
            'user_id' => Auth::id(),
            'text' => $this->newRuleText,
            'is_active' => true
        ]);

        $this->newRuleText = '';
        $this->openRulesManager(); // Recargar lista
        // Opcional: Si es el día de hoy y no habías empezado, recargar los objetivos del día actual
        if (!$this->entry->exists) $this->loadData();
    }

    // Borrar/Desactivar regla maestra
    public function deleteMasterRule($id)
    {
        \App\Models\TradingObjective::destroy($id);
        $this->openRulesManager();
    }

    // Toggle Activo/Inactivo
    public function toggleMasterRule($id)
    {
        $rule = \App\Models\TradingObjective::find($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();
        $this->openRulesManager();
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
    public function save()
    {
        $calculatedScore = $this->calculateDiscipline();

        // Usamos updateOrCreate para manejar tanto creación como edición
        $this->entry = JournalEntry::updateOrCreate(
            ['user_id' => Auth::id(), 'date' => $this->date],
            [
                'pre_market_mood' => $this->pre_market_mood,
                'pre_market_notes' => $this->pre_market_notes,
                'daily_objectives' => $this->daily_objectives, // Aquí se guarda la copia del día
                'content' => $this->content,
                'discipline_score' => $calculatedScore,
            ]
        );


        $this->showAlert('success', '✅ Diario Actualizado');
    }

    public function showAlert($type, $message)
    {
        $this->dispatch('show-alert', [
            'type' => $type,
            'message' => $message
        ]);
    }


    // Navegación del Mini-Calendario (Solo visual)
    public function prevMonth()
    {
        $this->calendarRef->subMonth();
    }

    public function nextMonth()
    {
        $this->calendarRef->addMonth();
    }

    // Propiedad Computada del Calendario (Optimizada)
    public function getMiniCalendarProperty()
    {
        $start = $this->calendarRef->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $this->calendarRef->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Días con entrada escrita
        $entries = JournalEntry::where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->whereNotNull('content') // Solo si escribieron algo
            ->pluck('date')
            ->toArray();

        // Formatear fechas de la DB para comparar string con string
        $entries = array_map(fn($d) => substr($d, 0, 10), $entries);

        $days = [];
        $curr = $start->copy();

        while ($curr <= $end) {
            $dateStr = $curr->format('Y-m-d');
            $days[] = [
                'date' => $dateStr,
                'day' => $curr->day,
                'is_current_month' => $curr->month === $this->calendarRef->month,
                'is_today' => $curr->isToday(),
                'is_selected' => $dateStr === $this->date,
                'has_entry' => in_array($dateStr, $entries),
            ];
            $curr->addDay();
        }
        return $days;
    }

    // MÉTODO NUEVO: Cambiar día sin recarga
    public function selectDate($date)
    {
        $this->date = $date;
        $this->calendarRef = Carbon::parse($date); // Sincronizar calendario
        $this->loadData(); // Recargar todos los datos de ese día
    }

    // Añade esto en JournalPage.php
    // Asegúrate de importar: use Illuminate\Support\Facades\Http; use Illuminate\Support\Facades\Log;

    public function generateAiDraft()
    {
        // 1. Validar si hay datos mínimos
        if (count($this->dayTrades) === 0 && empty($this->pre_market_mood)) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => 'Necesito al menos operaciones o un estado de ánimo para escribir.']);
            return;
        }

        // 2. Preparar el Contexto para la IA
        $mood = $this->pre_market_mood ? ucfirst($this->pre_market_mood) : 'No registrado';
        $pnl = number_format($this->dayPnL, 2);
        $totalTrades = count($this->dayTrades);

        // Resumen de Trades y Errores
        $tradesContext = collect($this->dayTrades)->map(function ($t) {
            $mistakes = $t->mistakes->pluck('name')->join(', ');
            $errorStr = $mistakes ? "(Errores: $mistakes)" : "(Ejecución Limpia)";
            $result = $t->pnl >= 0 ? "Ganancia" : "Pérdida";
            return "- {$t->exit_time->format('H:i')}: {$t->tradeAsset->name} ({$t->direction}) | $result {$t->pnl}$ | $errorStr";
        })->join("\n");

        // 3. El Prompt
        $prompt = "
            Actúa como un coach de trading profesional y redactor. Escribe la entrada del diario de hoy en PRIMERA PERSONA (como si fueras yo).
            
            MIS DATOS DE HOY:
            - Estado de ánimo inicial: $mood
            - Resultado total: $pnl $
            - Total operaciones: $totalTrades
            
            DESGLOSE DE OPERACIONES:
            $tradesContext
            
            INSTRUCCIONES DE REDACCIÓN:
            1. Empieza con una frase resumen de cómo fue la sesión (basado en PnL y Mood).
            2. Analiza brevemente el comportamiento. Si hubo errores (etiquetados arriba), sé crítico pero constructivo. Si fue limpio, felicítame.
            3. Si hubo pérdidas grandes o rachas, menciona el aspecto psicológico.
            4. Termina con una conclusión breve de mejora.
            5. Usa etiquetas HTML básicas para el formato (usa <strong> para negritas, <em> para cursiva, <br> para saltos de línea, <ul>/<li> para listas). NO uses Markdown.
            6. Sé conciso, máximo 3 párrafos.

             5. FORMATO TÉCNICO OBLIGATORIO:
               - Envuelve cada párrafo en etiquetas <p>...</p>.
               - Usa <strong> para negritas.
               - Usa <ul><li>...</li></ul> para listas.
               - NO uses Markdown (nada de ** o ##). Solo HTML limpio.
               - NO incluyas ```html al principio ni al final.
        ";

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

                // ENVÍO SIMPLE:
                $this->dispatch('editor-content-updated', $this->content);
                $this->dispatch('show-alert', ['type' => 'success', 'message' => 'Borrador generado correctamente']);
            }
        } catch (\Exception $e) {
            Log::error("Error IA Journal: " . $e->getMessage());
            $this->dispatch('show-alert', ['type' => 'error', 'message' => 'Error al conectar con la IA']);
        }
    }


    public function render()
    {
        return view('livewire.journal-page');
    }
}
