<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EconomicEvent;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EconomicCalendar extends Component
{
    public $date;
    public $events = [];

    // Formulario para añadir rápido
    public $newTime = '14:30';
    public $newCurrency = 'USD';
    public $newEvent = '';
    public $newImpact = 'high';

    public function mount($date)
    {
        $this->date = $date;
        $this->loadEvents();
    }

    public function loadEvents()
    {
        $this->events = EconomicEvent::where('user_id', Auth::id())
            ->whereDate('date', $this->date)
            ->orderBy('time', 'asc')
            ->get();
    }

    public function addEvent()
    {
        $this->validate([
            'newEvent' => 'required|min:3',
            'newTime' => 'required',
        ]);

        EconomicEvent::create([
            'user_id' => Auth::id(),
            'date' => $this->date,
            'time' => $this->newTime,
            'currency' => $this->newCurrency,
            'event' => $this->newEvent,
            'impact' => $this->newImpact,
        ]);

        $this->newEvent = ''; // Limpiar input
        $this->loadEvents();
    }

    public function deleteEvent($id)
    {
        EconomicEvent::where('id', $id)->where('user_id', Auth::id())->delete();
        $this->loadEvents();
    }

    // Método "Fake" para simular importación automática (Útil para demos)
    // En el futuro aquí conectarías una API real como Financial Modeling Prep
    public function importKeyEvents()
    {
        $presets = [
            ['14:30', 'USD', 'CPI (YoY)', 'high'],
            ['14:30', 'USD', 'Core PPI (MoM)', 'high'],
            ['20:00', 'USD', 'FOMC Meeting', 'high'],
        ];

        foreach ($presets as $p) {
            EconomicEvent::firstOrCreate([
                'user_id' => Auth::id(),
                'date' => $this->date,
                'event' => $p[2]
            ], [
                'time' => $p[0],
                'currency' => $p[1],
                'impact' => $p[3]
            ]);
        }
        $this->loadEvents();
    }

    public function render()
    {
        return view('livewire.economic-calendar');
    }
}
