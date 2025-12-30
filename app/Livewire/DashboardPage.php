<?php

namespace App\Livewire;

use App\Models\Alert;
use App\Models\Traffic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPage extends Component
{
    public function mount() {}


    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
