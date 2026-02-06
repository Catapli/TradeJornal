<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TradingSession;
use Illuminate\Support\Facades\Auth;

class SessionHistory extends Component
{
    use WithPagination;

    // Filtros (Sí afectan a la query, se quedan en Livewire)
    public $search = '';
    public $filterAccount = null;
    public $filterMood = null;
    public $dateFrom = null;
    public $dateTo = null;

    // Método para servir detalles ASÍNCRONAMENTE (Data Fetching)
    public function getSessionDetails($sessionId)
    {
        $session = TradingSession::with(['trades.tradeAsset', 'notes', 'account.tradingPlan', 'strategy'])
            ->find($sessionId);

        if (!$session) return null;

        $limitTrades = $session->account->tradingPlan->max_daily_trades ?? 999;

        return [
            'id' => $session->id,
            'pnl' => (float)$session->session_pnl,
            'pnl_percent' => (float)$session->session_pnl_percent,
            'duration' => $session->end_time ? $session->start_time->diff($session->end_time)->format('%Hh %Im') : 'En curso',
            'start_time' => $session->start_time->format('H:i'),
            'end_time' => $session->end_time?->format('H:i'),
            'start_mood' => $session->start_mood,
            'end_mood' => $session->end_mood,
            'pre_notes' => $session->pre_session_notes,
            'is_overtraded' => $session->total_trades > $limitTrades,
            'limit_trades' => $limitTrades,
            'total_trades' => $session->total_trades,

            // CORRECCIÓN: Aseguramos que siempre devuelva array (nunca null) y key único
            'notes' => $session->notes->map(fn($n, $index) => [
                'id' => $n->id ?? $index, // KEY ÚNICO
                'time' => $n->created_at->format('H:i'),
                'mood' => $n->mood ?? 'neutral',
                'text' => $n->note ?? ''
            ])->toArray(), // toArray() asegura que sea array limpio

            'trades' => $session->trades->map(fn($t, $index) => [
                'id' => $t->id ?? $index, // KEY ÚNICO
                'time' => $t->exit_time ? $t->exit_time->format('H:i') : '--:--',
                'symbol' => $t->tradeAsset->symbol ?? 'UNK',
                'direction' => $t->direction ?? 'long',
                'pnl' => (float)$t->pnl,
            ])->toArray()
        ];
    }



    public function render()
    {
        // Query optimizada para el Grid (Datos ligeros)
        $sessions = TradingSession::where('user_id', Auth::id())
            ->with(['account:id,name', 'strategy:id,name']) // Solo lo necesario para la tarjeta
            ->when($this->search, function ($q) {
                $q->whereHas('account', fn($a) => $a->where('name', 'like', '%' . $this->search . '%'));
            })
            ->when($this->filterAccount, fn($q) => $q->where('account_id', $this->filterAccount))
            ->when($this->filterMood, fn($q) => $q->where('end_mood', $this->filterMood))
            ->when($this->dateFrom, fn($q) => $q->whereDate('start_time', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('start_time', '<=', $this->dateTo))
            ->latest('start_time')
            ->paginate(9);

        // Stats globales
        $stats = [
            'total' => TradingSession::where('user_id', Auth::id())->count(),
            'win_rate' => 0 // Lógica de cálculo simplificada para ejemplo
        ];

        return view('livewire.session-history', [
            'sessions' => $sessions,
            'stats' => $stats,
            'accounts' => \App\Models\Account::where('user_id', Auth::id())->get(['id', 'name'])
        ]);
    }
}
