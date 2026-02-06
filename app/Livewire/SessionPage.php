<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\TradingSession;
use App\Models\Trade;
use App\Models\SessionNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionPage extends Component
{
    // Datos estáticos para configuración
    public $accounts = [];
    public $strategies = [];

    // Estado de la sesión
    public $sessionId = null;
    public $restoredSessionData = null;

    public function mount()
    {
        // 1. Cargar Cuentas con sus Planes (Evitar N+1)
        // Mapeamos solo lo necesario para Alpine
        $this->accounts = Account::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with('tradingPlan') // Relación definida en modelo Account
            ->get()
            ->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'balance' => $acc->current_balance,
                    'currency' => $acc->currency,
                    // Extraemos límites del plan si existe
                    // === AQUÍ ESTABA EL ERROR DE NOMBRES ===
                    'limits' => $acc->tradingPlan ? [
                        // Antes puse 'max_loss', cámbialo a 'max_loss_pct' para que coincida con el HTML
                        'max_loss_pct' => (float) $acc->tradingPlan->max_daily_loss_percent,

                        // Antes puse 'target', cámbialo a 'target_pct'
                        'target_pct' => (float) $acc->tradingPlan->daily_profit_target_percent,

                        'max_trades' => $acc->tradingPlan->max_daily_trades,
                        'start_time' => $acc->tradingPlan->start_time ? \Carbon\Carbon::parse($acc->tradingPlan->start_time)->format('H:i') : null,
                        'end_time' => $acc->tradingPlan->end_time ? \Carbon\Carbon::parse($acc->tradingPlan->end_time)->format('H:i') : null,
                    ] : null
                ];
            });

        // 2. Cargar Estrategias y decodificar reglas JSON
        $this->strategies = Strategy::where('user_id', Auth::id())
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'rules' => $s->rules ?? [] // Ya viene como array si usas $casts en el modelo
                ];
            });

        // 3. Restaurar Sesión Activa
        $activeSession = TradingSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($activeSession) {
            $this->sessionId = $activeSession->id;

            // Obtenemos datos frescos
            $data = $this->fetchUpdates();

            // Payload de restauración para Alpine
            $this->restoredSessionData = [
                'accountId' => $activeSession->account_id,
                'strategyId' => $activeSession->strategy_id,
                'startTime' => $activeSession->start_time->timestamp,
                'startBalance' => (float) $activeSession->start_balance,
                'checklistState' => $activeSession->checklist_state ?? [], // Restaurar checks
                'trades' => $data['trades'],
                'metrics' => $data['metrics'],
                'notes' => $data['notes']
            ];
        }
    }

    #[Layout('layouts.fullscreen')]
    public function render()
    {
        return view('livewire.session-page');
    }

    // ==========================================
    // 🚀 API METHODS (Llamados desde Alpine)
    // ==========================================

    public function startSession($accountId, $strategyId, $mood, $notes)
    {
        $account = Account::findOrFail($accountId);

        DB::transaction(function () use ($account, $strategyId, $mood, $notes) {
            $session = TradingSession::create([
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'strategy_id' => $strategyId,
                'start_time' => now(),
                'start_balance' => $account->current_balance, // Snapshot crítico para cálculos %
                'start_mood' => $mood,
                'pre_session_notes' => $notes,
                'status' => 'active',
                'checklist_state' => [] // Array vacío inicial
            ]);

            $this->sessionId = $session->id;
        });

        // Retornamos el timestamp y el balance inicial para que Alpine calcule los %
        return [
            'id' => $this->sessionId,
            'start_balance' => (float) $account->current_balance
        ];
    }

    /**
     * Guarda el estado del checklist sin recargar nada.
     * Alpine envía el array de reglas marcadas (strings).
     */
    public function syncChecklist($checkedRules)
    {
        if ($this->sessionId) {
            TradingSession::where('id', $this->sessionId)
                ->update(['checklist_state' => $checkedRules]);
        }
    }

    public function fetchUpdates()
    {
        if (!$this->sessionId) return null;

        $session = TradingSession::find($this->sessionId);
        if (!$session) return null;

        // 1. Obtener Trades vinculados a esta sesión o huérfanos recientes
        // Lógica: Trades cerrados DESPUÉS de iniciar la sesión
        $trades = Trade::where('account_id', $session->account_id)
            ->where('exit_time', '>=', $session->start_time)
            ->orderBy('exit_time', 'desc')
            ->get();

        // Vincular huérfanos a la sesión (Lazy sync)
        $orphanTrades = $trades->whereNull('trading_session_id');
        if ($orphanTrades->isNotEmpty()) {
            Trade::whereIn('id', $orphanTrades->pluck('id'))
                ->update(['trading_session_id' => $session->id]);
        }

        // 2. Cálculos Agregados (Server Side es más preciso para dinero)
        $totalPnL = $trades->sum('pnl');
        $winCount = $trades->where('pnl', '>', 0)->count();
        $totalCount = $trades->count();

        // Calcular % basado en el balance INICIAL de la sesión
        $startBal = $session->start_balance > 0 ? $session->start_balance : 1;
        $pnlPercent = ($totalPnL / $startBal) * 100;


        // 3. Formatear para Frontend
        $formattedTrades = $trades->map(function ($t) {
            return [
                'id' => $t->id,
                'symbol' => $t->trade_asset_id, // Idealmente cargar relación Asset para ver nombre
                'direction' => $t->direction,
                'pnl' => (float) $t->pnl,
                'time' => $t->exit_time->format('H:i'),
                'mood' => $t->mood,
            ];
        });

        $notes = SessionNote::where('trading_session_id', $this->sessionId)
            ->latest()
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'note' => $n->note,
                    'mood' => $n->mood,
                    'time' => $n->created_at->format('H:i')
                ];
            });


        $upcomingEvents = $this->getUpcomingHighImpactEvents();

        return [
            'trades' => $formattedTrades,
            'notes' => $notes,
            'upcoming_events' => $upcomingEvents,
            'metrics' => [
                'count' => $totalCount,
                'pnl' => round($totalPnL, 2),
                'pnl_percent' => round($pnlPercent, 2),
                'winrate' => $totalCount > 0 ? round(($winCount / $totalCount) * 100, 0) : 0
            ]
        ];
    }

    public function addNote($note, $mood)
    {
        if ($this->sessionId) {
            SessionNote::create([
                'trading_session_id' => $this->sessionId,
                'note' => $note,
                'mood' => $mood
            ]);
        }
    }

    // Método auxiliar para buscar noticias
    private function getUpcomingHighImpactEvents()
    {
        // Buscamos noticias de ALTO impacto en los próximos 60 minutos
        // O que hayan pasado hace menos de 10 mins (volatilidad residual)
        $now = now();
        $limit = now()->addMinutes(60);
        $past = now()->subMinutes(10);

        return \Illuminate\Support\Facades\DB::table('economic_events')
            ->where('date', $now->toDateString())
            ->whereBetween('time', [$past->format('H:i:s'), $limit->format('H:i:s')])
            ->where('impact', 'high')
            // Opcional: Filtrar por divisas relevantes (USD, EUR) si quisieras ser más específico
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($event) use ($now) {
                // Calculamos minutos restantes para mostrar "en 15m"
                $eventTime = \Carbon\Carbon::parse($event->date . ' ' . $event->time);
                $diff = $now->diffInMinutes($eventTime, false); // false para negativos si ya pasó

                return [
                    'id' => $event->id,
                    'currency' => $event->currency,
                    'event' => $event->event,
                    'time' => substr($event->time, 0, 5), // 14:30
                    'minutes_diff' => (int)$diff
                ];
            });
    }

    public function updateTradeMood($tradeId, $mood)
    {
        Trade::where('id', $tradeId)->where('account_id', Auth::user()->accounts->pluck('id'))
            ->update(['mood' => $mood]);
    }

    public function closeSession($metrics, $endMood)
    {
        if (!$this->sessionId) return;

        $session = TradingSession::find($this->sessionId);

        // Actualizamos balance final desde la cuenta
        $endBalance = Account::find($session->account_id)->current_balance;

        $session->update([
            'end_time' => now(),
            'end_balance' => $endBalance,
            'end_mood' => $endMood,
            'total_trades' => $metrics['count'] ?? 0,
            'session_pnl' => $metrics['pnl'] ?? 0,
            'session_pnl_percent' => $metrics['pnl_percent'] ?? 0,
            'status' => 'closed'
        ]);

        return route('journal'); // O la ruta que prefieras
    }
}
