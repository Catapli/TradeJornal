<?php

namespace App\Livewire;

use App\Actions\Dashboard\CalculateDashboardMetrics;
use App\Actions\Dashboard\DashboardTradeQuery;
use App\Actions\Mistakes\CalculateMistakeCost;
use App\Actions\Trades\BuildTradeAuditContext;
use App\LogActions;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Trade;
use App\Services\AiService;
use App\Services\TradingRulesService;
use App\WithAiLimits;
use Carbon\Carbon;
use Exception; // <--- Importamos el servicio
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class DashboardPage extends Component
{
    // <--- 2. Usar el Trait
    use LogActions;
    use WithAiLimits;

    // ? Variables Nuevas
    // #[Session]: el filtro de cuentas persiste entre recargas y navegación (por usuario).
    #[Session]
    public $selectedAccounts = []; // Aquí se guardarán los IDs (ej: [1, 5, 8])

    public $availableAccounts = [];

    // Datos para el gráfico
    public $winRateChartData = [];

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

    public $evolutionChartData = [];

    public $dailyPnLChartData = [];

    // PROPIEDADES PARA LA IA
    public $aiAnalysis = null;

    public $isAnalyzing = false;

    // Propiedades para el Journal
    // PROPIEDADES PÚBLICAS
    public $journalContent = '';

    public $journalMood = null;

    public $tags = [];

    public $planStatus = null;

    // 1. Añade esto a las propiedades públicas
    public $heatmapData = [];

    // KPIs extra y comparativa (calculados en calculateStats)
    public $extraKpis = [];

    public $comparison = null;

    public $assetBreakdown = [];

    // 👇 NUEVAS PROPIEDADES PRIVADAS (no se envían al navegador)
    private $_recentTradesCache = null;

    // Rango de fechas (solo se aplican juntas). Persisten igual que el filtro de cuentas.
    #[Session]
    public string $dateFrom = '';

    #[Session]
    public string $dateTo = '';

    // 👇 NUEVO: Listener para cuando se actualiza un trade
    protected $listeners = [
        'trade-updated' => 'refreshRecentNotes',
    ];

    /**
     * Qué etiqueta le toca a una cuenta en el selector: 'archived', 'burned' o
     * cadena vacía si está viva. El orden importa: una cuenta archivada podría
     * estar además quemada, y lo que explica por qué no sale por defecto es que
     * está archivada.
     */
    private function accountBadge(Account $account): string
    {
        if ($account->trashed()) {
            return 'archived';
        }

        return $account->status === 'burned' ? 'burned' : '';
    }

    public function mount()
    {
        try {
            // El selector ofrece TODAS las cuentas del usuario, incluidas las
            // quemadas y las archivadas: una cuenta muerta guarda meses de
            // historial que sigue siendo tuyo y que a veces es justo el que hay
            // que mirar. Lo que no cambia es el valor por defecto: «Todas»
            // significa las activas, así que abrir el panel enseña lo de siempre.
            $this->availableAccounts = Account::withTrashed()
                ->where('user_id', Auth::id())
                ->get()
                ->sortBy([
                    // Las vivas primero; dentro de cada grupo, por nombre.
                    fn ($a, $b) => $this->accountBadge($a) <=> $this->accountBadge($b),
                    fn ($a, $b) => strcasecmp($a->name, $b->name),
                ])
                ->map(function ($acc) {
                    return [
                        'id' => $acc->id,
                        'name' => $acc->name,
                        'subtext' => $acc->login . ' (' . $acc->broker_name . ')',
                        // Null en las activas: el badge solo aparece cuando dice algo.
                        'badge' => $this->accountBadge($acc)
                            ? __('labels.account_badge_' . $this->accountBadge($acc))
                            : null,
                    ];
                })
                ->values();

            // Saneamos el filtro restaurado de sesión: descartamos IDs de cuentas
            // que ya no existan (si no, el dashboard saldría vacío sin
            // explicación). Si no queda ninguna válida, volvemos a 'all'.
            $validIds = $this->availableAccounts->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $this->selectedAccounts = collect($this->selectedAccounts)
                ->filter(fn ($id) => $id === 'all' || in_array((string) $id, $validIds, true))
                ->values()
                ->all();

            if (empty($this->selectedAccounts)) {
                $this->selectedAccounts = ['all'];
            }

            $this->calendarDate = Carbon::now()->format('Y-m-d');
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
            $this->planStatus = [];
        }
    }

    public function applyDateRange(string $from, string $to): void
    {
        try {
            // Validar que ambas fechas sean válidas y coherentes
            $parsedFrom = Carbon::parse($from)->startOfDay();
            $parsedTo = Carbon::parse($to)->endOfDay();

            if ($parsedFrom->gt($parsedTo)) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_date_range')]);

                return;
            }

            $this->dateFrom = $parsedFrom->format('Y-m-d');
            $this->dateTo = $parsedTo->format('Y-m-d');

            $this->_recentTradesCache = null;
            $this->calculateStats();
            $this->dispatch('dashboard-updated');
        } catch (Exception $e) {
            $this->logError($e, 'ApplyDateRange', 'DashboardPage', 'Error al aplicar rango de fechas');
        }
    }

    public function clearDateRange(): void
    {
        try {
            $this->dateFrom = '';
            $this->dateTo = '';
            $this->_recentTradesCache = null;
            $this->calculateStats();
            $this->dispatch('dashboard-updated');
        } catch (Exception $e) {
            $this->logError($e, 'ClearDateRange', 'DashboardPage', 'Error al limpiar rango de fechas');
        }
    }

    /** Los filtros activos (cuentas + rango) como objeto de consulta reutilizable. */
    private function tradeFilters(): DashboardTradeQuery
    {
        return new DashboardTradeQuery($this->selectedAccounts, $this->dateFrom, $this->dateTo);
    }

    public function getTradesQuery()
    {
        return $this->tradeFilters()->filtered();
    }

    /**
     * Recalcula todos los KPIs del dashboard.
     *
     * El calculo vivia aqui: 398 lineas en ocho metodos privados que obligaban a
     * arrancar Livewire entero para probar una media. Ahora es una Action pura.
     */
    private function calculateStats(): void
    {
        $metrics = app(CalculateDashboardMetrics::class)->execute($this->tradeFilters());

        $this->winRateChartData = $metrics['winRateChartData'];
        $this->pnlTotal = $metrics['pnlTotal'];
        $this->pnlTotal_perc = $metrics['pnlTotal_perc'];
        $this->avgPnLChartData = $metrics['avgPnLChartData'];
        $this->extraKpis = $metrics['extraKpis'];
        $this->comparison = $metrics['comparison'];
        $this->assetBreakdown = $metrics['assetBreakdown'];
        $this->dailyWinLossData = $metrics['dailyWinLossData'];
        $this->evolutionChartData = $metrics['evolutionChartData'];
        $this->dailyPnLChartData = $metrics['dailyPnLChartData'];
        $this->heatmapData = $metrics['heatmapData'];

        // El estado del plan no es un KPI: lo resuelve su propio servicio.
        try {
            $this->planStatus = app(TradingRulesService::class)->checkDashboardStatus($this->selectedAccounts);
        } catch (Exception $e) {
            $this->logError($e, 'CalculatePlanStatus', 'DashboardPage', 'Error al calcular plan status');
            $this->planStatus = [];
        }
    }

    /**
     * Trades del día seleccionado en el modal. Computed: se cachea por request
     * y NO viaja en el estado Livewire (antes era una colección de modelos
     * que se rehidrataba con N+1 en cada interacción del modal).
     */
    #[Computed]
    public function dayTrades()
    {
        if (!$this->selectedDate) {
            return collect();
        }

        return $this->getTradesQuery()
            ->whereDate('exit_time', $this->selectedDate)
            ->with([
                // Sin withTrashed, la relación de una cuenta archivada llega a
                // null y la fila se queda sin nombre de cuenta.
                'account' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'tradeAsset:id,name,symbol',
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
                // pnl_percentage o el conmutador de % pinta un 0 en cada fila:
                // el select explícito no la traía y el modelo la daba por nula.
                'pnl_percentage',
                'mae_price',
                'mfe_price',
                'notes',
                'screenshot',
                'duration_minutes',
            ])
            ->orderBy('exit_time', 'asc')
            ->get();
    }

    /**
     * Últimas 4 notas de trades. Computed por el mismo motivo que dayTrades.
     */
    /**
     * Lo que los errores marcados han costado en el periodo que se está mirando.
     *
     * Usa exactamente los mismos filtros que el resto del panel: si el número
     * saliera de otra consulta, portada y Laboratorio dirían cosas distintas
     * sobre las mismas operaciones.
     */
    #[Computed]
    public function mistakeCost(): array
    {
        return app(CalculateMistakeCost::class)->execute($this->getTradesQuery());
    }

    /** Al repasar una operación cambia el coste: se tira la caché del cálculo. */
    #[On('mistakes-reviewed')]
    public function refreshMistakeCost(): void
    {
        unset($this->mistakeCost);
    }

    #[Computed]
    public function recentNotes()
    {
        return $this->getTradesQuery()
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
                    'account' => fn ($q) => $q->withTrashed()->select('id', 'name'),
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
                    'pnl_percentage',
                    'mae_price',
                    'mfe_price',
                    'notes',
                    'screenshot',
                ])
                ->orderBy('exit_time', 'desc')
                ->take(10)
                ->get();

            return $this->_recentTradesCache;
        } catch (Exception $e) {
            $this->logError($e, 'GetRecentTrades', 'DashboardPage', 'Error al cargar operaciones recientes');

            return collect([]);
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
        } catch (Exception $e) {
            $this->logError($e, 'UpdatedSelectedAccounts', 'DashboardPage', 'Error al cambiar filtro de cuentas');

            // Restaurar a 'all' como fallback
            $this->selectedAccounts = ['all'];

            // Intentar cargar con 'all' de nuevo
            try {
                $this->calculateStats();
                $this->generateCalendar();
                $this->dispatch('dashboard-updated');
            } catch (Exception $retryException) {
                // Si falla incluso con 'all', loguear y mostrar valores vacíos
                $this->logError($retryException, 'UpdatedSelectedAccountsRetry', 'DashboardPage', 'Error al reintentar con todas las cuentas');
                $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_loading_accounts')]);
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
            $journals = JournalEntry::where('user_id', Auth::id())
                ->whereBetween('date', [$startOfCalendar, $endOfCalendar])
                ->get()
                ->keyBy(fn ($j) => $j->date->format('Y-m-d'));

            // 4. Construir el Grid
            $grid = [];
            $currentDay = $startOfCalendar->copy();

            while ($currentDay <= $endOfCalendar) {
                $dayString = $currentDay->format('Y-m-d');
                $dayData = $trades->get($dayString);
                $pnl = $dayData ? $dayData->daily_pnl : null;
                $percentage = $dayData ? $dayData->daily_percent : null;

                $journalData = $journals->get($dayString);

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

    public function analyzeDayWithAi(AiService $ai, BuildTradeAuditContext $context)
    {
        try {
            // La API key ya la valida AiService::complete(): un solo sitio para todos.
            // ----------------------------------------------------
            // VALIDACIÓN DE LÍMITE
            // ----------------------------------------------------
            if (!$this->checkAiLimit()) {
                $this->isAnalyzing = false; // Apagar spinner

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
                ->map(function ($trade) use ($context) {
                    $hora = \Carbon\Carbon::parse($trade->exit_time)->format('H:i');
                    $tipo = strtoupper($trade->direction);
                    $simbolo = $trade->tradeAsset->name ?? $trade->tradeAsset->symbol ?? 'N/A';

                    // En pips, no en precios crudos: el modelo se equivocaba al derivarlos.
                    $excursion = $context->excursionSummary($trade);

                    return "- [{$hora}] {$simbolo} ({$tipo}) | " . __('labels.lots') . " {$trade->size} | PnL: {$trade->pnl} $"
                        . ($excursion === null ? '' : " | {$excursion}");
                })->join("\n");

            // 5. El Prompt
            // 5. El Prompt (traducido al idioma del usuario)
            $prompt = __('ai.session_prompt', ['trades_text' => $tradesText]);

            // 6. Petición a Groq (cacheada por usuario + día; mismos datos no repiten llamada)
            $result = $ai->complete(
                $prompt,
                temperature: 0.4,
                maxTokens: 2048,
                cacheKey: 'session:' . Auth::id() . ':' . $this->selectedDate,
            );

            if ($result->ok) {
                $this->aiAnalysis = $result->content;

                // Solo restamos crédito si hubo llamada real a la IA
                if (!$result->fromCache) {
                    $this->consumeAiCredit();
                }
            } else {
                $this->aiAnalysis = $result->userMessage();
            }
        } catch (\Exception $e) {
            // Cualquier otro error
            $this->logError($e, 'AnalyzeDayWithAi', 'DashboardPage', 'Error general al analizar día con IA');
            $this->aiAnalysis = __('labels.coach_IA_error');
        } finally {
            // IMPORTANTE: Siempre desactivar el loading, pase lo que pase
            $this->isAnalyzing = false;
        }
    }

    public function openDayDetails($date)
    {
        try {
            if (!$date || !strtotime($date)) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_date')]);

                return;
            }

            // El computed dayTrades depende de selectedDate: invalidar al cambiar de día
            if ($this->selectedDate !== $date) {
                unset($this->dayTrades);
            }

            $this->selectedDate = $date;
            $this->showDayModal = true;
        } catch (\Exception $e) {
            $this->logError($e, 'OpenDayDetails', 'DashboardPage', "Error al abrir detalles del día: {$date}");
            $this->showDayModal = true;
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_loading_day_details')]);
        }
    }

    public function closeDayModal()
    {
        $this->showDayModal = false;
        $this->selectedDate = null;
        $this->aiAnalysis = null;
        unset($this->dayTrades);
    }

    /**
     * Abre el detalle de un trade del día en el componente global TradeDetailModal.
     *
     * Antes este método cargaba el modelo entero en $selectedTrade y el dashboard
     * pintaba su propio panel de detalle: 493 líneas de Blade y cuatro métodos que
     * duplicaban TradeDetailModal. Las dos copias acabaron divergiendo y el mismo
     * trade daba veredictos de IA distintos según desde dónde se abriera.
     */
    public function selectTrade($tradeId)
    {
        try {
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_trade_id')]);

                return;
            }

            $ids = collect($this->dayTrades)->pluck('id')->map(fn ($id) => (int) $id)->toArray();

            // El trade pedido tiene que estar en el día que se está viendo.
            if (!in_array((int) $tradeId, $ids, strict: true)) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.trade_not_in_list')]);

                return;
            }

            $this->dispatch('open-trade-detail', tradeId: (int) $tradeId, tradeIds: $ids);
        } catch (Exception $e) {
            $this->logError($e, 'SelectTrade', 'DashboardPage', "Error al seleccionar trade ID: {$tradeId}");
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_loading_trade')]);
        }
    }

    public function openTradeFromNotes($tradeId)
    {
        try {
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_trade_id')]);

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
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.trade_not_in_list')]);

                return;
            }

            $this->dispatch(
                'open-trade-detail',
                tradeId: $tradeId,
                tradeIds: $ids
            );
        } catch (\Exception $e) {
            $this->logError($e, 'OpenTradeFromNotes', 'DashboardPage', "Error al abrir trade desde notas: {$tradeId}");
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_opening_trade')]);
        }
    }

    public function openTradeFromTable($tradeId)
    {
        try {
            // 1. Validar ID
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_trade_id')]);

                return;
            }

            // 2. Obtener los IDs de la tabla de trades recientes
            $ids = $this->recentTrades->pluck('id')->toArray();

            // 3. Validar que el trade está en la lista
            if (!in_array($tradeId, $ids)) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.trade_not_in_list')]);

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
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_opening_trade')]);
        }
    }

    /**
     * Se ejecuta automáticamente cuando TradeDetailModal despacha 'trade-updated'
     */
    public function refreshRecentNotes()
    {
        // Invalida el computed: la próxima lectura recarga las notas
        unset($this->recentNotes);
    }

    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
