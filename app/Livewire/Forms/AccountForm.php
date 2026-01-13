<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class AccountForm extends Form
{
    //

    // Nombre de la cuenta
    public $name;
    // Vinculación con Prop Firm
    public $selectedPropFirmID;

    // Vinculación con Programa/Producto de la Prop Firm
    public $selectedProgramID;

    // Vinculación con Tamaño de Cuenta del Programa
    public $size;

    // Vinculacion con el Tipo de Cuenta (ProgramLevel)
    public $programLevelID;

    // Vinculación con el Servidor de la Prop Firm
    public $server;

    // Vinculación con la Sincronización Automática
    public $sync;

    // Vinculación con el Broker/Plataforma de Trading
    public $platformBroker;

    // Vinculación con el Login de la Plataforma
    public $loginPlatform;

    // Vinculación con la Contraseña de la Plataforma
    public $passwordPlatform;
}
