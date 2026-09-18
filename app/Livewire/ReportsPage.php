<?php

namespace App\Livewire;

use App\Actions\Export\BuildMonthlyReport;
use App\Actions\Mistakes\CalculateMistakeCost;
use App\Concerns\RequiresProAccess;
use App\LogActions;
use App\Models\Account;
use App\Models\Trade;
use App\Services\Export\MonthlyReportPdf;
use App\Services\TradingAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportsPage extends Component
{
    use LogActions;
    use RequiresProAccess;

    // ============================================
    // PROPIEDADES PÚBLICAS (SOLO ESTADO)
    // ============================================

    public $accountId = 'all';

    /** Mes del informe mensual, en formato `YYYY-MM` (Fase 5 · P10). */
    public $reportMonth = '';

    public $scenarios = [
        'only_longs' => false,
        'only_shorts' => false,
        'remove_worst' => false,
        'max_daily_trades' => null,
        'exclude_days' => [],
        'fixed_sl' => null,
        'fixed_tp' => null,
        // Ids de errores cuyas operaciones se quitan de la curva simulada (P2).
        'exclude_mistakes' => [],
    ];

    // ============================================
    // LIFECYCLE HOOKS
    // ============================================

    /** El informe arranca en el último mes con operaciones, no en uno vacío. */
    public function mount(): void
    {
        $this->syncReportMonth();
    }

    public function updatedAccountId($value)
    {
        if ($value !== 'all') {
            $account = Account::where('id', $value)
                ->where('user_id', Auth::id())
                ->first();

            if (!$account) {
                $this->accountId = 'all';

                $this->insertLog(
                    action: 'Intento de acceso a cuenta no autorizada',
                    form: 'ReportsPage',
                    description: "Usuario intentó acceder a account_id: {$value}",
                    type: 'warning'
                );

                $this->dispatch('show-alert', [
                    'type' => 'error',
                    'message' => __('labels.account_not_found_lab'),
                ]);

                return;
            }
        }

        // Cada cuenta tiene sus propios meses con actividad: dejar el anterior
        // seleccionado ofrecería un mes que en esta cuenta está vacío.
        unset($this->reportMonths);
        $this->syncReportMonth();

        $this->insertLog(
            action: 'Cambio de cuenta en Laboratorio',
            form: 'ReportsPage',
            description: "Cambió a cuenta: {$value}"
        );
    }

    /** Deja `reportMonth` en un mes que exista; si no hay ninguno, en el actual. */
    private function syncReportMonth(): void
    {
        $meses = array_column($this->reportMonths, 'value');

        if (in_array($this->reportMonth, $meses, true)) {
            return;
        }

        $this->reportMonth = $meses[0] ?? CarbonImmutable::now(Auth::user()?->preferredTimezone())->format('Y-m');
    }

    public function updatedScenarios($value, $key)
    {
        $this->insertLog(
            action: 'Modificación de escenario',
            form: 'ReportsPage',
            description: "Escenario '{$key}' cambió a: " . json_encode($value)
        );
    }

    // ============================================
    // MÉTODOS PRIVADOS (HELPERS)
    // ============================================

    /**
     * Obtiene los trades del usuario con seguridad y EAGER LOADING COMPLETO
     *
     * ⚡ OPTIMIZACIÓN: Se cargan todas las relaciones necesarias en UNA sola query
     *
     * @return \Illuminate\Support\Collection
     */
    private function getTrades()
    {
        try {
            // DOBLE VALIDACIÓN de seguridad
            if ($this->accountId !== 'all') {
                $accountExists = Account::where('id', $this->accountId)
                    ->where('user_id', Auth::id())
                    ->exists();

                if (!$accountExists) {
                    $this->accountId = 'all';

                    $this->insertLog(
                        action: 'Bloqueo de acceso no autorizado',
                        form: 'ReportsPage',
                        description: 'Intento de obtener trades con cuenta no autorizada',
                        type: 'error'
                    );

                    $this->dispatch('show-alert', [
                        'type' => 'error',
                        'message' => __('labels.unexpected_error'),
                    ]);
                }
            }

            // ⚡ OPTIMIZACIÓN 1: EAGER LOADING COMPLETO
            // Cargamos TODAS las relaciones que necesitamos en una sola query
            $query = Trade::with([
                'mistakes',           // Para analyzeMistakes()
                'account',            // Para validaciones y balances
                'tradeAsset',         // Para símbolos (si usas $trade->tradeAsset->symbol)
                'strategy',           // Para filtros por estrategia (opcional)
            ])
                ->forUser()
                ->orderBy('entry_time', 'asc');

            // ⚡ OPTIMIZACIÓN 2: SELECT SOLO LOS CAMPOS NECESARIOS
            // Si no necesitas TODOS los campos, especifica solo los que usas
            // Esto reduce el payload de la query en ~30-40%
            $query->select([
                'id',
                'account_id',
                'trade_asset_id',
                'strategy_id',
                'trading_session_id',
                'ticket',
                'direction',
                'entry_price',
                'exit_price',
                'size',
                'pnl',
                'duration_minutes',
                'entry_time',
                'exit_time',
                'mae_price',
                'mfe_price',
                'notes',
                // Sin esta columna el Laboratorio daba una cobertura distinta a
                // la de la portada sobre exactamente las mismas operaciones.
                'mistakes_reviewed_at',
            ]);

            if ($this->accountId !== 'all') {
                $query->where('account_id', $this->accountId);
            }

            return $query->get();
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error al obtener trades',
                form: 'ReportsPage',
                description: 'Fallo en getTrades() - accountId: ' . $this->accountId
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_loading_data'),
            ]);

            return collect();
        }
    }

    /**
     * Helper para obtener el balance actual con seguridad
     *
     * @return float
     */
    private function getCurrentBalance()
    {
        try {
            $balance = 0;

            if ($this->accountId !== 'all') {
                $account = Account::where('id', $this->accountId)
                    ->where('user_id', Auth::id())
                    ->first();

                $balance = $account ? $account->current_balance : 10000;
            } else {
                $balance = Account::where('user_id', Auth::id())->sum('current_balance');
            }

            if ($balance <= 0) {
                $balance = 10000;
            }

            return $balance;
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error al obtener balance',
                form: 'ReportsPage',
                description: 'Fallo en getCurrentBalance()'
            );

            $this->dispatch('show-alert', [
                'type' => 'warning',
                'message' => __('labels.error_obtaining_balance'),
            ]);

            return 10000;
        }
    }

    /**
     * Helper para verificar si hay escenarios activos
     *
     * @return bool
     */
    private function hasActiveScenarios()
    {
        return in_array(true, [
            $this->scenarios['only_longs'],
            $this->scenarios['only_shorts'],
            $this->scenarios['remove_worst'],
        ]) || !empty($this->scenarios['max_daily_trades'])
            || !empty($this->scenarios['fixed_sl'])
            || !empty($this->scenarios['fixed_tp'])
            || !empty($this->scenarios['exclude_days'])
            || !empty($this->scenarios['exclude_mistakes']);
    }

    // ============================================
    // COMPUTED PROPERTIES (CON GESTIÓN DE ERRORES)
    // ============================================

    #[Computed]
    public function allTrades()
    {
        return $this->getTrades();
    }

    #[Computed]
    public function realCurve()
    {
        try {
            if ($this->allTrades->isEmpty()) {
                return [];
            }

            return app(TradingAnalysisService::class)->calculateEquityCurve($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en realCurve',
                form: 'ReportsPage',
                description: 'Fallo al calcular curva de capital real'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generating_curve'),
            ]);

            return [];
        }
    }

    #[Computed]
    public function realStats()
    {
        try {
            if ($this->allTrades->count() < 5) {
                return null;
            }

            return app(TradingAnalysisService::class)->calculateSystemHealth($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en realStats',
                form: 'ReportsPage',
                description: 'Fallo al calcular estadísticas reales'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_calculating_stats'),
            ]);

            return null;
        }
    }

    #[Computed]
    public function simulatedData()
    {
        try {
            if (!$this->hasActiveScenarios()) {
                return ['curve' => [], 'stats' => null];
            }

            if ($this->allTrades->isEmpty()) {
                return ['curve' => [], 'stats' => null];
            }

            $service = app(TradingAnalysisService::class);

            $simTrades = $service->applyScenarios($this->allTrades, $this->scenarios);

            if ($simTrades->count() < 5) {
                $this->dispatch('show-alert', [
                    'type' => 'warning',
                    'message' => __('labels.only_x_trades_before_filters', ['count' => $simTrades->count()]),
                ]);
            }

            $stats = $simTrades->count() >= 5
                ? $service->calculateSystemHealth($simTrades)
                : null;

            return [
                'curve' => $service->calculateEquityCurve($simTrades),
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en simulatedData',
                form: 'ReportsPage',
                description: 'Fallo al calcular simulación'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_calculating_sim'),
            ]);

            return ['curve' => [], 'stats' => null];
        }
    }

    #[Computed]
    public function hourlyReportData()
    {
        try {
            if ($this->allTrades->isEmpty()) {
                return [];
            }

            return app(TradingAnalysisService::class)->analyzeByHour($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en hourlyReportData',
                form: 'ReportsPage',
                description: 'Fallo al analizar por hora'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generating_analyze_by_hour'),
            ]);

            return [];
        }
    }

    #[Computed]
    public function sessionReportData()
    {
        try {
            if ($this->allTrades->isEmpty()) {
                return [];
            }

            return app(TradingAnalysisService::class)->analyzeBySession($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en sessionReportData',
                form: 'ReportsPage',
                description: 'Fallo al analizar por sesión'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generating_analyze_by_sesion'),
            ]);

            return [];
        }
    }

    #[Computed]
    public function efficiencyData()
    {
        try {
            if ($this->allTrades->isEmpty()) {
                return [];
            }

            $tradesWithMAE = $this->allTrades->filter(function ($t) {
                return $t->mae_price !== null && $t->mfe_price !== null;
            });

            if ($tradesWithMAE->isEmpty()) {
                $this->dispatch('show-alert', [
                    'type' => 'info',
                    'message' => __('labels.trades_no_mfe_mae'),
                ]);

                return [];
            }

            return app(TradingAnalysisService::class)->analyzeTradeEfficiency($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en efficiencyData',
                form: 'ReportsPage',
                description: 'Fallo al analizar eficiencia'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generate_efficiency_analisis'),
            ]);

            return [];
        }
    }

    #[Computed]
    public function distributionData()
    {
        try {
            if ($this->allTrades->isEmpty()) {
                return [];
            }

            return app(TradingAnalysisService::class)->analyzeDistribution($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en distributionData',
                form: 'ReportsPage',
                description: 'Fallo al analizar distribución'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generating_histograma'),
            ]);

            return [];
        }
    }

    #[Computed]
    public function radarData()
    {
        try {
            if ($this->allTrades->count() < 5) {
                return [
                    'Winrate' => 0,
                    'Rentabilidad' => 0,
                    'Ratio R:R' => 0,
                    'Consistencia' => 0,
                    'Experiencia' => 0,
                ];
            }

            $data = app(TradingAnalysisService::class)->analyzeTraderProfile($this->allTrades);

            if (!$data) {
                return [
                    'Winrate' => 0,
                    'Rentabilidad' => 0,
                    'Ratio R:R' => 0,
                    'Consistencia' => 0,
                    'Experiencia' => 0,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en radarData',
                form: 'ReportsPage',
                description: 'Fallo al analizar perfil del trader'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generating_profile_trader'),
            ]);

            return [
                'Winrate' => 0,
                'Rentabilidad' => 0,
                'Ratio R:R' => 0,
                'Consistencia' => 0,
                'Experiencia' => 0,
            ];
        }
    }

    #[Computed]
    public function riskData()
    {
        try {
            if ($this->allTrades->count() < 10) {
                return null;
            }

            $currentBalance = $this->getCurrentBalance();

            return app(TradingAnalysisService::class)->analyzeRiskOfRuin($this->allTrades, $currentBalance);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en riskData',
                form: 'ReportsPage',
                description: 'Fallo al analizar riesgo de ruina'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_calculating_risk'),
            ]);

            return null;
        }
    }

    #[Computed]
    public function mistakesData()
    {
        try {
            if ($this->allTrades->isEmpty()) {
                return [];
            }

            return app(TradingAnalysisService::class)->analyzeMistakes($this->allTrades);
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error en mistakesData',
                form: 'ReportsPage',
                description: 'Fallo al analizar errores'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_generating_errors'),
            ]);

            return [];
        }
    }

    /**
     * Coste de los errores sobre las operaciones que se están mirando (P2).
     *
     * Comparte acción con el dashboard para que las dos pantallas no puedan
     * discrepar sobre el mismo periodo.
     */
    #[Computed]
    public function mistakeCost(): array
    {
        return app(CalculateMistakeCost::class)->fromTrades($this->allTrades);
    }

    /** Al repasar una operación desde la cola, el coste y la curva cambian. */
    #[On('mistakes-reviewed')]
    public function refreshAfterReview(): void
    {
        unset($this->allTrades, $this->mistakeCost, $this->mistakesData, $this->simulatedData);
    }

    #[Computed]
    public function accounts()
    {
        try {
            return Account::where('user_id', Auth::id())->get();
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error al cargar cuentas',
                form: 'ReportsPage',
                description: 'Fallo al obtener listado de cuentas'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_loading_list_accounts'),
            ]);

            return collect();
        }
    }

    // ============================================
    // INFORME MENSUAL (P10)
    // ============================================

    /**
     * Los meses que de verdad tienen operaciones, del más reciente al más viejo.
     *
     * Ofrecer un calendario abierto sería ofrecer doscientos meses vacíos; así el
     * desplegable solo enseña meses de los que hay algo que contar.
     *
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function reportMonths(): array
    {
        try {
            return Trade::query()
                ->forUser(Auth::id())
                ->when($this->accountId !== 'all', fn ($q) => $q->where('account_id', $this->accountId))
                ->whereNotNull('exit_time')
                ->selectRaw("to_char(exit_time, 'YYYY-MM') as ym")
                ->distinct()
                ->orderByDesc('ym')
                ->limit(24)
                ->pluck('ym')
                ->map(fn (string $ym): array => [
                    'value' => $ym,
                    'label' => ucfirst(CarbonImmutable::parse($ym . '-01')->translatedFormat('F Y')),
                ])
                ->all();
        } catch (\Throwable $e) {
            $this->logError($e, 'Meses disponibles para informe', 'ReportsPage');

            return [];
        }
    }

    /**
     * El informe mensual en PDF.
     *
     * Se genera dentro de esta misma petición: no hay worker garantizado en
     * producción y un informe encolado que no llega es peor que uno que tarda.
     * El muro PRO no se comprueba aquí porque `RequiresProAccess` ya corta toda
     * acción de este componente para un usuario gratuito.
     */
    public function downloadMonthlyReport(BuildMonthlyReport $builder, MonthlyReportPdf $pdf)
    {
        try {
            $mes = $this->resolveReportMonth();

            $cuenta = $this->accountId !== 'all'
                ? Account::where('id', $this->accountId)->where('user_id', Auth::id())->first()
                : null;

            // Cuenta pedida que no es suya: se cae a "todas" en vez de fallar,
            // igual que hace el resto del componente.
            if ($this->accountId !== 'all' && $cuenta === null) {
                $this->accountId = 'all';
            }

            $informe = $builder->execute(Auth::user(), $mes, $cuenta);

            if (!$informe['has_activity']) {
                $this->dispatch('show-alert', [
                    'type' => 'error',
                    'message' => __('export.pdf.empty', ['month' => $informe['month']['label']]),
                ]);

                return null;
            }

            $this->insertLog(
                action: 'Descarga de informe mensual',
                form: 'ReportsPage',
                description: "Mes: {$informe['month']['start']}. Cuenta: {$this->accountId}"
            );

            return $pdf->download($informe);
        } catch (\Throwable $e) {
            $this->logError($e, 'Informe mensual PDF', 'ReportsPage');

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.unexpected_error'),
            ]);

            return null;
        }
    }

    /** El mes elegido, o el mes en curso si viene vacío o con basura. */
    private function resolveReportMonth(): CarbonImmutable
    {
        $tz = Auth::user()->preferredTimezone();

        if (!is_string($this->reportMonth) || !preg_match('/^\d{4}-\d{2}$/', $this->reportMonth)) {
            return CarbonImmutable::now($tz)->startOfMonth();
        }

        return CarbonImmutable::parse($this->reportMonth . '-01', $tz)->startOfMonth();
    }

    // ============================================
    // RENDER
    // ============================================

    public function render()
    {
        try {
            $this->insertLog(
                action: 'Vista de Laboratorio',
                form: 'ReportsPage',
                description: "Visualizó reportes con {$this->allTrades->count()} trades. Cuenta: {$this->accountId}"
            );

            return view('livewire.reports-page');
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Error crítico en render',
                form: 'ReportsPage',
                description: 'Fallo catastrófico al renderizar la vista'
            );

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.critic_error_loading_page'),
            ]);

            return view('livewire.reports-page');
        }
    }
}
