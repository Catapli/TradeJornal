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
    public $totalPnl = 0;
    public $balanceChartData = [
        'labels' => [],
        'datasets' => []
    ];
    public $lastSyncedAccountId;
    public $isSyncing = false;  // idle, syncing, done
    public $firstTradeDate;
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


        // Calcular algo...
        // $this->actualizarEstadisticas();

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
        // Refrescamos modelo para ver si el Job ya tocó la DB
        $this->selectedAccount->refresh();

        // CONDICIÓN DE ÉXITO: 
        // Si la cuenta se actualizó DESPUÉS de que empezamos el sync
        if ($this->selectedAccount->updated_at > $this->syncStartTime) {

            // 1. Detenemos el polling (importante para que deje de preguntar)
            $this->isSyncing = false;

            // 2. 🔥 EJECUTAMOS TU LÓGICA FINAL AQUÍ
            $this->onSyncCompleted();
        }
    }




    public function syncSelectedAccount(): void
    {
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
            $this->loadBalanceChart();
        }
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
