<?php

namespace App\Livewire;

use App\AuthActions;
use App\Livewire\Forms\LogCreateForm;
use App\Livewire\Forms\LogFilterForm;
use App\LogActions;
use App\Models\Log;
use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Logs extends Component
{
    use LogActions;
    use AuthActions;
    public LogFilterForm $filters;
    public LogCreateForm $logForm;
    public $towns;
    public $users;

    // Permisos de Usuario
    public $canRead = false;
    public $canWrite = false;
    public $canDelete = false;
    public $canDownload = false;
    public $isSuperAdmin = false;
    private $section = 'logs';


    public function mount()
    {
        $user = Auth::user();
        $this->checkPerms($user);
        $this->towns = Town::all();
        $this->users = User::all();

        $this->insertLog(
            Auth::user()->id,
            null,
            __('logging.log'), //Formulario
            __('logging.view'), // Accion
            __('logging.view_logs'), // Descripcion
        );
    }

    private function checkPerms($user)
    {
        $this->canRead = $this->userCan($user, $this->section, 'r');
        $this->canWrite = $this->userCan($user, $this->section, 'w');
        $this->canDelete = $this->userCan($user, $this->section, 'd');
        $this->canDownload = $this->userCan($user, $this->section, 'x');
    }


    public function findByID($id)
    {
        $log = Log::with(['user:id,name', 'town:id,town'])->find($id);
        $this->logForm->id = $log->id;
        $this->logForm->date = $log->created_at;
        $this->logForm->username = $log->user->name;
        if ($log->town != null) {
            $this->logForm->town_name = $log->town->town;
        }
        $this->logForm->form = $log->form;
        $this->logForm->action = $log->action;
        $this->logForm->description = $log->description;
        $this->insertLog(
            Auth::user()->id,
            null,
            __('logging.log'),
            __('logging.select'),
            __('logging.select_log') . $id
        );
    }


    public function render()
    {
        return view('livewire.logs');
    }
}
