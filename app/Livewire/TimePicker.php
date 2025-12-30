<?php

namespace App\Livewire;

use Livewire\Component;

class TimePicker extends Component
{

    public $time;

    public $icono;

    public $placeholder;

    public $tooltip;

    public $variable;

    protected $listeners = ['resetTimepicker']; // Escucha evento para reset

    public function mount($icono = null, $placeholder = null, $tooltip = null, $variable = null)
    {
        $this->icono = $icono;
        $this->placeholder = $placeholder;
        $this->tooltip = $tooltip;
        $this->variable = $variable;
    }

    public function render()
    {
        return view('livewire.time-picker');
    }
}
