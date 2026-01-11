<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class AccountForm extends Form
{
    //

    // Vinculación con Prop Firm
    public $selectedPropFirmID;

    // Vinculación con Programa/Producto de la Prop Firm
    public $selectedProgramID;

    // Vinculación con Tamaño de Cuenta del Programa
    public $size;

    // Vinculacion con el Tipo de Cuenta (ProgramLevel)
    public $programLevelID;
}
