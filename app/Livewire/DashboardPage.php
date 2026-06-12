<?php

namespace App\Livewire;

use App\LogActions;
use App\Models\Account;
use App\Models\Alert;
use App\Models\JournalEntry;
use App\Models\Trade;
use App\Models\Traffic;
use App\Services\StorageService;
use Carbon\Carbon;
use App\Services\AiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\TradingRulesService; // <--- Importamos el servicio
use App\WithAiLimits;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

class DashboardPage extends Component
{

    protected StorageService $storage;
    use WithFileUploads; // <--- IMPORTANTE: Usar el Trait
    use WithAiLimits; // <--- 2. Usar el Trait
    use LogActions;
    // ? Variables Nuevas
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

    public $selectedTrade = null;

    // PROPIEDADES PARA LA IA
    public $aiAnalysis = null;
    public $isAnalyzing = false;
    public $isAnalyzingTrade = false; // Spinner específico para el trade individual

    // Propiedades para el Journal
    // PROPIEDADES PÚBLICAS
    public $journalContent = '';
    public $journalMood = null;
    public $tags = [];

    // NUEVO: Propiedad para editar la nota
    public $notes = '';
    public $isSavingNotes = false;
    public $planStatus = null;

    // 1. Añade esto a las propiedades públicas
    public $heatmapData = [];

    // KPIs extra y comparativa (calculados en calculateStats)
    public $extraKpis = [];
    public $comparison = null;
    public $assetBreakdown = [];

    // NUEVO: Propiedad para la subida de imagen temporal
    public $uploadedScreenshot;

    // NUEVO: Variable primitiva para controlar la vista de la imagen
    public $currentScreenshot = null;

    // 👇 NUEVAS PROPIEDADES PRIVADAS (no se envían al navegador)
    private $_recentTradesCache = null;

    // Rango de fechas (solo se aplican juntas)
    public string $dateFrom = '';
    public string $dateTo   = '';

    // 👇 NUEVO: Listener para cuando se actualiza un trade
    protected $listeners = [
        'trade-updated' => 'refreshRecentNotes'
    ];


    public function boot(StorageService $storage): void
    {
        $this->storage = $storage;
    }

    #[Computed]
    public function screenshotUrl(): ?string
    {
        if (!$this->currentScreenshot) return null;
        return $this->storage->temporaryUrl($this->currentScreenshot, 30);
    }

    public function mount()
    {
        try {
            // 👇 SIN CACHÉ - Query directa (versión original)
            $this->availableAccounts = Account::where('user_id', Auth::id())
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
            $this->planStatus = [];
        }
    }

    public function applyDateRange(string $from, string $to): void
    {
        try {
            // Validar que ambas fechas sean válidas y coherentes
            $parsedFrom = Carbon::parse($from)->startOfDay();
            $parsedTo   = Carbon::parse($to)->endOfDay();

            if ($parsedFrom->gt($parsedTo)) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_date_range')]);
                return;
            }

            $this->dateFrom = $parsedFrom->format('Y-m-d');
            $this->dateTo   = $parsedTo->format('Y-m-d');

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
            $this->dateTo   = '';
            $this->_recentTradesCache = null;
            $this->calculateStats();
            $this->dispatch('dashboard-updated');
        } catch (Exception $e) {
            $this->logError($e, 'ClearDateRange', 'DashboardPage', 'Error al limpiar rango de fechas');
        }
    }




    /**
     * Query base: filtros de usuario y cuentas, SIN rango de fechas.
     * La usa también la comparativa con el periodo anterior.
     */
    private function getBaseTradesQuery()
    {
        $query = Trade::query();

        // 1. Si hay cuentas específicas seleccionadas (y no es 'all')
        if (!in_array('all', $this->selectedAccounts) && count($this->selectedAccounts) > 0) {
            $query->whereIn('account_id', $this->selectedAccounts);
        }

        // 2. Filtro de seguridad por usuario, excluyendo cuentas quemadas (igual que el mount)
        return $query->forUserActiveAccounts(Auth::id());
    }

    public function getTradesQuery()
    {
        $query = $this->getBaseTradesQuery();

        // 👇 Filtro de rango — solo si AMBAS fechas están definidas
        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $query->whereBetween('exit_time', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);
        }

        return $query;
    }

    private function calculateStats()
    {
        // --- 1. KPIs CONSOLIDADOS: win rate + PnL total + medias + PF/expectancy en UNA sola query ---
        try {
            $stats = $this->getTradesQuery()->selectRaw('
            COUNT(*) as total_trades,
            SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
            COALESCE(SUM(pnl), 0) as total_pnl,
            COALESCE(SUM(pnl_percentage), 0) as total_pnl_perc,
            AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
            AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss,
            COALESCE(SUM(CASE WHEN pnl > 0 THEN pnl ELSE 0 END), 0) as gross_profit,
            COALESCE(SUM(CASE WHEN pnl < 0 THEN pnl ELSE 0 END), 0) as gross_loss,
            MAX(pnl) as best_trade,
            MIN(pnl) as worst_trade
        ')->first();

            $total = (int) ($stats->total_trades ?? 0);
            $wins = (int) ($stats->winning_trades ?? 0);
            $losses = $total - $wins;
            $winRate = $total > 0 ? round(($wins / $total) * 100, 2) : 0;

            $this->winRateChartData = [
                'series' => [$wins, $losses],
                'rate' => $winRate,
                'count_wins' => $wins,
                'count_losses' => $losses
            ];

            $this->pnlTotal = $stats->total_pnl;
            $this->pnlTotal_perc = $stats->total_pnl_perc;

            $avgWin = $stats->avg_win ? round($stats->avg_win, 2) : 0;
            $avgLoss = $stats->avg_loss ? round($stats->avg_loss, 2) : 0;
            $rrRatio = ($avgLoss != 0) ? abs($avgWin / $avgLoss) : 0;

            $this->avgPnLChartData = [
                'avg_win' => $avgWin,
                'avg_loss' => $avgLoss,
                'rr_ratio' => round($rrRatio, 2)
            ];

            // --- KPIs EXTRA: Profit Factor, Expectancy y racha actual ---
            $grossLossAbs = abs((float) $stats->gross_loss);
            $profitFactor = $grossLossAbs > 0
                ? round((float) $stats->gross_profit / $grossLossAbs, 2)
                : ((float) $stats->gross_profit > 0 ? null : 0); // null = ∞ (sin pérdidas)

            $expectancy = $total > 0
                ? round((($wins / $total) * $avgWin) + ((($total - $wins) / $total) * $avgLoss), 2)
                : 0;

            $this->extraKpis = [
                'profit_factor' => $profitFactor,
                'expectancy' => $expectancy,
                'streak' => $this->calculateCurrentStreak(),
                'best_trade' => $stats->best_trade !== null ? round((float) $stats->best_trade, 2) : null,
                'worst_trade' => $stats->worst_trade !== null ? round((float) $stats->worst_trade, 2) : null,
            ];
        } catch (Exception $e) {
            $this->logError($e, 'CalculateKpis', 'DashboardPage', 'Error al calcular KPIs consolidados');
            $this->winRateChartData = ['series' => [0, 0], 'rate' => 0, 'count_wins' => 0, 'count_losses' => 0];
            $this->pnlTotal = 0;
            $this->pnlTotal_perc = 0;
            $this->avgPnLChartData = ['avg_win' => 0, 'avg_loss' => 0, 'rr_ratio' => 0];
            $this->extraKpis = ['profit_factor' => 0, 'expectancy' => 0, 'streak' => ['type' => null, 'count' => 0], 'best_trade' => null, 'worst_trade' => null];
        }

        // --- 2. COMPARATIVA CON EL PERIODO ANTERIOR (solo si hay rango activo) ---
        try {
            $this->calculateComparison();
        } catch (Exception $e) {
            $this->logError($e, 'CalculateComparison', 'DashboardPage', 'Error al calcular comparativa de periodo');
            $this->comparison = null;
        }

        // --- 3. TOP / PEORES ACTIVOS DEL PERIODO ---
        try {
            $this->calculateAssetBreakdown();
        } catch (Exception $e) {
            $this->logError($e, 'CalculateAssetBreakdown', 'DashboardPage', 'Error al calcular desglose por activo');
            $this->assetBreakdown = [];
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

        // 5+6. EVOLUCIÓN + BARRAS DIARIAS (comparten una sola query agrupada por día en SQL)
        try {
            $dailyPnl = $this->getDailyPnlByExitDate();
            $this->calculateEvolution($dailyPnl);
            $this->calculateDailyBars($dailyPnl);
        } catch (Exception $e) {
            $this->logError($e, 'CalculateDailyCharts', 'DashboardPage', 'Error al calcular gráficos diarios');
            $this->evolutionChartData = ['categories' => [], 'data' => [], 'is_positive' => true];
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

    /**
     * Racha actual de wins/losses consecutivos (desde el trade más reciente hacia atrás).
     * Solo mira los últimos 50 trades del filtro activo: suficiente y barato.
     */
    private function calculateCurrentStreak(): array
    {
        $pnls = $this->getTradesQuery()
            ->whereNotNull('exit_time')
            ->orderByDesc('exit_time')
            ->limit(50)
            ->pluck('pnl');

        $type = null;
        $count = 0;

        foreach ($pnls as $pnl) {
            $sign = $pnl > 0 ? 'win' : ($pnl < 0 ? 'loss' : null);
            if ($sign === null) break; // break-even corta la racha

            if ($type === null) {
                $type = $sign;
                $count = 1;
            } elseif ($sign === $type) {
                $count++;
            } else {
                break;
            }
        }

        return ['type' => $type, 'count' => $count];
    }

    /**
     * PnL y win rate del periodo equivalente anterior (misma duración, justo antes).
     * Solo aplica cuando hay rango de fechas activo.
     */
    private function calculateComparison(): void
    {
        $this->comparison = null;

        if (empty($this->dateFrom) || empty($this->dateTo)) {
            return;
        }

        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();
        $days = $from->diffInDays($to) + 1;

        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $prev = $this->getBaseTradesQuery()
            ->whereBetween('exit_time', [$prevFrom, $prevTo])
            ->selectRaw('
                COUNT(*) as total_trades,
                SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                COALESCE(SUM(pnl), 0) as total_pnl
            ')->first();

        $prevTotal = (int) ($prev->total_trades ?? 0);

        if ($prevTotal === 0) {
            return; // sin datos previos no hay comparativa honesta
        }

        $prevPnl = (float) $prev->total_pnl;
        $prevWr = round(((int) $prev->winning_trades / $prevTotal) * 100, 2);

        $this->comparison = [
            'prev_label' => $prevFrom->format('d/m') . ' → ' . $prevTo->format('d/m'),
            'pnl_prev' => round($prevPnl, 2),
            'pnl_diff' => round((float) $this->pnlTotal - $prevPnl, 2),
            'wr_prev' => $prevWr,
            'wr_diff' => round(($this->winRateChartData['rate'] ?? 0) - $prevWr, 2),
        ];
    }

    /**
     * Mejores y peores símbolos del periodo filtrado, por PnL acumulado.
     */
    private function calculateAssetBreakdown(): void
    {
        $rows = $this->getTradesQuery()
            ->join('trade_assets', 'trade_assets.id', '=', 'trades.trade_asset_id')
            ->selectRaw('trade_assets.name as asset, COUNT(*) as trades_count, COALESCE(SUM(trades.pnl), 0) as total_pnl')
            ->groupBy('trade_assets.name')
            ->orderByDesc('total_pnl')
            ->get();

        if ($rows->isEmpty()) {
            $this->assetBreakdown = [];
            return;
        }

        $this->assetBreakdown = [
            'top' => $rows->take(3)->map(fn ($r) => [
                'asset' => $r->asset,
                'trades' => (int) $r->trades_count,
                'pnl' => round((float) $r->total_pnl, 2),
            ])->values()->toArray(),
            'worst' => $rows->reverse()->take(3)
                ->filter(fn ($r) => (float) $r->total_pnl < 0)
                ->map(fn ($r) => [
                    'asset' => $r->asset,
                    'trades' => (int) $r->trades_count,
                    'pnl' => round((float) $r->total_pnl, 2),
                ])->values()->toArray(),
        ];
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
    }

    /**
     * Últimas 4 notas de trades. Computed por el mismo motivo que dayTrades.
     */
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
        } catch (Exception $e) {
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

            // Indexar por día-hora para lookup O(1) en vez de recorrer la colección 120 veces
            $statsByCell = $rawStats->keyBy(fn ($s) => $s->day_index . '-' . $s->hour);

            $chartData = [];
            foreach ($days as $index => $dayName) {
                $hourlyData = [];
                for ($h = 0; $h < 24; $h++) {
                    $stat = $statsByCell->get($index . '-' . $h);
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
        } catch (Exception $e) {
            $this->logError($e, 'CalculateHeatmap', 'DashboardPage', 'Error al calcular heatmap temporal');
            $this->heatmapData = [];
        }
    }




    /**
     * PnL agrupado por día de cierre, calculado en SQL.
     * Alimenta a la vez el gráfico de evolución y las barras diarias.
     */
    private function getDailyPnlByExitDate()
    {
        return $this->getTradesQuery()
            ->selectRaw('DATE(exit_time) as date, SUM(pnl) as daily_pnl')
            ->whereNotNull('exit_time')
            ->groupByRaw('DATE(exit_time)')
            ->orderBy('date', 'asc')
            ->get();
    }

    private function calculateEvolution($dailyPnl)
    {
        try {
            $labels = [__('labels.start_without_flag')];
            $data = [0];

            $runningTotal = 0;
            foreach ($dailyPnl as $day) {
                $runningTotal += $day->daily_pnl;
                $labels[] = $day->date;
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




    private function calculateDailyBars($dailyPnl)
    {
        try {
            $categories = [];
            $data = [];

            foreach ($dailyPnl as $day) {
                $categories[] = \Carbon\Carbon::parse($day->date)->translatedFormat('d M');
                $data[] = round($day->daily_pnl, 2);
            }

            $this->dailyPnLChartData = [
                'categories' => $categories,
                'data' => $data
            ];
        } catch (Exception $e) {
            $this->logError($e, 'CalculateDailyBars', 'DashboardPage', 'Error al calcular barras diarias de PnL');
            $this->dailyPnLChartData = ['categories' => [], 'data' => []];
        }
    }




    public function analyzeDayWithAi(AiService $ai)
    {
        try {
            // 1. Validar API Key
            if (!$ai->isConfigured()) {
                $this->aiAnalysis = __('labels.gemini_api_key_missing');
                $this->isAnalyzing = false;
                return;
            }

            // ----------------------------------------------------
            // 2. VALIDACIÓN DE LÍMITE (NUEVO)
            // ----------------------------------------------------
            if (!$this->checkAiLimit()) {
                $this->isAnalyzing = false; // Apagar spinner
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.limit_ai_reached')]);
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
            // 5. El Prompt (traducido al idioma del usuario)
            $prompt = __('ai.session_prompt', ['trades_text' => $tradesText]);



            // 6. Petición a Groq (cacheada por usuario + día; mismos datos no repiten llamada)
            $result = $ai->complete(
                $prompt,
                temperature: 0.4,
                maxTokens: 1024,
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
        $this->selectedTrade = null;
        $this->aiAnalysis = null;
        unset($this->dayTrades);
    }



    public function selectTrade($tradeId)
    {
        try {
            if (!is_numeric($tradeId) || $tradeId <= 0) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.invalid_trade_id')]);
                return;
            }

            $this->notes = '';
            $this->uploadedScreenshot = null;

            // 👇 OPTIMIZACIÓN: Eager Loading selectivo + SELECT específico
            $this->selectedTrade = Trade::query()
                ->forUser(Auth::id())
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
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.trade_not_found')]);
                return;
            }

            $this->notes = $this->selectedTrade->notes;
            $this->currentScreenshot = $this->selectedTrade->screenshot;

            // ✅ Presigned URL generada en PHP, nunca el path crudo
            $chartUrl = $this->selectedTrade->chart_data_path
                ? $this->storage->temporaryUrl($this->selectedTrade->chart_data_path, 60)
                : null;

            $this->dispatch(
                'trade-selected',
                path: $chartUrl,
                entry: $this->selectedTrade->entry_price,
                exit: $this->selectedTrade->exit_price,
                direction: $this->selectedTrade->direction
            );
        } catch (\Exception $e) {
            $this->logError($e, 'SelectTrade', 'DashboardPage', "Error al seleccionar trade ID: {$tradeId}");
            $this->selectedTrade = null;
            $this->notes = '';
            $this->currentScreenshot = null;
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_loading_trade')]);
        }
    }



    /**
     * NUEVO: Se ejecuta automáticamente cuando 'uploadedScreenshot' cambia
     * (es decir, cuando el usuario suelta el archivo en el input).
     */
    public function updatedUploadedScreenshot(): void
    {
        try {
            $this->validate([
                'uploadedScreenshot' => 'required|image|mimes:png,jpg,jpeg,webp|max:10240',
            ]);

            if (!$this->selectedTrade) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.notradeselected')]);
                $this->reset('uploadedScreenshot');
                return;
            }

            // Borrar screenshot anterior de R2
            if ($this->selectedTrade->screenshot) {
                $this->storage->delete($this->selectedTrade->screenshot);
            }

            // Guardar en R2 con path estandarizado
            $ext  = $this->uploadedScreenshot->getClientOriginalExtension() ?: 'png';
            $path = $this->storage->tradeScreenshotPath(
                Auth::id(),
                $this->selectedTrade->ticket,
                $ext
            );
            $this->storage->putFile($path, $this->uploadedScreenshot->readStream());

            $this->selectedTrade->update(['screenshot' => $path]);
            $this->currentScreenshot = $path;

            $this->reset('uploadedScreenshot');
            $this->dispatch('screenshot-updated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => $e->validator->errors()->first()]);
        } catch (\Throwable $e) {
            $this->logError($e, 'UploadScreenshot', 'DashboardPage', "Trade ID: {$this->selectedTrade?->id}");
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.screenshotuploadfailed')]);
            $this->reset('uploadedScreenshot');
        }
    }



    public function saveNotes()
    {
        try {
            if (!$this->selectedTrade) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.no_trade_selected')]);
                return;
            }

            $this->isSavingNotes = true;

            $this->selectedTrade->update([
                'notes' => $this->notes
            ]);

            // Despachar evento para actualizar dashboard si es necesario
            // (el feedback visual lo da wire:loading, sin dormir el servidor)
            $this->dispatch('trade-updated');
        } catch (Exception $e) {
            $this->logError($e, 'SaveNotes', 'DashboardPage', 'Error al guardar notas del trade');
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.notes_save_failed')]);
        } finally {
            $this->isSavingNotes = false;
        }
    }



    public function analyzeIndividualTrade(AiService $ai)
    {
        try {
            // 1. Validaciones previas
            if (!$this->selectedTrade) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.no_trade_selected')]);
                return;
            }

            // ----------------------------------------------------
            // 2. VALIDACIÓN DE LÍMITE (NUEVO)
            // ----------------------------------------------------
            if (!$this->checkAiLimit()) {
                $this->isAnalyzingTrade = false; // Apagar spinner
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.limit_ai_reached')]);
                return; // Detener ejecución
            }

            if (!$ai->isConfigured()) {
                $this->dispatch('show-alert', ['type' => 'warn', 'message' => __('labels.gemini_api_key_missing')]);
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

            // 4. Petición a Groq (cacheada por trade; mismos datos no repiten llamada)
            $result = $ai->complete(
                $prompt,
                temperature: 0.4,
                maxTokens: 1024,
                cacheKey: 'audit:' . $trade->id,
            );

            if ($result->ok) {
                // Guardar en BD
                $trade->update(['ai_analysis' => $result->content]);

                // Solo restamos crédito si hubo llamada real a la IA
                if (!$result->fromCache) {
                    $this->consumeAiCredit();
                }

                // Actualizar la propiedad local
                $this->selectedTrade->ai_analysis = $result->content;
            } else {
                $this->dispatch('show-alert', ['type' => 'error', 'message' => $result->userMessage()]);
            }
        } catch (\Exception $e) {
            $this->logError($e, 'AnalyzeIndividualTrade', 'DashboardPage', 'Error general al analizar trade individual');
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.coach_IA_error')]);
        } finally {
            $this->isAnalyzingTrade = false;
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
