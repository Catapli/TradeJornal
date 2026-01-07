<?php

namespace App\Livewire;

use App\Jobs\SyncAccountTrades;
use App\Jobs\SyncMt5Account;
use App\Models\Account;
use App\Models\Trade;
use App\Services\Mt5Gateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AccountPage extends Component
{

    public $accounts;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedAccount;
    public $selectedAccountId;

    // ? Datos para el gráfico de balance
    public $balanceChartData = [
        'labels' => [],
        'datasets' => []
    ];
    // ? Estadisticas de cuenta
    public $totalPnl = 0; // PNL total de la cuenta
    public $winRate = 0; // % de trades ganadores
    public $totalTrades; // Total de trades
    public $firstTradeDate; // Fecha del primer trade
    public $avgDurationMinutes = 0;
    public $avgDurationFormatted = '0h 0m';
    public $maxWin = 0;      // Ganancia Máxima
    public $maxLoss = 0;     // Pérdida Máxima
    public $topAsset = 'N/A'; // Símbolo más operado
    public $tradingDays = 0; // Días de trading activos
    public $avgWinTrade = 0;    // €127.50
    public $avgLossTrade = 0;   // €55.20
    public $arr = 0;
    public $accountAgeDays = 0;
    public $accountAgeFormatted = '0 días';

    public $profitFactor = 0;    // 2.15
    public $grossProfit = 0;     // €12,450
    public $grossLoss = 0;       // €5,780

    public $lastSyncedAccountId;
    public $isSyncing = false;  // idle, syncing, done
    public $syncStartTime = null; // 👇 Nueva propiedad para guardar cuándo empezamos
    public $selectedTimeframe = 'all'; // ← NUEVO



    public $timeframes = [
        '1h' => ['minutes' => 60, 'format' => 'H:i'],
        '24h' => ['hours' => 24, 'format' => 'd H:i'],
        '7d' => ['days' => 7, 'format' => 'd MMM'],
        'all' => ['all' => true, 'format' => 'd/m H:i']
    ];

    public function mount()
    {
        $user = Auth::user();
        $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->get();
        $this->selectedAccount = $this->accounts->first(); // ← Array[0]
        $this->updateData();
    }

    /**
     * 🔥 ESTA ES LA FUNCIÓN QUE QUERÍAS EJECUTAR
     * Aquí pones toda la lógica post-job.
     */
    public function onSyncCompleted()
    {
        // Ejemplo de lógica:
        $balance = $this->selectedAccount->balance;

        // Notificar usuario
        $this->updateData();
        $this->dispatch('timeframe-updated', timeframe: 'all');

        session()->flash('message', "✅ Sync finalizado. Nuevo balance: $balance");

        Log::info("Livewire: Lógica post-sync ejecutada correctamente.");
    }


    //* Para modificar el timeframe del grafico
    public function setTimeframe($timeframe) // ← NUEVO MÉTODO
    {
        $this->selectedTimeframe = $timeframe;
        $this->loadBalanceChart(); // ← Recarga gráfico filtrado
        $this->dispatch('timeframe-updated', timeframe: $timeframe);
    }

    public function refreshData()
    {
        $this->updateData();  // Tu método existente
        $this->isSyncing = false;
        session()->flash('message', '✅ Sync completado');
    }

    /**
     * Esta función es llamada automáticamente por wire:poll cada X segundos
     * MIENTRAS $isSyncing sea true.
     */
    public function checkSyncStatus()
    {
        // Forzamos fresh() para traer los datos reales de la DB, no de la caché
        $this->selectedAccount = $this->selectedAccount->fresh();

        $updatedAt = $this->selectedAccount->updated_at;

        Log::info('Verificando...', [
            'db_updated' => $updatedAt->toDateTimeString(),
            'start_time' => $this->syncStartTime->toDateTimeString(),
            'error_en_db' => $this->selectedAccount->sync_error
        ]);

        // Usamos greaterThanOrEqualTo para evitar el bloqueo del mismo segundo
        if ($updatedAt->greaterThanOrEqualTo($this->syncStartTime)) {

            // IMPORTANTE: Si es el mismo segundo exacto, necesitamos verificar 
            // si el Job realmente hizo algo (o hubo error o se actualizó last_sync)
            if ($this->selectedAccount->sync_error) {
                $this->isSyncing = false;
                session()->flash('error', '🚫 Sync falló: ' . $this->selectedAccount->sync_error_message);
                return;
            }

            // Si el last_sync es reciente, es que terminó bien
            if ($this->selectedAccount->last_sync && $this->selectedAccount->last_sync->greaterThanOrEqualTo($this->syncStartTime)) {
                $this->isSyncing = false;
                $this->onSyncCompleted();
                return;
            }
        }
    }

    private function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? sprintf('%dh %02dm', $hours, $mins) : $mins . 'm';
    }




    public function syncSelectedAccount(): void
    {

        // 1. Limpiamos el estado en la base de datos ANTES de disparar el Job
        $this->selectedAccount->update([
            'sync_error' => false,
            'sync_error_message' => null,
        ]);

        // 1. Inicia el proceso
        $this->isSyncing = true;
        $this->syncStartTime = Carbon::now();

        // 2. Manda el Job a la cola
        SyncMt5Account::dispatch($this->selectedAccount);
    }
    public function changeAccount($accountId)
    {
        $this->selectedAccount = $this->accounts->firstWhere('id', $accountId);
        $this->updateData();
        $this->dispatch('timeframe-updated', timeframe: 'all');
    }



    // * Actualizar la data
    private function updateData()
    {
        if ($this->selectedAccount) {
            // ← CALCULA P&L real de trades
            $this->totalPnl = $this->selectedAccount->trades()
                ->sum('pnl');
            Log::info("Total PnL calculado: " . $this->totalPnl);

            // ← Actualiza balance con trades REALES
            $this->selectedAccount->current_balance = $this->selectedAccount->initial_balance + $this->totalPnl;
            $this->selectedAccount->save();

            $this->firstTradeDate = $this->selectedAccount->trades()
                ->orderBy('exit_time', 'asc')
                ->value('exit_time');

            $this->calculateStatistics();

            // ← Carga gráfico de balance

            $this->loadBalanceChart();
        }
    }

    private function calculateStatistics()
    {
        $trades = $this->selectedAccount->trades();

        // Query eficiente UNA SOLA VEZ para todas las stats
        $stats = $trades->selectRaw('
        COUNT(*) as total_trades,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
        AVG(duration_minutes) as avg_duration_minutes,
        MAX(pnl) as max_win,
        MIN(pnl) as max_loss')->first();

        $this->totalTrades = $stats->total_trades; // Total de Trades
        $this->winRate = $this->totalTrades > 0 ? round(($stats->winning_trades / $this->totalTrades) * 100, 1) : 0; // % de trades ganadores

        // Tiempo medio retención
        $this->avgDurationMinutes = round($stats->avg_duration_minutes ?? 0);
        $this->avgDurationFormatted = $this->formatDuration($this->avgDurationMinutes);

        // 🆕 Ganancia y pérdida más grandes
        $this->maxWin = $stats->max_win ?? 0;
        $this->maxLoss = abs($stats->max_loss ?? 0); // Positivo para mostrar

        // 🆕 1. SÍMBOLO MÁS OPERADO
        $topAsset = $this->selectedAccount->trades()
            ->join('trade_assets', 'trades.trade_asset_id', '=', 'trade_assets.id')
            ->whereNotNull('trades.exit_time')
            ->selectRaw('trade_assets.symbol, COUNT(*) as trade_count')
            ->groupBy('trade_assets.id', 'trade_assets.symbol')
            ->orderByDesc('trade_count')
            ->first();

        $this->topAsset = $topAsset ? $topAsset->symbol : 'N/A';

        // 🆕 DÍAS DE TRADING (día con al menos 1 entry_time)
        $tradingDays = $this->selectedAccount->trades()
            ->whereNotNull('entry_time')
            ->selectRaw('COUNT(DISTINCT DATE(entry_time)) as trading_days')
            ->value('trading_days');

        $this->tradingDays = $tradingDays ?? 0;

        // 🆕 Ganancia y Pérdida MEDIA (sin ARRR)
        $avgStats = $this->selectedAccount->trades()
            ->whereNotNull('exit_time')
            ->whereNotNull('pnl')
            ->selectRaw('
            AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
            AVG(CASE WHEN pnl < 0 THEN ABS(pnl) END) as avg_loss_abs
        ')
            ->first();

        $this->avgWinTrade = round($avgStats->avg_win ?? 0, 2);
        $this->avgLossTrade = round($avgStats->avg_loss_abs ?? 0, 2);

        // ARRR calculado a partir de medias
        $this->arr = $this->avgLossTrade > 0 ?
            round($this->avgWinTrade / $this->avgLossTrade, 2) : 0;

        // 🆕 ANTIGÜEDAD DE LA CUENTA (días desde funded_date)
        $accountAgeDays = Carbon::parse($this->selectedAccount->funded_date)
            ->diffInDays(now());

        $this->accountAgeDays = $accountAgeDays;
        $this->accountAgeFormatted = $this->formatAge($accountAgeDays);

        // 🆕 FACTOR DE BENEFICIO (Profit Factor)
        $profitFactorStats = $this->selectedAccount->trades()
            ->whereNotNull('exit_time')
            ->whereNotNull('pnl')
            ->selectRaw('
            SUM(CASE WHEN pnl > 0 THEN pnl ELSE 0 END) as gross_profit,
            SUM(CASE WHEN pnl < 0 THEN ABS(pnl) ELSE 0 END) as gross_loss
        ')
            ->first();

        $this->grossProfit = round($profitFactorStats->gross_profit ?? 0, 2);
        $this->grossLoss = round($profitFactorStats->gross_loss ?? 0, 2);

        // Profit Factor = Gross Profit / Gross Loss
        $this->profitFactor = $this->grossLoss > 0 ?
            round($this->grossProfit / $this->grossLoss, 4) : 0;  // 4 decimales como 0.7892

    }

    private function formatAge($days)
    {
        if ($days >= 365) {
            $years = floor($days / 365);
            return $years . 'a ' . ($days % 365) . 'd';
        }
        if ($days >= 30) {
            $months = floor($days / 30);
            return $months . 'm ' . ($days % 30) . 'd';
        }
        return $days . ' días';
    }

    private function loadBalanceChart() // ← MODIFICAR existente
    {
        $trades = $this->selectedAccount->trades()
            ->when($this->selectedTimeframe !== 'all', function ($query) {
                $config = $this->timeframes[$this->selectedTimeframe];
                if (isset($config['minutes'])) {
                    $query->where('exit_time', '>=', now()->subMinutes($config['minutes']));
                } elseif (isset($config['hours'])) {
                    $query->where('exit_time', '>=', now()->subHours($config['hours']));
                } elseif (isset($config['days'])) {
                    $query->where('exit_time', '>=', now()->subDays($config['days']));
                }
            })
            ->orderBy('exit_time')
            ->get();


        $labels = ['Inicio'];
        $balanceData = [$this->selectedAccount->initial_balance];
        $currentBalance = $this->selectedAccount->initial_balance;
        $format = $this->timeframes[$this->selectedTimeframe]['format'] ?? 'd/m H:i';

        foreach ($trades as $trade) {
            $currentBalance += $trade->pnl;
            $labels[] = $trade->exit_time->format($format);
            $balanceData[] = $currentBalance;
        }

        $this->balanceChartData = [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Balance',
                'data' => $balanceData,
                'borderColor' => 'rgb(16, 185, 129)',
                'backgroundColor' => 'rgba(16, 185, 129, 0.3)',
                'fill' => 'origin',
                'tension' => 0.4,
                'pointBackgroundColor' => 'rgb(16, 185, 129)'
            ]]
        ];
    }



    public function render()
    {
        return view('livewire.account-page');
    }
}
