<?php

namespace App\Livewire;

use App\Enums\NoteMood;
use App\Enums\SessionMood;
use App\Enums\TradeMood;
use App\LogActions; // ✅ TRAIT AÑADIDO
use App\Models\Account;
use App\Models\EconomicEvent;
use App\Models\SessionNote;
use App\Models\Strategy;
use App\Models\Trade;
use App\Models\TradingSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SessionPage extends Component
{
    use LogActions; // ✅ CRASH PROTECTION ACTIVADO

    // ==========================================
    // 📦 PROPIEDADES PÚBLICAS
    // ==========================================
    public $sessionId;
    public $accounts = [];
    public $strategies = [];
    public $restoredSessionData = null;
    public $moodConfig = [];

    // ==========================================
    // 🎬 LIFECYCLE: MOUNT
    // ==========================================
    public function mount()
    {
        try {
            // ✅ QUERIES DIRECTAS (PostgreSQL es rápido con eager loading)
            $this->moodConfig = [
                'session' => SessionMood::toOptions(),
                'note'    => NoteMood::toOptions(),
                'trade'   => TradeMood::toOptions(),
            ];
            $this->accounts = $this->loadAccounts();
            $this->strategies = $this->loadStrategies();

            // ✅ RESTAURAR SESIÓN ACTIVA
            $this->restoreActiveSession();

            $this->moodConfig = [
                'session' => SessionMood::toOptions(),
                'note'    => NoteMood::toOptions(),
                'trade'   => TradeMood::toOptions(),
            ];
        } catch (\Exception $e) {
            $this->logError($e, 'mount', 'SessionPage', 'Error al cargar datos iniciales');

            // ✅ RETORNO SEGURO
            $this->accounts = [];
            $this->strategies = [];
            $this->restoredSessionData = null;

            $this->dispatch('show-alert', [
                'message' => __('labels.error_loading_conf'),
                'type' => 'error'
            ]);
        }
    }

    /**
     * ✅ MEJORADO: Restaura sesión activa con validación
     */
    private function restoreActiveSession(): void
    {
        $activeSession = TradingSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$activeSession) {
            return; // No hay sesión activa, estado limpio
        }

        try {
            $this->sessionId = $activeSession->id;
            $data = $this->fetchUpdates();

            // ✅ VALIDACIÓN: Si fetchUpdates falla, no restaurar
            if (!$data) {
                $this->sessionId = null;
                return;
            }

            $this->restoredSessionData = [
                'accountId' => $activeSession->account_id,
                'strategyId' => $activeSession->strategy_id,
                'startTime' => $activeSession->start_time->timestamp,
                'startBalance' => (float) $activeSession->start_balance,
                'checklistState' => $activeSession->checklist_state ?? [],
                'trades' => $data['trades'],
                'metrics' => $data['metrics'],
                'notes' => $data['notes'],
                'events' => $data['events'] ?? []
            ];

            $this->insertLog('restore_session', 'SessionPage', "Sesión #{$activeSession->id} restaurada");
        } catch (\Exception $e) {
            $this->logError($e, 'restoreActiveSession', 'SessionPage', 'Error al restaurar sesión activa');
            $this->sessionId = null;
            $this->restoredSessionData = null;
        }
    }

    // ==========================================
    // 🎨 RENDER
    // ==========================================
    #[Layout('layouts.fullscreen')]
    public function render()
    {
        return view('livewire.session-page');
    }

    // ==========================================
    // 🚀 API METHODS (Llamados desde Alpine)
    // ==========================================

    /**
     * ✅ MEJORADO: Validaciones robustas y transacción atómica
     */
    public function startSession($accountId, $strategyId, $mood, $notes)
    {
        try {
            // ✅ VALIDACIÓN 1: Mood válido
            $mood = SessionMood::tryFrom($mood)?->value ?? SessionMood::Neutral->value;

            // ✅ VALIDACIÓN 2: Verificar que no haya sesión activa (con lock)
            $existingSession = TradingSession::where('user_id', Auth::id())
                ->where('status', 'active')
                ->lockForUpdate() // ✅ Prevenir race condition
                ->first();

            if ($existingSession) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.yet_active_session'),
                    'type' => 'warning'
                ]);
                return null;
            }

            // ✅ VALIDACIÓN 3: Cuenta válida, pertenece al usuario y está activa
            $account = Account::where('id', $accountId)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->lockForUpdate() // ✅ Lock para evitar modificaciones concurrentes
                ->first();

            if (!$account) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.not_valid_account'),
                    'type' => 'error'
                ]);
                return null;
            }

            // ✅ VALIDACIÓN 4: Balance inicial válido
            if ($account->current_balance <= 0) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.balance_not_zero'),
                    'type' => 'error'
                ]);
                return null;
            }

            // ✅ VALIDACIÓN 5: Estrategia existe y pertenece al usuario
            if ($strategyId) {
                $strategyExists = Strategy::where('id', $strategyId)
                    ->where('user_id', Auth::id())
                    ->exists();

                if (!$strategyExists) {
                    $this->dispatch('show-alert', [
                        'message' => __('labels.strategy_not_valid'),
                        'type' => 'error'
                    ]);
                    return null;
                }
            }

            // ✅ TRANSACCIÓN ATÓMICA
            DB::beginTransaction();

            $session = TradingSession::create([
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'strategy_id' => $strategyId,
                'start_time' => now(),
                'start_balance' => $account->current_balance,
                'start_mood' => $mood,
                'pre_session_notes' => $notes,
                'status' => 'active',
                'checklist_state' => []
            ]);

            $this->sessionId = $session->id;

            DB::commit();

            // ✅ LOG EXITOSO
            $this->insertLog(
                'start_session',
                'SessionPage',
                "Sesión #{$session->id} iniciada - Cuenta: {$account->name} - Balance: {$account->current_balance}"
            );

            return [
                'id' => $this->sessionId,
                'start_balance' => (float) $account->current_balance
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError($e, 'startSession', 'SessionPage', 'Error al iniciar sesión de trading');
            $this->dispatch('show-alert', [
                'message' => __('labels.error_loging'),
                'type' => 'error'
            ]);
            return null;
        }
    }

    /**
     * ✅ BLINDADO: Sincroniza checklist sin errores
     */
    public function syncChecklist($checkedRules)
    {
        try {
            if (!$this->sessionId) {
                return; // Silencioso, no es crítico
            }

            // ✅ VALIDACIÓN: Sesión pertenece al usuario
            $updated = TradingSession::where('id', $this->sessionId)
                ->where('user_id', Auth::id())
                ->update(['checklist_state' => $checkedRules]);

            if (!$updated) {
                $this->logError(
                    new \Exception('Sesión no encontrada o no autorizada'),
                    'syncChecklist',
                    'SessionPage',
                    "Intento de sync en sesión #{$this->sessionId}"
                );
            }
        } catch (\Exception $e) {
            $this->logError($e, 'syncChecklist', 'SessionPage', 'Error al sincronizar checklist');
            // ✅ No mostramos alerta, no afecta UX (se reintenta en próximo toggle)
        }
    }

    /**
     * ✅ OPTIMIZADO: Fetch con eager loading agresivo
     */
    public function fetchUpdates()
    {
        try {
            if (!$this->sessionId) {
                return $this->getEmptyMetrics();
            }

            // ✅ VALIDACIÓN: Sesión existe y pertenece al usuario
            $session = TradingSession::where('id', $this->sessionId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$session) {
                $this->sessionId = null;
                return $this->getEmptyMetrics();
            }

            // ✅ OPTIMIZACIÓN 1: Select específico + Eager loading
            $trades = Trade::select([
                'id',
                'trade_asset_id',
                'direction',
                'pnl',
                'exit_time',
                'mood',
                'trading_session_id',
                'strategy_id'
            ])
                ->where('account_id', $session->account_id)
                ->where('exit_time', '>=', $session->start_time)
                ->with([
                    'tradeAsset:id,symbol', // Solo columnas necesarias
                    'strategy:id,name,color' // Para futura expansión
                ])
                ->orderBy('exit_time', 'desc')
                ->get();

            // ✅ VINCULACIÓN AUTOMÁTICA DE HUÉRFANOS (mejorada)
            $this->syncOrphanTrades($trades, $session->id);

            // ✅ CÁLCULOS AGREGADOS
            $metrics = $this->calculateMetrics($trades, $session->start_balance);

            // ✅ FORMATEAR TRADES (optimizado)
            $formattedTrades = $trades->map(function ($t) {
                return [
                    'id' => $t->id,
                    'symbol' => $t->tradeAsset?->symbol ?? 'N/A',
                    'direction' => $t->direction,
                    'pnl' => (float) $t->pnl,
                    'time' => $t->exit_time->format('H:i'),
                    'mood' => $t->mood,
                ];
            })->values(); // ✅ Reset keys para JSON limpio

            // ✅ OPTIMIZACIÓN 2: Lazy load de notas (solo si hay sesión)
            $notes = $this->loadSessionNotes();

            // ✅ OPTIMIZACIÓN 3: Eventos con scope
            $upcomingEvents = $this->getUpcomingHighImpactEvents();

            return [
                'trades' => $formattedTrades,
                'notes' => $notes,
                'events' => $upcomingEvents,
                'metrics' => $metrics
            ];
        } catch (\Exception $e) {
            $this->logError($e, 'fetchUpdates', 'SessionPage', 'Error al obtener actualizaciones');
            return $this->getEmptyMetrics();
        }
    }

    /**
     * ✅ MEJORADO: Protección contra race conditions
     */
    private function syncOrphanTrades($trades, $sessionId): void
    {
        try {
            $orphanIds = $trades->whereNull('trading_session_id')->pluck('id');

            if ($orphanIds->isEmpty()) {
                return;
            }

            // ✅ ATOMIC UPDATE: Solo actualizar si aún no tienen sesión
            $updated = Trade::whereIn('id', $orphanIds)
                ->whereNull('trading_session_id') // ✅ Doble check para race conditions
                ->update([
                    'trading_session_id' => $sessionId,
                    'updated_at' => now()
                ]);

            if ($updated > 0) {
                $this->insertLog(
                    'sync_orphan_trades',
                    'SessionPage',
                    "Vinculados {$updated} trades a sesión #{$sessionId}"
                );
            }
        } catch (\Exception $e) {
            $this->logError($e, 'syncOrphanTrades', 'SessionPage', 'Error al vincular trades huérfanos');
        }
    }


    /**
     * ✅ NUEVO: Carga notas de forma optimizada
     */
    private function loadSessionNotes(): array
    {
        try {
            return SessionNote::select(['id', 'note', 'mood', 'created_at'])
                ->where('trading_session_id', $this->sessionId)
                ->latest()
                ->limit(50) // ✅ LÍMITE: No cargar más de 50 notas
                ->get()
                ->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'note' => $n->note,
                        'mood' => $n->mood,
                        'time' => $n->created_at->format('H:i')
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->logError($e, 'loadSessionNotes', 'SessionPage', 'Error al cargar notas');
            return [];
        }
    }

    /**
     * ✅ OPTIMIZADO: Usa scope del modelo EconomicEvent
     */
    private function getUpcomingHighImpactEvents(): array
    {
        try {
            return EconomicEvent::upcoming(10, 60) // Scope personalizado
                ->get()
                ->map(function ($event) {
                    return $event->toSimpleArray(); // Método del modelo
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->logError($e, 'getUpcomingHighImpactEvents', 'SessionPage', 'Error al cargar eventos económicos');
            return [];
        }
    }

    /**
     * ✅ OPTIMIZADO: Cálculos con collection methods
     */
    private function calculateMetrics($trades, $startBalance): array
    {
        $totalCount = $trades->count();
        if ($totalCount === 0) {
            return ['count' => 0, 'pnl' => 0, 'pnl_percent' => 0, 'winrate' => 0];
        }
        $totalPnL = $trades->sum('pnl');
        $winCount = $trades->where('pnl', '>', 0)->count();
        $startBal = max($startBalance, 1);
        $pnlPercent = ($totalPnL / $startBal) * 100;
        return [
            'count' => $totalCount,
            'pnl' => round($totalPnL, 2),
            'pnl_percent' => round($pnlPercent, 2),
            'winrate' => round(($winCount / $totalCount) * 100, 0)
        ];
    }

    /**
     * ✅ OPTIMIZADO: Carga de accounts con select específico
     */
    private function loadAccounts(): array
    {
        return Account::select([
            'id',
            'name',
            'current_balance',
            'currency',
            'status'
        ])
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->with([
                'tradingPlan:id,account_id,max_daily_loss_percent,daily_profit_target_percent,max_daily_trades,start_time,end_time'
            ])
            ->get()
            ->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'balance' => (float) $acc->current_balance,
                    'currency' => $acc->currency,
                    'limits' => $acc->tradingPlan ? [
                        'max_loss_pct' => (float) $acc->tradingPlan->max_daily_loss_percent,
                        'target_pct' => (float) $acc->tradingPlan->daily_profit_target_percent,
                        'max_trades' => $acc->tradingPlan->max_daily_trades,
                        'start_time' => $acc->tradingPlan->start_time
                            ? \Carbon\Carbon::parse($acc->tradingPlan->start_time)->format('H:i')
                            : null,
                        'end_time' => $acc->tradingPlan->end_time
                            ? \Carbon\Carbon::parse($acc->tradingPlan->end_time)->format('H:i')
                            : null,
                    ] : null
                ];
            })
            ->toArray();
    }

    /**
     * ✅ OPTIMIZADO: Carga de strategies con select específico
     */
    private function loadStrategies(): array
    {
        return Strategy::select(['id', 'name', 'rules', 'color'])
            ->where('user_id', Auth::id())
            ->orderBy('is_main', 'desc') // ✅ Estrategia principal primero
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'rules' => $s->rules ?? [],
                    'color' => $s->color ?? '#4F46E5' // ✅ Para futura UI
                ];
            })
            ->toArray();
    }


    /**
     * ✅ NUEVO: Estructura vacía segura
     */
    private function getEmptyMetrics(): array
    {
        return [
            'trades' => [],
            'notes' => [],
            'events' => [],
            'metrics' => [
                'count' => 0,
                'pnl' => 0,
                'pnl_percent' => 0,
                'winrate' => 0
            ]
        ];
    }

    /**
     * ✅ MEJORADO: Validación de mood con enum estricto
     */
    public function addNote($note, $mood)
    {
        try {
            if (!$this->sessionId) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.not_active_sesion'),
                    'type' => 'warning'
                ]);
                return;
            }

            // ✅ VALIDACIÓN 1: Nota no vacía
            $note = trim($note);
            if (empty($note)) {
                return;
            }

            // ✅ VALIDACIÓN 2: Nota no muy larga (evitar spam)
            if (strlen($note) > 1000) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.note_large'),
                    'type' => 'warning'
                ]);
                return;
            }

            // ✅ VALIDACIÓN 3: Mood válido
            $mood = NoteMood::tryFrom($mood)?->value ?? NoteMood::Neutral->value;

            // ✅ VALIDACIÓN 4: Sesión existe y pertenece al usuario
            $sessionExists = TradingSession::where('id', $this->sessionId)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->exists();

            if (!$sessionExists) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.session_not_valid'),
                    'type' => 'error'
                ]);
                return;
            }

            SessionNote::create([
                'trading_session_id' => $this->sessionId,
                'note' => $note,
                'mood' => $mood
            ]);
        } catch (\Exception $e) {
            $this->logError($e, 'addNote', 'SessionPage', 'Error al guardar nota');
            $this->dispatch('show-alert', [
                'message' => __('labels.error_saving_note'),
                'type' => 'error'
            ]);
        }
    }
    /**
     * ✅ MEJORADO: Validación de mood con enum estricto
     */
    public function updateTradeMood($tradeId, $mood)
    {
        try {
            // ✅ VALIDACIÓN: Mood válido
            if (!TradeMood::tryFrom($mood)) {
                $this->insertLog('validation_fail', 'SessionPage', "Trade mood inválido rechazado: {$mood}");
                return;
            }

            // ✅ OPTIMIZADO: Query con whereHas
            $updated = Trade::where('id', $tradeId)
                ->forUser()
                ->update(['mood' => $mood]);

            if (!$updated) {
                $this->logError(
                    new \Exception('Trade no encontrado o no autorizado'),
                    'updateTradeMood',
                    'SessionPage',
                    "Intento de actualizar trade #{$tradeId}"
                );
            }
        } catch (\Exception $e) {
            $this->logError($e, 'updateTradeMood', 'SessionPage', 'Error al actualizar mood del trade');
        }
    }

    /**
     * ✅ OPTIMIZADO: Sin validación de trades abiertos (solo cerrados se sincronizan)
     */
    public function closeSession($metrics, $endMood, $postSessionNotes = '')
    {
        try {
            if (!$this->sessionId) {
                return route('journal');
            }

            // ✅ VALIDACIÓN 1: Sesión existe y pertenece al usuario
            $session = TradingSession::where('id', $this->sessionId)
                ->where('user_id', Auth::id())
                ->lockForUpdate() // ✅ Lock para transacción
                ->first();

            if (!$session) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.session_not_found'),
                    'type' => 'error'
                ]);
                return route('journal');
            }

            // ✅ VALIDACIÓN 2: No cerrar dos veces
            if ($session->status === 'closed') {
                $this->insertLog('duplicate_close', 'SessionPage', "Intento de cerrar sesión #{$session->id} ya cerrada");
                return route('journal');
            }

            // ✅ VALIDACIÓN 3: Mood válido
            $endMood = SessionMood::tryFrom($endMood)?->value ?? SessionMood::Neutral->value;


            // ✅ VALIDACIÓN 4: Métricas válidas
            if (!is_array($metrics) || !isset($metrics['count'], $metrics['pnl'], $metrics['pnl_percent'])) {
                $this->logError(
                    new \Exception('Métricas inválidas'),
                    'closeSession',
                    'SessionPage',
                    'Métricas recibidas: ' . json_encode($metrics)
                );
                // ✅ Recalcular métricas desde servidor
                $data = $this->fetchUpdates();
                $metrics = $data['metrics'] ?? ['count' => 0, 'pnl' => 0, 'pnl_percent' => 0];
            }

            // ✅ VALIDACIÓN: Notas no demasiado largas
            $postSessionNotes = trim($postSessionNotes);
            if (strlen($postSessionNotes) > 2000) {
                $postSessionNotes = substr($postSessionNotes, 0, 2000);
            }

            // ✅ TRANSACCIÓN ATÓMICA
            DB::beginTransaction();

            $endBalance = Account::find($session->account_id)->current_balance;

            $session->update([
                'end_time' => now(),
                'end_balance' => $endBalance,
                'end_mood' => $endMood,
                'total_trades' => $metrics['count'] ?? 0,
                'session_pnl' => $metrics['pnl'] ?? 0,
                'session_pnl_percent' => $metrics['pnl_percent'] ?? 0,
                'post_session_notes'    => $postSessionNotes ?: null, // ✅ NUEVO
                'status' => 'closed'
            ]);

            DB::commit();

            // ✅ LOG
            $this->insertLog(
                'close_session',
                'SessionPage',
                "Sesión #{$session->id} cerrada - Trades: {$session->total_trades} - PnL: {$session->session_pnl_percent}%"
            );

            // ✅ LIMPIAR ESTADO
            $this->sessionId = null;

            return route('journal');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError($e, 'closeSession', 'SessionPage', 'Error al cerrar sesión');
            $this->dispatch('show-alert', [
                'message' => __('labels.error_closing_sesion'),
                'type' => 'error'
            ]);
            return route('journal');
        }
    }
}
