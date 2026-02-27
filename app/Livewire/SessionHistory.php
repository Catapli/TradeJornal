<?php

namespace App\Livewire;

use App\LogActions;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\TradingSession;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class SessionHistory extends Component
{
    use WithPagination;
    use LogActions;

    // --- Filtros ---
    #[Url(as: 'account', nullable: true)]
    public ?string $filterAccount  = null;

    #[Url(as: 'mood', nullable: true)]
    public ?string $filterMood     = null;

    #[Url(as: 'strategy', nullable: true)]
    public ?string $filterStrategy = null; // ← NUEVO

    #[Url(as: 'from', nullable: true)]
    public ?string $dateFrom       = null;

    #[Url(as: 'to', nullable: true)]
    public ?string $dateTo         = null;

    // ─────────────────────────────────────────────────────────
    // RESET FILTROS
    // ─────────────────────────────────────────────────────────
    public function resetFilters(): void
    {
        $this->filterAccount  = null;
        $this->filterMood     = null;
        $this->filterStrategy = null; // ← NUEVO
        $this->dateFrom       = null;
        $this->dateTo         = null;
        $this->resetPage();
    }

    // ─────────────────────────────────────────────────────────
    // DATA FETCHING — Modal de detalle
    // ─────────────────────────────────────────────────────────
    public function getSessionDetails(int $sessionId): ?array
    {
        try {
            $session = TradingSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->with([
                    'account:id',
                    'account.tradingPlan:id,account_id,max_daily_trades',
                    'notes:id,trading_session_id,note,mood,created_at',
                    'trades:id,trading_session_id,trade_asset_id,direction,pnl,exit_time',
                    'trades.tradeAsset:id,symbol',
                ])
                ->first();

            if (!$session) {
                $this->dispatch('show-alert', [
                    'message' => __('labels.session_not_found'),
                    'type'    => 'error',
                ]);
                return null;
            }

            $limitTrades = $session->account?->tradingPlan?->max_daily_trades ?? 999;

            return [
                'id'            => $session->id,
                'pnl'           => (float) $session->session_pnl,
                'pnl_percent'   => (float) $session->session_pnl_percent,
                'duration'      => $session->end_time
                    ? $session->start_time->diff($session->end_time)->format('%Hh %Im')
                    : 'En curso',
                'start_time'    => $session->start_time->format('H:i'),
                'end_time'      => $session->end_time?->format('H:i'),
                'start_mood'    => $session->start_mood,
                'end_mood'      => $session->end_mood,
                'pre_notes'     => $session->pre_session_notes,
                'post_notes'    => $session->post_session_notes,
                'is_overtraded' => $session->total_trades > $limitTrades,
                'limit_trades'  => $limitTrades,
                'total_trades'  => $session->total_trades,
                'notes' => $session->notes->map(fn($n, $index) => [
                    'id'   => $n->id ?? $index,
                    'time' => $n->created_at->format('H:i'),
                    'mood' => $n->mood ?? 'neutral',
                    'text' => $n->note ?? '',
                ])->toArray(),
                'trades' => $session->trades->map(fn($t, $index) => [
                    'id'        => $t->id ?? $index,
                    'time'      => $t->exit_time ? $t->exit_time->format('H:i') : '--:--',
                    'symbol'    => $t->tradeAsset?->symbol ?? 'UNK',
                    'direction' => $t->direction ?? 'long',
                    'pnl'       => (float) $t->pnl,
                ])->toArray(),
            ];
        } catch (\Exception $e) {
            $this->logError($e, 'GetSessionDetails', 'SessionHistory', "Error al cargar sesión ID: {$sessionId}");
            $this->dispatch('show-alert', [
                'message' => __('labels.error_loading_data_sesion'),
                'type'    => 'error',
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────────────────────────
    public function render()
    {
        try {
            $sessions = TradingSession::where('user_id', Auth::id())
                ->with(['account:id,name', 'strategy:id,name'])
                ->when($this->filterAccount,  fn($q) => $q->where('account_id', $this->filterAccount))
                ->when($this->filterMood,     fn($q) => $q->where('end_mood', $this->filterMood))
                ->when($this->filterStrategy, fn($q) => $q->where('strategy_id', $this->filterStrategy)) // ← NUEVO
                ->when($this->dateFrom,       fn($q) => $q->whereDate('start_time', '>=', $this->dateFrom))
                ->when($this->dateTo,         fn($q) => $q->whereDate('start_time', '<=', $this->dateTo))
                ->latest('start_time')
                ->paginate(9);

            $rawStats = TradingSession::where('user_id', Auth::id())
                ->selectRaw("
                    COUNT(*)                                                                AS total,
                    COALESCE(
                        ROUND(
                            AVG(CASE WHEN session_pnl > 0 THEN 100.0 ELSE 0.0 END)::numeric,
                        1),
                    0)                                                                      AS win_rate,
                    COALESCE(SUM(session_pnl), 0)                                          AS total_pnl,
                    COALESCE(SUM(CASE WHEN session_pnl > 0 THEN 1 ELSE 0 END), 0)         AS winning_sessions
                ")
                ->first();

            $stats = [
                'total'            => (int)   ($rawStats->total            ?? 0),
                'win_rate'         => (float) ($rawStats->win_rate         ?? 0),
                'total_pnl'        => (float) ($rawStats->total_pnl        ?? 0),
                'winning_sessions' => (int)   ($rawStats->winning_sessions ?? 0),
            ];

            $accounts   = Account::where('user_id', Auth::id())->get(['id', 'name']);
            $strategies = Strategy::where('user_id', Auth::id())->get(['id', 'name']); // ← NUEVO

        } catch (\Exception $e) {
            $this->logError($e, 'Render', 'SessionHistory', 'Error al cargar el histórico de sesiones');

            $sessions = new LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 9,
                currentPage: 1,
            );
            $stats      = ['total' => 0, 'win_rate' => 0, 'total_pnl' => 0, 'winning_sessions' => 0];
            $accounts   = collect();
            $strategies = collect(); // ← NUEVO
        }

        return view('livewire.session-history', [
            'sessions'   => $sessions,
            'stats'      => $stats,
            'accounts'   => $accounts,
            'strategies' => $strategies, // ← NUEVO
        ]);
    }
}
