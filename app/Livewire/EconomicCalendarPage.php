<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EconomicEvent;
use Carbon\Carbon;

class EconomicCalendarPage extends Component
{
    public $selectedDate;

    // Estos arrays se sincronizarán con Alpine
    public $filterImpact = [];
    public $filterCurrency = [];

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
    }

    public function prevDay()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
    }
    public function nextDay()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }
    public function setToday()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
    }

    public function render()
    {
        $query = EconomicEvent::query()
            ->whereDate('date', $this->selectedDate)
            ->orderBy('time', 'asc');

        // Si el filtro NO está vacío, aplicamos. Si está vacío, traemos todo.
        if (!empty($this->filterImpact)) {
            $query->whereIn('impact', $this->filterImpact);
        }

        if (!empty($this->filterCurrency)) {
            $query->whereIn('currency', $this->filterCurrency);
        }

        $events = $query->get();
        $now = Carbon::now()->format('H:i:s');
        $isToday = Carbon::parse($this->selectedDate)->isToday();

        // Helper de banderas simple para la vista
        $flags = ['USD' => 'us', 'EUR' => 'eu', 'GBP' => 'gb', 'JPY' => 'jp', 'AUD' => 'au', 'CAD' => 'ca', 'CHF' => 'ch', 'NZD' => 'nz', 'CNY' => 'cn'];

        return view('livewire.economic-calendar-page', [
            'events' => $events,
            'now' => $now,
            'isToday' => $isToday,
            'flags' => $flags
        ]);
    }
}
