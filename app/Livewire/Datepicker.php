<?php

namespace App\Livewire;

use Livewire\Component;

class Datepicker extends Component
{
    public $date;

    public $icono;

    public $placeholder;

    public $tooltip;

    public $variable;

    protected $listeners = ['resetDatepicker']; // <-- ESCUCHA EL EVENTO

    public function mount($icono = null, $placeholder = null, $tooltip = null, $variable = null)
    {
        $this->icono = $icono;
        $this->placeholder = $placeholder;
        $this->tooltip = $tooltip;
        $this->variable = $variable;
    }


    public function render()
    {
        return view('livewire.datepicker');
    }
}
