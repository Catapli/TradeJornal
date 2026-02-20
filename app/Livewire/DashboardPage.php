<?php

namespace App\Livewire;

use App\LogActions;
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
use Exception;
use Illuminate\Support\Facades\Cache;

class DashboardPage extends Component
{
    use WithFileUploads; // <--- IMPORTANTE: Usar el Trait
    use WithAiLimits; // <--- 2. Usar el Trait
    use LogActions;
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

    // 👇 NUEVAS PROPIEDADES PRIVADAS (no se envían al navegador)
    private $_dayTradesCache = null;
    private $_cachedDate = null;
    private $_recentTradesCache = null;

    // 👇 NUEVO: Listener para cuando se actualiza un trade
    protected $listeners = [
        'trade-updated' => 'refreshRecentNotes'
    ];

    public function mount()
    {
        try {
            $this->user = Auth::user();

            // 👇 SIN CACHÉ - Query directa (versión original)
            $this->availableAccounts = Account::where('user_id', $this->user->id)
                ->where('status', '!=', 'burned')
                ->get()
                ->map(function ($acc) {
                    return [
                        'id' => $acc->id,
                        'name' => $acc->name,
                        'subtext' => $acc->login . ' (' . $acc->broker_name . ')'
                    ];
                });

            $this->selectedAccounts = ['all'];
            $this->calculateStats();
            $this->generateCalendar();
        } catch (Exception $e) {
            $this->logError($e, 'mount', 'DashboardPage', 'Error al cargar el dashboard inicial');

            // Fallback seguro
            $this->availableAccounts = collect([]);
            $this->selectedAccounts = ['all'];
            $this->winRateChartData = ['series' => [0, 0], 'rate' => 0];
            $this->avgPnLChartData = ['avg_win' => 0, 'avg_loss' => 0, 'rr_ratio' => 0];
            $this->dailyWinLossData = ['series' => [0, 0], 'rate' => 0];
            $this->evolutionChartData = ['categories' => [], 'data' => [], 'is_positive' => true];
            $this->dailyPnLChartData = ['categories' => [], 'data' => []];
            $this->heatmapData = [];
            $this->pnlTotal = 0;
            $this->pnlTotal_perc = 0;
            $this->calendarGrid = [];
            $this->recentNotes = collect([]);
            $this->planStatus = [];
        }
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
        // --- 1. WIN RATE ---
        try {
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
                'count_wins' => (int)$wins,
                'count_losses' => (int)$losses
            ];
        } catch (Exception $e) {
            $this->logError($e, 'CalculateWinRate', 'DashboardPage', 'Error al calcular Win Rate');
            $this->winRateChartData = ['series' => [0, 0], 'rate' => 0, 'count_wins' => 0, 'count_losses' => 0];
        }



        // --- 2. RECENT NOTES ---
        try {
            $this->loadRecentNotes();
        } catch (Exception $e) {
            $this->logError($e, 'CalculateRecentNotes', 'DashboardPage', 'Error al cargar notas recientes');
            $this->recentNotes = collect([]);
        }

        // --- 3. PNL TOTAL ---
        try {
            $this->pnlTotal = $query->sum('pnl');
            $this->pnlTotal_perc = $query->sum('pnl_percentage');
        } catch (Exception $e) {
            $this->logError($e, 'CalculatePnLTotal', 'DashboardPage', 'Error al calcular PnL Total');
            $this->pnlTotal = 0;
            $this->pnlTotal_perc = 0;
        }


        // ------------------------------------------------------
        // 3. CÁLCULO DE MEDIAS (AVG WIN vs AVG LOSS)
        // ------------------------------------------------------
        // Reutilizamos la query de trades (con los filtros de cuentas aplicados)
        try {
            $query = $this->getTradesQuery();
            $avgs = $query->selectRaw('
            AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
            AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss
        ')->first();

            $avgWin = $avgs->avg_win ? round($avgs->avg_win, 2) : 0;
            $avgLoss = $avgs->avg_loss ? round($avgs->avg_loss, 2) : 0;
            $rrRatio = ($avgLoss != 0) ? abs($avgWin / $avgLoss) : 0;

            $this->avgPnLChartData = [
                'avg_win' => $avgWin,
                'avg_loss' => $avgLoss,
                'rr_ratio' => round($rrRatio, 2)
            ];
        } catch (Exception $e) {
            $this->logError($e, 'CalculateAvgPnL', 'DashboardPage', 'Error al calcular Avg Win/Loss');
            $this->avgPnLChartData = ['avg_win' => 0, 'avg_loss' => 0, 'rr_ratio' => 0];
        }

        // ------------------------------------------------------
        // 4. CÁLCULO DE DÍAS GANADORES VS PERDEDORES
        // ------------------------------------------------------
        try {
            $query = $this->getTradesQuery();
            $dailyStats = $query->selectRaw('DATE(entry_time) as trade_date, SUM(pnl) as daily_pnl')
                ->whereNotNull('entry_time')
                ->groupByRaw('DATE(entry_time)')
                ->get();

            $winDays = $dailyStats->where('daily_pnl', '>', 0)->count();
            $lossDays = $dailyStats->where('daily_pnl', '<', 0)->count();
            $totalDays = $winDays + $lossDays;
            $dailyWinRate = $totalDays > 0 ? round(($winDays / $totalDays) * 100, 2) : 0;

            $this->dailyWinLossData = [
                'series' => [(int)$winDays, (int)$lossDays],
                'rate' => $dailyWinRate,
                'count_wins' => $winDays,
                'count_losses' => $lossDays
            ];
        } catch (Exception $e) {
            $this->logError($e, 'CalculateDailyWinLoss', 'DashboardPage', 'Error al calcular días ganadores/perdedores');
            $this->dailyWinLossData = ['series' => [0, 0], 'rate' => 0, 'count_wins' => 0, 'count_losses' => 0];
        }

        // 5. CÁLCULO DE EVOLUCIÓN (AREA CHART)
        try {
            $this->calculateEvolution();
        } catch (Exception $e) {
            $this->logError($e, 'CalculateEvolution', 'DashboardPage', 'Error al calcular evolución');
            $this->evolutionChartData = ['categories' => [], 'data' => [], 'is_positive' => true];
        }

        // --- 6. DAILY PNL BARS ---
        try {
            $this->calculateDailyBars();
        } catch (Exception $e) {
            $this->logError($e, 'CalculateDailyBars', 'DashboardPage', 'Error al calcular barras diarias');
            $this->dailyPnLChartData = ['categories' => [], 'data' => []];
        }

        // 7. Calculo del MAPA DE CALOR TEMPORAL
        try {
            $this->calculateHeatmap();
        } catch (Exception $e) {
            $this->logError($e, 'CalculateHeatmap', 'DashboardPage', 'Error al calcular heatmap');
            $this->heatmapData = [];
        }

        // --- 9. PLAN STATUS ---
        try {
            $rulesService = app(TradingRulesService::class);
            $this->planStatus = $rulesService->checkDashboardStatus($this->selectedAccounts);
        } catch (Exception $e) {
            $this->logError($e, 'CalculatePlanStatus', 'DashboardPage', 'Error al calcular plan status');
            $this->planStatus = [];
        }

        // 👇 AÑADIR al final

    }

    public function getRecentTradesProperty()
    {
        try {
            // 👇 Si ya se calculó, devolver la cache
            if ($this->_recentTradesCache !== null) {
                return $this->_recentTradesCache;
            }

            // 👇 Si no, ejecutar la query y cachear
            $this->_recentTradesCache = $this->getTradesQuery()
                ->with([
                    'tradeAsset:id,name,symbol',
                    'account:id,name'
                ])
                ->select([
                    'id',
                    'trade_asset_id',
                    'account_id',
                    'exit_time',
                    'entry_price',
                    'exit_price',
                    'direction',
                    'size',
                    'pnl',
                    'mae_price',
                    'mfe_price',
                    'notes',
                    'screenshot',
                ])
                ->orderBy('exit_time', 'desc')
                ->take(10)
                ->get();

            return $this->_recentTradesCache;
        } catch (\Exception $e) {
            $this->logError($e, 'GetRecentTrades', 'DashboardPage', 'Error al cargar operaciones recientes');
            return collect([]);
        }
    }



    private function calculateHeatmap()
    {
        try {
            $query = $this->getTradesQuery();
            $rawStats = $query->selectRaw('
            (CAST(EXTRACT(ISODOW FROM entry_time) AS INTEGER) - 1) as day_index,
            CAST(EXTRACT(HOUR FROM entry_time) AS INTEGER) as hour,
            SUM(pnl) as total_pnl
        ')
                ->whereNotNull('entry_time')
                ->whereRaw('EXTRACT(ISODOW FROM entry_time) <= 5')
                ->groupByRaw('(CAST(EXTRACT(ISODOW FROM entry_time) AS INTEGER) - 1), CAST(EXTRACT(HOUR FROM entry_time) AS INTEGER)')
                ->get();

            $days = [
                __('labels.monday'),
                __('labels.tuesday'),
                __('labels.wednesday'),
                __('labels.thursday'),
                __('labels.friday')
            ];

            $chartData = [];
            foreach ($days as $index => $dayName) {
                $hourlyData = [];
                for ($h = 0; $h < 24; $h++) {
                    $stat = $rawStats->where('day_index', $index)->where('hour', $h)->first();
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

            $this->heatmapData = array_reverse($chartData);
        } catch (\Exception $e) {
            $this->logError($e, 'CalculateHeatmap', 'DashboardPage', 'Error al calcular heatmap temporal');
            $this->heatmapData = [];
        }
    }




    private function calculateEvolution()
    {
        try {
            $query = $this->getTradesQuery();
            $trades = $query->select(['exit_time', 'pnl'])
                ->whereNotNull('exit_time')
                ->orderBy('exit_time', 'asc')
                ->get();

            $dailyPnL = $trades->groupBy(function ($trade) {
                return $trade->exit_time->format('Y-m-d');
            })->map(function ($dayTrades) {
                return $dayTrades->sum('pnl');
            });

            $labels = [];
            $data = [];

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
                'is_positive' => $runningTotal >= 0
            ];
        } catch (\Exception $e) {
            $this->logError($e, 'CalculateEvolution', 'DashboardPage', 'Error al calcular evolución del PnL');
            $this->evolutionChartData = ['categories' => [], 'data' => [], 'is_positive' => true];
        }
    }




    public function updatedSelectedAccounts()
    {
        try {
            // 1. Validar que al menos haya una cuenta seleccionada
            if (empty($this->selectedAccounts)) {
                $this->selectedAccounts = ['all'];
            }

            $this->_recentTradesCache = null;

            // 2. Recalcular estadísticas
            $this->calculateStats();

            // 3. Regenerar calendario
            $this->generateCalendar();

            // 4. Avisar a Alpine que hay nuevos datos para redibujar gráficos
            $this->dispatch('dashboard-updated');
        } catch (\Exception $e) {
            $this->logError($e, 'UpdatedSelectedAccounts', 'DashboardPage', 'Error al cambiar filtro de cuentas');

            // Restaurar a 'all' como fallback
            $this->selectedAccounts = ['all'];

            // Intentar cargar con 'all' de nuevo
            try {
                $this->calculateStats();
                $this->generateCalendar();
                $this->dispatch('dashboard-updated');
            } catch (\Exception $retryException) {
                // Si falla incluso con 'all', loguear y mostrar valores vacíos
                $this->logError($retryException, 'UpdatedSelectedAccountsRetry', 'DashboardPage', 'Error al reintentar con todas las cuentas');
                $this->dispatch('notify', __('labels.error_loading_accounts'));
            }
        }
    }

    public function nextMonth()
    {
        try {
            $this->calendarDate = Carbon::parse($this->calendarDate)
                ->addMonth()
                ->format('Y-m-d');

            $this->generateCalendar();
        } catch (\Exception $e) {
            $this->logError($e, 'NextMonth', 'DashboardPage', 'Error al navegar al mes siguiente');

            // Restaurar a mes actual como fallback
            $this->calendarDate = Carbon::now()->format('Y-m-d');
            $this->generateCalendar();
        }
    }

    public function prevMonth()
    {
        try {
            $this->calendarDate = Carbon::parse($this->calendarDate)
                ->subMonth()
                ->format('Y-m-d');

            $this->generateCalendar();
        } catch (\Exception $e) {
            $this->logError($e, 'PrevMonth', 'DashboardPage', 'Error al navegar al mes anterior');

            // Restaurar a mes actual como fallback
            $this->calendarDate = Carbon::now()->format('Y-m-d');
            $this->generateCalendar();
        }
    }



    public function generateCalendar()
    {
        try {
            $date = Carbon::parse($this->calendarDate);

            // 1. Definir rango visual
            $startOfCalendar = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $endOfCalendar = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            // 2. Obtener Trades en ese rango
            $query = $this->getTradesQuery();
            $trades = $query
                ->whereBetween('entry_time', [$startOfCalendar, $endOfCalendar])
                ->selectRaw('DATE(entry_time) as date, SUM(pnl) as daily_pnl, SUM(pnl_percentage) as daily_percent')
                ->groupByRaw('DATE(entry_time)')
                ->get()
                ->keyBy('date');

            // 3. Journals
            $journals = JournalEntry::where('user_id', $this->user->id)
                ->whereBetween('date', [$startOfCalendar, $endOfCalendar])
                ->get()
                ->keyBy('date');

            // 4. Construir el Grid
            $grid = [];
            $currentDay = $startOfCalendar->copy();

            while ($currentDay <= $endOfCalendar) {
                $dayString = $currentDay->format('Y-m-d');
                $dayData = $trades->get($dayString);
                $pnl = $dayData ? $dayData->daily_pnl : null;
                $percentage = $dayData ? $dayData->daily_percent : null;

                $journalData = $journals->first(function ($item) use ($dayString) {
                    return $item->date->format('Y-m-d') === $dayString;
                });

                $grid[] = [
                    'day' => $currentDay->format('d'),
                    'date' => $dayString,
                    'pnl' => $pnl,
                    'pnl_percentage' => $percentage,
                    'journal_mood' => $journalData ? $journalData->mood : null,
                    'has_notes' => $journalData && !empty($journalData->content),
                    'is_current_month' => $currentDay->month === $date->month,
                    'is_today' => $currentDay->isToday(),
                ];

                $currentDay->addDay();
            }

            $this->calendarGrid = $grid;
        } catch (Exception $e) {
            $this->logError($e, 'GenerateCalendar', 'DashboardPage', 'Error al generar calendario');
            $this->calendarGrid = [];
        }
    }




    private function calculateDailyBars()
    {
        try {
            $query = $this->getTradesQuery();
            $trades = $query->selectRaw('DATE(exit_time) as date, SUM(pnl) as daily_pnl')
                ->whereNotNull('exit_time')
                ->groupByRaw('DATE(exit_time)')
                ->orderBy('date', 'asc')
                ->get();

            $categories = [];
            $data = [];

            foreach ($trades as $day) {
                $categories[] = \Carbon\Carbon::parse($day->date)->translatedFormat('d M');
                $data[] = round($day->daily_pnl, 2);
            }

            $this->dailyPnLChartData = [
                'categories' => $categories,
                'data' => $data
            ];
        } catch (\Exception $e) {
            $this->logError($e, 'CalculateDailyBars', 'DashboardPage', 'Error al calcular barras diarias de PnL');
            $this->dailyPnLChartData = ['categories' => [], 'data' => []];
        }
    }




    public function analyzeDayWithAi()
    {
        try {
            // 1. Validar API Key
            $apiKey = env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                $this->aiAnalysis = __('labels.gemini_api_key_missing');
                $this->isAnalyzing = false;
                return;
            }

            // ----------------------------------------------------
            // 2. VALIDACIÓN DE LÍMITE (NUEVO)
            // ----------------------------------------------------
            if (!$this->checkAiLimit()) {
                $this->isAnalyzingTrade = false; // Apagar spinner
                $this->dispatch('notify', __('labels.limit_ai_reached'));
                return; // Detener ejecución
            }

            // 2. Evitar doble click
            $this->isAnalyzing = true;
            $this->aiAnalysis = null;

            // 3. Validación: ¿Hay operaciones?
            if (empty($this->dayTrades) || count($this->dayTrades) == 0) {
                $this->aiAnalysis = __('labels.not_operations_to_analyze');
                $this->isAnalyzing = false;
                return;
            }

            // 4. Formatear los datos (orden cronológico)
            $tradesText = collect($this->dayTrades)
                ->sortBy('exit_time')
                ->map(function ($trade) {
                    $hora = \Carbon\Carbon::parse($trade->exit_time)->format('H:i');
                    $tipo = strtoupper($trade->direction);
                    $simbolo = $trade->tradeAsset->name ?? $trade->tradeAsset->symbol ?? 'N/A';

                    $extraInfo = "";
                    if ($trade->mae_price && $trade->mfe_price) {
                        $extraInfo = "| MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}";
                    }

                    return "- [{$hora}] {$simbolo} ({$tipo}) | " . __('labels.lots') . " {$trade->size} | PnL: {$trade->pnl} {$extraInfo}";
                })->join("\n");

            // 5. El Prompt
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

            // 6. Petición a Gemini con timeout de 15 segundos
            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                    ],
                ]);

            if ($response->successful()) {
                $this->aiAnalysis = $response->json()['candidates'][0]['content']['parts'][0]['text'];

                // ----------------------------------------------------
                // 2. CONSUMIR CRÉDITO (NUEVO)
                // Solo restamos si la IA respondió bien.
                // ----------------------------------------------------
                $this->consumeAiCredit();
            } else {
                // Log del error con el cuerpo completo de la respuesta
                $this->logError(
                    new \Exception('Gemini API Error: ' . $response->body()),
                    'AnalyzeDayWithAi',
                    'DashboardPage',
                    'Error en la respuesta de Gemini API'
                );
                $this->aiAnalysis = __("labels.coach_IA_not_available");
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout o error de red
            $this->logError($e, 'AnalyzeDayWithAi', 'DashboardPage', 'Timeout o error de conexión con Gemini');
            $this->aiAnalysis = __("labels.coach_IA_timeout");
        } catch (\Exception $e) {
            // Cualquier otro error
            $this->logError($e, 'AnalyzeDayWithAi', 'DashboardPage', 'Error general al analizar día con IA');
            $this->aiAnalysis = __("labels.coach_IA_error");
        } finally {
            // IMPORTANTE: Siempre desactivar el loading, pase lo que pase
            $this->isAnalyzing = false;
        }
    }



    public function openDayDetails($date)
    {
        try {
            if (!$date || !strtotime($date)) {
                $this->dispatch('notify', __('labels.invalid_date'));
                return;
            }

            $this->selectedDate = $date;

            // 👇 OPTIMIZACIÓN: Solo cargar si cambia la fecha
            if ($this->_cachedDate !== $date) {
                $query = $this->getTradesQuery();
                $this->_dayTradesCache = $query->whereDate('exit_time', $date)
                    ->with([
                        'account:id,name',
                        'tradeAsset:id,name,symbol'
                    ])
                    ->select([
                        'id',
                        'account_id',
                        'trade_asset_id',
                        'exit_time',
                        'entry_price',
                        'exit_price',
                        'direction',
                        'size',
                        'pnl',
                        'mae_price',
                        'mfe_price',
                        'notes',
                        'screenshot',
                        'duration_minutes',
                    ])
                    ->orderBy('exit_time', 'asc')
                    ->get();

                // Marcar la fecha como cacheada
                $this->_cachedDate = $date;
            }

            // Asignar la cache a la propiedad pública (para que Blade la vea)
            $this->dayTrades = $this->_dayTradesCache;

            $this->journalEntry = JournalEntry::where('user_id', $this->user->id)
                ->where('date', $this->selectedDate)
                ->first();

            $this->showDayModal = true;
        } catch (\Exception $e) {
            $this->logError($e, 'OpenDayDetails', 'DashboardPage', "Error al abrir detalles del día: {$date}");
            $this->dayTrades = collect([]);
            $this->journalEntry = null;
            $this->showDayModal = true;
            $this->dispatch('notify', __('labels.error_loading_day_details'));
        }
    }




    public function closeDayModal()
    {
        try {
            $this->showDayModal = false;
            $this->dayTrades = [];
            $this->selectedTrade = null;
            $this->aiAnalysis = null;

            // 👇 NUEVO: Limpiar cache privada
            $this->_dayTradesCache = null;
            $this->_cachedDate = null;
        } catch (Exception $e) {
            $this->logError($e, 'CloseDayModal', 'DashboardPage', 'Error al cerrar modal de día');
            $this->showDayModal = false;
            $this->dayTrades = [];
            $this->selectedTrade = null;

            // Limpiar cache también en caso de error
            $this->_dayTradesCache = null;
            $this->_cachedDate = null;
        }
    }



    public function selectTrade($tradeId)
    {
        try {
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('notify', __('labels.invalid_trade_id'));
                return;
            }

            $this->notes = '';
            $this->uploadedScreenshot = null;

            // 👇 OPTIMIZACIÓN: Eager Loading selectivo + SELECT específico
            $this->selectedTrade = Trade::query()
                ->whereHas('account', function ($q) {
                    $q->where('user_id', $this->user->id);
                })
                // Solo cargar relaciones esenciales
                ->with([
                    'tradeAsset:id,name,symbol',
                    'account:id,name', // Solo si lo muestras en el modal
                ])
                // Solo traer campos necesarios
                ->select([
                    'id',
                    'account_id',
                    'trade_asset_id',
                    'strategy_id',      // Por si lo usas
                    'direction',
                    'entry_price',
                    'exit_price',
                    'size',
                    'pnl',
                    'pnl_percentage',
                    'duration_minutes',
                    'entry_time',
                    'exit_time',
                    'notes',
                    'screenshot',
                    'chart_data_path',
                    'ai_analysis',
                    'mae_price',
                    'mfe_price',
                    'mood',             // Por si lo usas
                    'pips_traveled'
                ])
                ->find($tradeId);

            if (!$this->selectedTrade) {
                $this->dispatch('notify', __('labels.trade_not_found'));
                return;
            }

            $this->notes = $this->selectedTrade->notes;
            $this->currentScreenshot = $this->selectedTrade->screenshot;

            $this->dispatch(
                'trade-selected',
                path: $this->selectedTrade->chart_data_path,
                entry: $this->selectedTrade->entry_price,
                exit: $this->selectedTrade->exit_price,
                direction: $this->selectedTrade->direction
            );
        } catch (\Exception $e) {
            $this->logError($e, 'SelectTrade', 'DashboardPage', "Error al seleccionar trade ID: {$tradeId}");
            $this->selectedTrade = null;
            $this->notes = '';
            $this->currentScreenshot = null;
            $this->dispatch('notify', __('labels.error_loading_trade'));
        }
    }



    /**
     * NUEVO: Se ejecuta automáticamente cuando 'uploadedScreenshot' cambia
     * (es decir, cuando el usuario suelta el archivo en el input).
     */
    public function updatedUploadedScreenshot()
    {
        try {
            // 1. Validación del archivo
            $this->validate([
                'uploadedScreenshot' => 'required|image|mimes:png,jpg,jpeg,webp|max:10240', // 10MB
            ]);

            // 2. Validar que hay un trade seleccionado
            if (!$this->selectedTrade) {
                $this->dispatch('notify', __('labels.no_trade_selected'));
                $this->reset('uploadedScreenshot');
                return;
            }

            // 3. Guardar el archivo nuevo
            $path = $this->uploadedScreenshot->store('screenshots', 'public');

            // 4. Eliminar el archivo antiguo (solo si existe)
            if (
                $this->selectedTrade->screenshot &&
                \Illuminate\Support\Facades\Storage::disk('public')->exists($this->selectedTrade->screenshot)
            ) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->selectedTrade->screenshot);
            }

            // 5. Actualizar Base de Datos
            $this->selectedTrade->update(['screenshot' => $path]);

            // 6. Recargar solo lo necesario
            $this->selectedTrade = Trade::query()
                ->with([
                    'tradeAsset:id,name,symbol',
                    'account:id,name',
                ])
                ->select([
                    'id',
                    'account_id',
                    'trade_asset_id',
                    'direction',
                    'entry_price',
                    'exit_price',
                    'size',
                    'pnl',
                    'pnl_percentage',
                    'duration_minutes',
                    'entry_time',
                    'exit_time',
                    'notes',
                    'screenshot',
                    'chart_data_path',
                    'ai_analysis',
                    'mae_price',
                    'mfe_price',
                ])
                ->find($this->selectedTrade->id);


            // 7. Actualizar variable primitiva para Alpine
            $this->currentScreenshot = $path;

            // 8. Limpiar el input temporal
            $this->reset('uploadedScreenshot');

            // 9. Notificar al frontend
            $this->dispatch('screenshot-updated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación (archivo muy grande, formato incorrecto)
            $this->dispatch('notify', $e->validator->errors()->first());
        } catch (\Exception $e) {
            $this->logError($e, 'UploadScreenshot', 'DashboardPage', 'Error al subir captura de pantalla');
            $this->dispatch('notify', __('labels.screenshot_upload_failed'));
            $this->reset('uploadedScreenshot');
        }
    }


    public function saveNotes()
    {
        try {
            if (!$this->selectedTrade) {
                $this->dispatch('notify', __('labels.no_trade_selected'));
                return;
            }

            $this->isSavingNotes = true;

            $this->selectedTrade->update([
                'notes' => $this->notes
            ]);

            // Despachar evento para actualizar dashboard si es necesario
            $this->dispatch('trade-updated');

            // Feedback visual breve
            usleep(200000); // 0.2 segundos

        } catch (Exception $e) {
            $this->logError($e, 'SaveNotes', 'DashboardPage', 'Error al guardar notas del trade');
            $this->dispatch('notify', __('labels.notes_save_failed'));
        } finally {
            $this->isSavingNotes = false;
        }
    }



    public function analyzeIndividualTrade()
    {
        try {
            // 1. Validaciones previas
            if (!$this->selectedTrade) {
                $this->dispatch('notify', __('labels.no_trade_selected'));
                return;
            }

            // ----------------------------------------------------
            // 2. VALIDACIÓN DE LÍMITE (NUEVO)
            // ----------------------------------------------------
            if (!$this->checkAiLimit()) {
                $this->isAnalyzingTrade = false; // Apagar spinner
                $this->dispatch('notify', __('labels.limit_ai_reached'));
                return; // Detener ejecución
            }

            $apiKey = env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                $this->dispatch('notify', __('labels.gemini_api_key_missing'));
                return;
            }

            $this->isAnalyzingTrade = true;
            $trade = $this->selectedTrade;

            // 2. Preparar el contexto textual (traducido)
            $contextoDatos = "
" . __('ai.labels.asset') . ": {$trade->tradeAsset->name}
" . __('ai.labels.type') . ": " . strtoupper($trade->direction) . "
" . __('ai.labels.entry') . ": {$trade->entry_price} | " . __('ai.labels.exit') . ": {$trade->exit_price}
" . __('ai.labels.result') . ": {$trade->pnl} (Lots: {$trade->size})
" . __('ai.labels.duration') . ": {$trade->duration_minutes} min
" . __('ai.labels.efficiency') . ": MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}
        ";

            // 3. Obtener el prompt traducido
            $prompt = __('ai.audit_prompt', ['context' => $contextoDatos]);

            // 4. Preparar el payload
            $parts = [
                ['text' => $prompt]
            ];

            // 5. Añadir imagen SI EXISTE y es válida
            if ($trade->screenshot && \Illuminate\Support\Facades\Storage::disk('public')->exists($trade->screenshot)) {
                try {
                    // Validar tamaño de la imagen (máximo 4MB para Gemini)
                    $fileSize = \Illuminate\Support\Facades\Storage::disk('public')->size($trade->screenshot);
                    if ($fileSize > 4 * 1024 * 1024) {
                        // Imagen muy grande, analizar solo con texto
                        $this->dispatch('notify', __('labels.screenshot_too_large'));
                    } else {
                        $imageContent = \Illuminate\Support\Facades\Storage::disk('public')->get($trade->screenshot);
                        $base64Image = base64_encode($imageContent);

                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => 'image/png',
                                'data' => $base64Image
                            ]
                        ];
                    }
                } catch (\Exception $e) {
                    // Si falla la carga de imagen, continuamos solo con texto
                    $this->logError($e, 'LoadScreenshot', 'DashboardPage', 'Error al cargar screenshot para análisis IA');
                }
            }

            // 6. Petición a Gemini con timeout
            $response = Http::timeout(20) // Más tiempo porque envía imagen
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['parts' => $parts]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                    ],
                ]);

            if ($response->successful()) {
                $analysisText = $response->json()['candidates'][0]['content']['parts'][0]['text'];

                // Guardar en BD
                $trade->update(['ai_analysis' => $analysisText]);

                // ----------------------------------------------------
                // 2. CONSUMIR CRÉDITO (NUEVO)
                // Solo restamos si la IA respondió bien.
                // ----------------------------------------------------
                $this->consumeAiCredit();

                // Actualizar la propiedad local
                $this->selectedTrade->ai_analysis = $analysisText;
            } else {
                $this->logError(
                    new \Exception('Gemini API Error: ' . $response->body()),
                    'AnalyzeIndividualTrade',
                    'DashboardPage',
                    'Error en respuesta de Gemini al analizar trade individual'
                );
                $this->dispatch('notify', __('labels.coach_IA_not_available'));
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->logError($e, 'AnalyzeIndividualTrade', 'DashboardPage', 'Timeout o error de conexión con Gemini');
            $this->dispatch('notify', __('labels.coach_IA_timeout'));
        } catch (\Exception $e) {
            $this->logError($e, 'AnalyzeIndividualTrade', 'DashboardPage', 'Error general al analizar trade individual');
            $this->dispatch('notify', __('labels.coach_IA_error'));
        } finally {
            $this->isAnalyzingTrade = false;
        }
    }


    public function openTradeFromNotes($tradeId)
    {
        try {
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('notify', __('labels.invalid_trade_id'));
                return;
            }

            // 👇 OPTIMIZACIÓN: Query ligera solo para IDs (sin relaciones)
            $ids = $this->getTradesQuery()
                ->whereNotNull('notes')
                ->where('notes', '!=', '')
                ->orderBy('exit_time', 'desc')
                ->take(4)
                ->pluck('id')
                ->toArray();

            if (!in_array($tradeId, $ids)) {
                $this->dispatch('notify', __('labels.trade_not_in_list'));
                return;
            }

            $this->dispatch(
                'open-trade-detail',
                tradeId: $tradeId,
                tradeIds: $ids
            );
        } catch (\Exception $e) {
            $this->logError($e, 'OpenTradeFromNotes', 'DashboardPage', "Error al abrir trade desde notas: {$tradeId}");
            $this->dispatch('notify', __('labels.error_opening_trade'));
        }
    }


    public function openTradeFromTable($tradeId)
    {
        try {
            // 1. Validar ID
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('notify', __('labels.invalid_trade_id'));
                return;
            }

            // 2. Obtener los IDs de la tabla de trades recientes
            $ids = $this->recentTrades->pluck('id')->toArray();

            // 3. Validar que el trade está en la lista
            if (!in_array($tradeId, $ids)) {
                $this->dispatch('notify', __('labels.trade_not_in_list'));
                return;
            }

            // 4. Despachar evento
            $this->dispatch(
                'open-trade-detail',
                tradeId: $tradeId,
                tradeIds: $ids
            );
        } catch (\Exception $e) {
            $this->logError($e, 'OpenTradeFromTable', 'DashboardPage', "Error al abrir trade desde tabla: {$tradeId}");
            $this->dispatch('notify', __('labels.error_opening_trade'));
        }
    }

    /**
     * Se ejecuta automáticamente cuando TradeDetailModal despacha 'trade-updated'
     */
    public function refreshRecentNotes()
    {
        try {

            // Recargar solo las notas recientes
            $this->loadRecentNotes();

            // Opcional: Notificar al usuario (si tienes sistema de toast)
            // $this->dispatch('notify', __('labels.notes_updated'));

        } catch (Exception $e) {
            $this->logError($e, 'RefreshRecentNotes', 'DashboardPage', 'Error al refrescar notas tras actualización');
        }
    }

    private function loadRecentNotes()
    {
        try {
            $this->recentNotes = $this->getTradesQuery()
                ->whereNotNull('notes')
                ->where('notes', '!=', '')
                ->with('tradeAsset:id,name,symbol')
                ->select([
                    'id',
                    'trade_asset_id',
                    'exit_time',
                    'notes',
                    'direction',
                    'pnl',
                ])
                ->orderBy('exit_time', 'desc')
                ->take(4)
                ->get();
        } catch (\Exception $e) {
            $this->logError($e, 'LoadRecentNotes', 'DashboardPage', 'Error al cargar notas recientes');
            $this->recentNotes = collect([]);
        }
    }






    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
