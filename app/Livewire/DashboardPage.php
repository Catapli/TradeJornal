<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Alert;
use App\Models\JournalEntry;
use App\Models\Trade;
use App\Models\Traffic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\TradingRulesService; // <--- Importamos el servicio
use App\WithAiLimits;

class DashboardPage extends Component
{
    use WithFileUploads; // <--- IMPORTANTE: Usar el Trait
    use WithAiLimits; // <--- 2. Usar el Trait
    // ? Variables Nuevas
    public $selectedAccounts = []; // Aquí se guardarán los IDs (ej: [1, 5, 8])
    public $availableAccounts = [];
    // Datos para el gráfico
    public $winRateChartData = [];
    public $user;

    public $avgPnLChartData = []; // Variable para el gráfico
    public $dailyWinLossData = []; // Diario Ganancias Perdidas
    public $pnlTotal = 0;
    public $pnlTotal_perc = 0;
    // Estado del Calendario
    public $calendarDate; // Fecha de referencia (ej: 2026-01-01)
    public $calendarGrid = []; // Array con los datos para la vista
    // PROPIEDADES NUEVAS PARA EL MODAL
    public $showDayModal = false;
    public $selectedDate = null;
    public $dayTrades = [];

    public $evolutionChartData = [];
    public $dailyPnLChartData = [];

    public $selectedTrade = null;

    // PROPIEDADES PARA LA IA
    public $aiAnalysis = null;
    public $isAnalyzing = false;
    public $isAnalyzingTrade = false; // Spinner específico para el trade individual

    // Propiedades para el Journal
    // PROPIEDADES PÚBLICAS
    public $journalEntry;
    public $journalContent = '';
    public $journalMood = null;
    public $tags = [];

    // NUEVO: Propiedad para editar la nota
    public $notes = '';
    public $isSavingNotes = false;
    public $planStatus = null;

    // 1. Añade esto a las propiedades públicas
    public $heatmapData = [];

    public $recentNotes = []; // <--- NUEVA PROPIEDAD

    // NUEVO: Propiedad para la subida de imagen temporal
    public $uploadedScreenshot;

    // NUEVO: Variable primitiva para controlar la vista de la imagen
    public $currentScreenshot = null;

    public function mount()
    {
        $this->user = Auth::user();
        // Cargamos las cuentas con un formato amigable para el componente
        $this->availableAccounts = Account::where('user_id', $this->user->id)->where('status', '!=', 'burned')
            ->get()
            ->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'subtext' => $acc->login . ' (' . $acc->broker_name . ')' // Opcional
                ];
            });

        // Seleccionar 'all' por defecto o dejar vacío según prefieras
        $this->selectedAccounts = ['all'];

        $this->calculateStats();
        $this->generateCalendar(); // Generamos el grid
    }

    public function getTradesQuery()
    {
        $query = Trade::query();

        // 1. Si hay cuentas específicas seleccionadas (y no es 'all')
        if (!in_array('all', $this->selectedAccounts) && count($this->selectedAccounts) > 0) {
            $query->whereIn('account_id', $this->selectedAccounts);
        }

        // 2. Filtro de seguridad por usuario Y CONSISTENCIA DE ESTADO
        $query->whereHas('account', function ($q) {
            $q->where('user_id', $this->user->id);

            // 👇 AQUÍ ESTÁ EL FIX:
            // Debemos excluir las cuentas quemadas igual que hiciste en el mount().
            // De lo contrario, "ALL" incluye cuentas zombis que no están en el select.
            $q->where('status', '!=', 'burned');
        });

        return $query;
    }

    private function calculateStats()
    {
        // ... (Tu query anterior se mantiene igual) ...
        $query = $this->getTradesQuery();

        $stats = $query->selectRaw('
        COUNT(*) as total_trades,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades
    ')->first();

        $total = $stats->total_trades ?? 0;
        $wins = $stats->winning_trades ?? 0;
        $losses = $total - $wins;
        $winRate = $total > 0 ? round(($wins / $total) * 100, 2) : 0;

        $this->winRateChartData = [
            'series' => [(int)$wins, (int)$losses],
            'rate' => $winRate,
            'count_wins' => (int)$wins,     // 👈 Nuevo: Para la pastilla verde
            'count_losses' => (int)$losses  // 👈 Nuevo: Para la pastilla roja
        ];

        // --- NUEVO: Cargar últimas 5 notas ---
        $this->recentNotes = $this->getTradesQuery()
            ->whereNotNull('notes')
            ->where('notes', '!=', '') // Que no estén vacías
            ->with('tradeAsset') // Cargar el activo para mostrar el nombre
            ->orderBy('exit_time', 'desc')
            ->take(4) // Top 4 para que cuadre en diseño
            ->get();

        // ------------------------------------------------------
        // 1. CÁLCULO DE PNL TOTAL (Optimizado usando Accounts)
        // ------------------------------------------------------

        // Iniciamos la query sobre la tabla CUENTAS
        $accountsQuery = Account::where('user_id', $this->user->id);

        // Filtramos según la selección del multiselect
        if (!in_array('all', $this->selectedAccounts) && count($this->selectedAccounts) > 0) {
            // Si hay selección específica
            $accountsQuery->whereIn('id', $this->selectedAccounts);
        } else {
            // Si es 'all', aplicamos el mismo filtro que usaste en el mount 
            // (para ser coherentes con lo que ve el usuario)
            $accountsQuery->where('status', '!=', 'burned');
        }

        // Hacemos una única consulta a la base de datos para sumar balances
        //     $sums = $accountsQuery->selectRaw('
        //     SUM(current_balance) as total_current, 
        //     SUM(initial_balance) as total_initial
        // ')->first();

        // Reutilizamos $query que ya tiene los filtros de cuenta, usuario y status 'burned' aplicados.
        $this->pnlTotal = $query->sum('pnl');
        $this->pnlTotal_perc = $query->sum('pnl_percentage');


        // ------------------------------------------------------
        // 3. CÁLCULO DE MEDIAS (AVG WIN vs AVG LOSS)
        // ------------------------------------------------------
        // Reutilizamos la query de trades (con los filtros de cuentas aplicados)
        $avgs = $query->selectRaw('
        AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
        AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss
    ')->first();

        $avgWin = $avgs->avg_win ? round($avgs->avg_win, 2) : 0;
        $avgLoss = $avgs->avg_loss ? round($avgs->avg_loss, 2) : 0; // Esto será negativo (ej: -50.00)

        // CÁLCULO DEL RATIO RIESGO/BENEFICIO
        // Fórmula: Ganancia Media / Valor Absoluto de Pérdida Media
        // Si avg_loss es 0, el ratio es 0 (o infinito, pero ponemos 0 para no romper)
        $rrRatio = ($avgLoss != 0) ? abs($avgWin / $avgLoss) : 0;

        $this->avgPnLChartData = [
            'avg_win' => $avgWin,
            'avg_loss' => $avgLoss,   // Lo enviamos negativo para que se pinte el texto "-50€"
            'rr_ratio' => round($rrRatio, 2) // Lo enviamos calculado (ej: 1.5)
        ];

        // ------------------------------------------------------
        // 4. CÁLCULO DE DÍAS GANADORES VS PERDEDORES
        // ------------------------------------------------------

        $query = $this->getTradesQuery();

        // Hacemos la agrupación directamente en la base de datos
        // Esto devuelve una lista de días con su PnL total: 
        // [ {date: "2026-01-13", daily_pnl: 500}, {date: "2026-01-14", daily_pnl: -200} ]
        $dailyStats = $query->selectRaw('DATE(exit_time) as trade_date, SUM(pnl) as daily_pnl')
            ->whereNotNull('exit_time')
            ->groupByRaw('DATE(exit_time)') // groupByRaw funciona en MySQL y Postgres
            ->get();

        // Ahora contamos sobre los resultados agrupados
        $winDays = $dailyStats->where('daily_pnl', '>', 0)->count();
        $lossDays = $dailyStats->where('daily_pnl', '<', 0)->count();
        // $breakEvenDays = $dailyStats->where('daily_pnl', '=', 0)->count();

        $totalDays = $winDays + $lossDays;

        // Evitamos división por cero
        $dailyWinRate = $totalDays > 0 ? round(($winDays / $totalDays) * 100, 2) : 0;

        $this->dailyWinLossData = [
            'series' => [(int)$winDays, (int)$lossDays],
            'rate' => $dailyWinRate,
            'count_wins' => $winDays,
            'count_losses' => $lossDays
        ];

        // 5. CÁLCULO DE EVOLUCIÓN (AREA CHART)
        $this->calculateEvolution();
        // 6. CÁLCULO DE BARRAS PNL DIARIO
        $this->calculateDailyBars();

        // 7. Calculo del MAPA DE CALOR TEMPORAL
        $this->calculateHeatmap();

        // --- 5. PLAN DIARIO (WIDGET OBJETIVOS) ---
        // Instanciamos el servicio manualmente para no depender de inyección en métodos que no son render/mount
        $rulesService = app(TradingRulesService::class);
        $this->planStatus = $rulesService->checkDashboardStatus($this->selectedAccounts);
        // dd($this->planStatus);
    }

    public function getRecentTradesProperty()
    {
        // Reutilizamos tu query maestra (que ya filtra por cuentas, usuario y status)
        return $this->getTradesQuery()
            ->with('tradeAsset') // Carga impaciente para optimizar rendimiento
            ->orderBy('exit_time', 'desc') // Los más recientes primero
            ->take(10) // Limitamos a 10 fijo
            ->get();
    }

    private function calculateHeatmap()
    {
        $query = $this->getTradesQuery();

        // === VERSIÓN POSTGRESQL ===
        // EXTRACT(ISODOW) devuelve: 1 (Lunes) a 7 (Domingo).
        // Restamos 1 para obtener: 0 (Lunes) a 6 (Domingo), igual que MySQL WEEKDAY.

        $rawStats = $query->selectRaw('
            (CAST(EXTRACT(ISODOW FROM exit_time) AS INTEGER) - 1) as day_index, 
            CAST(EXTRACT(HOUR FROM exit_time) AS INTEGER) as hour, 
            SUM(pnl) as total_pnl
        ')
            ->whereNotNull('exit_time')
            // Filtramos solo Lunes (1) a Viernes (5) usando ISODOW estándar
            ->whereRaw('EXTRACT(ISODOW FROM exit_time) <= 5')
            // Agrupamos por las fórmulas exactas (Postgres es estricto con el Group By)
            ->groupByRaw('(CAST(EXTRACT(ISODOW FROM exit_time) AS INTEGER) - 1), CAST(EXTRACT(HOUR FROM exit_time) AS INTEGER)')
            ->get();

        // Inicializamos la estructura para ApexCharts (5 días x 24 horas)
        // ApexCharts Heatmap espera: [{ name: 'Lunes', data: [{x: '00:00', y: 50}, ...] }]
        $days = [__('labels.monday'), __('labels.tuesday'), __('labels.wednesday'), __('labels.thursday'), __('labels.friday')];
        $chartData = [];

        foreach ($days as $index => $dayName) {
            $hourlyData = [];
            for ($h = 0; $h < 24; $h++) {
                // Buscamos si hay datos para este Día/Hora
                $stat = $rawStats->where('day_index', $index)->where('hour', $h)->first();

                // x = Hora, y = PnL
                $hourlyData[] = [
                    'x' => sprintf('%02d:00', $h),
                    'y' => $stat ? round($stat->total_pnl, 2) : 0
                ];
            }
            $chartData[] = [
                'name' => $dayName,
                'data' => $hourlyData
            ];
        }

        // ApexCharts dibuja de abajo a arriba, invertimos para que Lunes quede arriba
        $this->heatmapData = array_reverse($chartData);
    }

    private function calculateEvolution()
    {
        $query = $this->getTradesQuery();

        // Obtenemos solo fecha y pnl, ordenados cronológicamente
        $trades = $query->select(['exit_time', 'pnl'])
            ->whereNotNull('exit_time')
            ->orderBy('exit_time', 'asc')
            ->get();

        // 1. Agrupamos por día (Y-m-d) y sumamos el PnL de ese día
        $dailyPnL = $trades->groupBy(function ($trade) {
            return $trade->exit_time->format('Y-m-d');
        })->map(function ($dayTrades) {
            return $dayTrades->sum('pnl');
        });

        // 2. Construimos la suma acumulativa
        $labels = []; // Fechas
        $data = [];   // PnL Acumulado

        // Punto de partida (Opcional, para que el gráfico nazca en 0)
        // Si tienes trades muy antiguos, quizás prefieras no poner esto, 
        // pero el usuario pidió "empezando por 0".
        $labels[] = __('labels.start_without_flag');
        $data[] = 0;

        $runningTotal = 0;

        foreach ($dailyPnL as $date => $pnl) {
            $runningTotal += $pnl;

            $labels[] = $date;
            $data[] = round($runningTotal, 2);
        }

        $this->evolutionChartData = [
            'categories' => $labels,
            'data' => $data,
            // Enviamos el total final para decidir el color del gráfico (Verde/Rojo)
            'is_positive' => $runningTotal >= 0
        ];
    }

    // Hook de Livewire: Se ejecuta cuando cambia el multiselect
    public function updatedSelectedAccounts()
    {
        $this->calculateStats();
        $this->generateCalendar();
        // Avisamos a Alpine que hay nuevos datos para redibujar gráficos
        $this->dispatch('dashboard-updated');
    }

    // Métodos de Navegación del Calendario
    public function nextMonth()
    {
        $this->calendarDate = Carbon::parse($this->calendarDate)->addMonth()->format('Y-m-d');
        $this->generateCalendar();
    }

    public function prevMonth()
    {
        $this->calendarDate = Carbon::parse($this->calendarDate)->subMonth()->format('Y-m-d');
        $this->generateCalendar();
    }

    public function generateCalendar()
    {
        $date = Carbon::parse($this->calendarDate);

        // 1. Definir rango visual (Lunes de la primera semana - Domingo de la última)
        $startOfCalendar = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar   = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // 2. Obtener Trades en ese rango
        $query = $this->getTradesQuery();

        // Agrupamos por día para obtener el PnL diario
        $trades = $query->whereBetween('exit_time', [$startOfCalendar, $endOfCalendar])
            ->selectRaw('DATE(exit_time) as date, SUM(pnl) as daily_pnl, SUM(pnl_percentage) as daily_percent')
            ->groupByRaw('DATE(exit_time)')
            ->get()
            ->keyBy('date'); // Indexamos por fecha para búsqueda rápida

        // 3. TUS JOURNALS 
        // Traemos solo el mood y si tiene contenido
        $journals = JournalEntry::where('user_id', $this->user->id)
            ->whereBetween('date', [$startOfCalendar, $endOfCalendar])
            ->get()
            ->keyBy('date'); // Indexamos por fecha (Y-m-d desde el cast del modelo)

        // 4. Construir el Grid
        $grid = [];
        $currentDay = $startOfCalendar->copy();

        while ($currentDay <= $endOfCalendar) {
            $dayString = $currentDay->format('Y-m-d');

            // Buscamos si hubo trades ese día
            // Nota: En la DB la fecha puede venir como '2026-01-13' (string)
            $dayData = $trades->get($dayString);
            $pnl = $dayData ? $dayData->daily_pnl : null;
            $percentage = $dayData ? $dayData->daily_percent : null;
            // Datos del Journal (Buscamos por objeto Carbon o String según tu cast)
            // Al usar keyBy('date') en Eloquent con cast 'date', la clave suele ser string Y-m-d 00:00:00
            // Para asegurar, buscamos flexiblemente:
            $journalData = $journals->first(function ($item) use ($dayString) {
                return $item->date->format('Y-m-d') === $dayString;
            });

            $grid[] = [
                'day' => $currentDay->format('d'),
                'date' => $dayString,
                'pnl' => $pnl,
                'pnl_percentage' => $percentage,
                // NUEVOS DATOS PARA LA VISTA
                'journal_mood' => $journalData ? $journalData->mood : null,
                'has_notes' => $journalData && !empty($journalData->content),
                'is_current_month' => $currentDay->month === $date->month,
                'is_today' => $currentDay->isToday(),
            ];

            $currentDay->addDay();
        }

        $this->calendarGrid = $grid;
    }

    private function calculateDailyBars()
    {
        $query = $this->getTradesQuery();

        $trades = $query->selectRaw('DATE(exit_time) as date, SUM(pnl) as daily_pnl')
            ->whereNotNull('exit_time')
            ->groupByRaw('DATE(exit_time)')
            ->orderBy('date', 'asc') // Cronológico
            ->get();



        $categories = [];
        $data = [];

        foreach ($trades as $day) {
            // Formato fecha corto: "13 Ene"
            $categories[] = \Carbon\Carbon::parse($day->date)->translatedFormat('d M');
            $data[] = round($day->daily_pnl, 2);
        }

        $this->dailyPnLChartData = [
            'categories' => $categories,
            'data' => $data
        ];
    }

    public function analyzeDayWithAi()
    {
        // 1. Evitar doble click
        $this->isAnalyzing = true;
        $this->aiAnalysis = null; // Limpiamos análisis previo

        // 2. Validación: ¿Hay operaciones?
        if (empty($this->dayTrades) || count($this->dayTrades) == 0) {
            $this->aiAnalysis = __('labels.not_operations_to_analyze');
            $this->isAnalyzing = false;
            return;
        }



        // 3. Formatear los datos: FORZAMOS EL ORDEN CRONOLÓGICO (De 00:00 a 23:59)
        // Usamos sortBy('exit_time') para asegurar que la IA lea la historia en orden correcto
        $tradesText = collect($this->dayTrades)
            ->sortBy('exit_time')
            ->map(function ($trade) {
                $hora = \Carbon\Carbon::parse($trade->exit_time)->format('H:i');
                $tipo = strtoupper($trade->direction);
                $simbolo = $trade->asset->name ?? $trade->tradeAsset->symbol ?? 'N/A';

                // Calculamos distancias si existen
                $extraInfo = "";
                if ($trade->mae_price && $trade->mfe_price) {
                    // Calculamos la distancia absoluta respecto a la entrada para contexto
                    // No le pasamos el precio exacto (ej: 1.0923) sino el concepto "Drawdown vs Runup"
                    // Pero para simplificar el prompt, le pasamos los precios y que la IA calcule si quiere,
                    // o mejor, le pasamos la "Eficiencia".

                    // Simple: Pasamos los datos crudos, Gemini es listo.
                    $extraInfo = "| MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}";
                }

                return "- [{$hora}] {$simbolo} ({$tipo}) | " . __('labels.lots') . " {$trade->size} | PnL: {$trade->pnl} {$extraInfo}";
            })->join("\n");

        Log::info($tradesText);

        // 4. El Prompt (La instrucción maestra)
        $prompt = "
            Realiza una auditoría de riesgo y comportamiento de la sesión de trading completa de hoy.
            Sé estricto, objetivo y profesional.
            
            DATOS DE LA SESIÓN (Cronológicos):
            $tradesText
            
            INSTRUCCIONES DE ANÁLISIS (Busca estos patrones):
            1. CONTROL EMOCIONAL (Tilt): ¿Hay operaciones consecutivas rápidas tras una pérdida (Revenge Trading)?
            2. GESTIÓN DE RIESGO: ¿Aumenta el lotaje tras perder (Martingala)? ¿Corta las ganancias rápido y deja correr las pérdidas?
            3. DISCIPLINA: ¿Hay sobreoperativa (muchas operaciones mediocres) o selección de calidad?

            REGLAS DE FORMATO:
            - NO escribas introducciones, saludos ni frases dramáticas.
            - Empieza DIRECTAMENTE con el primer punto del formato.

            FORMATO DE RESPUESTA REQUERIDO (Usa estos iconos):
            - **📊 Resumen:** Una frase que defina el estado mental y técnico del trader hoy.
            - **🚩 Alertas Detectadas:** Lista de errores graves (Tilt, Sobreoperativa, etc.). Si fue un día limpio, indica 'Ninguna'.
            - **💡 Consejo para Mañana:** Una acción correctiva concreta.
            - **🏆 Nota del Día:** [0/10] (Basado en la disciplina, no solo en el dinero ganado).
        ";

        try {
            $apiKey = env('GEMINI_API_KEY');

            // 5. Petición a Google Gemini (Modelo Flash, rápido y gratis)
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.4, // 0.4 es ideal para análisis técnico (bajo = más lógico/estricto)
                ],
            ]);

            if ($response->successful()) {
                // La estructura de Google es un poco anidada, así se saca el texto:
                $this->aiAnalysis = $response->json()['candidates'][0]['content']['parts'][0]['text'];
            } else {
                Log::error('Error Gemini API', ['body' => $response->body()]);
                $this->aiAnalysis = __("labels.coach_IA_not_available");
            }
        } catch (\Exception $e) {
            Log::error('Excepción Gemini', ['message' => $e->getMessage()]);
            $this->aiAnalysis = __("labels.coach_IA_error");
        }

        $this->isAnalyzing = false;
    }


    // Esta función se llama al hacer click en un día
    public function openDayDetails($date)
    {
        $this->selectedDate = $date;

        // 1. REUTILIZAMOS LA MISMA QUERY BASE DEL CALENDARIO
        // Esto garantiza que los filtros de Cuentas y el estado 'burned' coincidan al 100%
        $query = $this->getTradesQuery();

        // 2. Solo añadimos el filtro de fecha y las relaciones para la tabla
        $this->dayTrades = $query->whereDate('exit_time', $date)
            ->with(['account', 'tradeAsset']) // Traemos relación cuenta y activo
            ->orderBy('exit_time', 'asc')
            ->get();

        $this->journalEntry = JournalEntry::where('user_id', $this->user->id)
            ->where('date', $this->selectedDate)
            ->first();

        $this->showDayModal = true;
    }

    public function closeDayModal()
    {
        $this->showDayModal = false;
        $this->dayTrades = []; // Limpiamos para ahorrar memoria
        $this->selectedTrade = null; // Reseteamos también esto
    }

    public function selectTrade($tradeId)
    {
        // Cargamos el trade con todas sus relaciones necesarias para el detalle
        // (Incluimos 'account' y 'asset' por si acaso no estaban cargadas antes)
        $this->notes = ''; // Resetear notas
        $this->uploadedScreenshot = null; // Resetear input de archivo
        $this->selectedTrade = Trade::with(['account', 'tradeAsset'])->find($tradeId);
        Log::info('Trade seleccionado' . $this->selectedTrade->screenshot);
        // Cargar la nota existente
        $this->notes = $this->selectedTrade->notes;
        $this->currentScreenshot = $this->selectedTrade->screenshot;
        // 2. DISPARAR EVENTO PARA EL GRÁFICO (Esto es lo nuevo)
        // Enviamos la ruta directamente al navegador
        // $this->dispatch('trade-selected', path: $this->selectedTrade->chart_data_path);
        $this->dispatch(
            'trade-selected',
            path: $this->selectedTrade->chart_data_path,
            entry: $this->selectedTrade->entry_price,
            exit: $this->selectedTrade->exit_price,
            direction: $this->selectedTrade->direction
        );
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


    public function analyzeIndividualTrade()
    {
        // 1. Validaciones
        if (!$this->selectedTrade) return;

        $this->isAnalyzingTrade = true;
        $trade = $this->selectedTrade;

        // 2. Preparar el Prompt de Texto (Contexto Numérico)
        $contextoDatos = "
            DATOS DEL TRADE:
            - Activo: {$trade->tradeAsset->name}
            - Tipo: " . strtoupper($trade->direction) . "
            - Entrada: {$trade->entry_price} | Salida: {$trade->exit_price}
            - Resultado: {$trade->pnl} (Lotes: {$trade->size})
            - Duración: {$trade->duration_minutes} min
            - Eficiencia: MAE (Contra): {$trade->mae_price} | MFE (Favor): {$trade->mfe_price}
        ";

        // 2. Preparar los DATOS (Traducimos también las etiquetas: Activo, Tipo, etc.)
        // Usamos __('ai.labels.x') para que la data también esté en el idioma correcto
        $contextoDatos = "
        " . __('ai.labels.asset') . ": {$trade->tradeAsset->name}
        " . __('ai.labels.type') . ": " . strtoupper($trade->direction) . "
        " . __('ai.labels.entry') . ": {$trade->entry_price} | " . __('ai.labels.exit') . ": {$trade->exit_price}
        " . __('ai.labels.result') . ": {$trade->pnl} (Lots: {$trade->size})
        " . __('ai.labels.duration') . ": {$trade->duration_minutes} min
        " . __('ai.labels.efficiency') . ": MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}
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

        Log::info('Partes: ' . json_encode($parts));

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

    public function openTradeFromNotes($tradeId)
    {
        // 1. Obtenemos los IDs de la lista de NOTAS recientes
        // (Asegúrate de usar la misma query que usas para pintar la lista visual)
        $ids = collect($this->recentNotes)->pluck('id')->toArray();

        $this->dispatch(
            'open-trade-detail',
            tradeId: $tradeId,
            tradeIds: $ids
        );
    }

    public function openTradeFromTable($tradeId)
    {
        // 1. Obtenemos los IDs de la lista de TRADES recientes (la tabla grande)
        $ids = $this->recentTrades->pluck('id')->toArray();

        // 2. Disparamos el evento con el contexto "Tabla"
        $this->dispatch(
            'open-trade-detail',
            tradeId: $tradeId,
            tradeIds: $ids
        );
    }






    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
