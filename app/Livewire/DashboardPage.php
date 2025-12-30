<?php

namespace App\Livewire;

use App\Models\Alert;
use App\Models\Traffic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPage extends Component
{
    public $winrate = '0%';
    public $pnl = '€0';
    public $accounts = 0;
    public $trades = 0;

    public $recentTrades;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        // Stats de ejemplo (luego reales con DB)
        $this->winrate = '62.5%';
        $this->pnl = '+€2,847';
        $this->accounts = 3;
        $this->trades = 47;

        // Trades de ejemplo (sin DB aún)
        $this->recentTrades = collect([
            (object)['id' => 1, 'pnl' => 247.50, 'asset' => (object)['symbol' => 'EURUSD'], 'direction' => 'long', 'rr_ratio' => 2.3, 'created_at' => now()],
            (object)['id' => 2, 'pnl' => -89.20, 'asset' => (object)['symbol' => 'BTCUSD'], 'direction' => 'short', 'rr_ratio' => 1.8, 'created_at' => now()->subDay()],
            (object)['id' => 3, 'pnl' => 156.00, 'asset' => (object)['symbol' => 'NAS100'], 'direction' => 'long', 'rr_ratio' => 3.1, 'created_at' => now()->subDays(2)],
        ]);
    }



    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
