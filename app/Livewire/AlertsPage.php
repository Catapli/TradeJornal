<?php

namespace App\Livewire;

use App\LogActions;
use App\Models\TradeViolation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AlertsPage extends Component
{
    use WithPagination, LogActions;

    public $user;

    public $violations;

    public function mount()
    {
        $this->user = Auth::user();

        $this->violations = TradeViolation::query()
            ->with([
                'trade:id,account_id,trade_asset_id,entry_time,exit_time,pnl,direction,size',
                'trade.tradeAsset:id,name,symbol',
                'trade.account:id,name'
            ])
            ->whereHas('trade.account', function ($q) {
                $q->where('user_id', $this->user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function render()
    {
        return view('livewire.alerts-page');
    }
}
