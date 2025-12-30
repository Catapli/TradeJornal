<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;
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

    //* Para modificar el timeframe del grafico
    public function setTimeframe($timeframe) // ← NUEVO MÉTODO
    {
        $this->selectedTimeframe = $timeframe;
        $this->loadBalanceChart(); // ← Recarga gráfico filtrado
        $this->dispatch('timeframe-updated', timeframe: $timeframe);
    }



    // * Actualizar la data
    private function updateData()
    {
        if ($this->selectedAccount) {
            // ← CALCULA P&L real de trades
            $this->totalPnl = $this->selectedAccount->trades()
                ->where('status', 'closed')
                ->sum('pnl');

            // dd($this->totalPnl);

            // ← Actualiza balance con trades REALES
            $this->selectedAccount->current_balance = $this->selectedAccount->initial_balance + $this->totalPnl;
            $this->selectedAccount->save();
            $this->loadBalanceChart();
        }
    }

    private function loadBalanceChart() // ← MODIFICAR existente
    {
        $trades = $this->selectedAccount->trades()
            ->where('status', 'closed')
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

    public function updatedSelectedAccountId($accountId)
    {
        // ← MAGIA: Cuando cambia select → busca cuenta
        // ← Carga cuenta + trades P&L
        $this->selectedAccount = $this->accounts->firstWhere('id', $accountId);
        $this->updateData();
    }


    public function createAccount()
    {
        $this->showCreateModal = true;
    }

    public function editAccount()
    {
        if (!$this->selectedAccount) {
            session()->flash('error', 'Selecciona una cuenta primero');
            return;
        }
        $this->showEditModal = true;
    }


    public function render()
    {
        return view('livewire.account-page');
    }
}
