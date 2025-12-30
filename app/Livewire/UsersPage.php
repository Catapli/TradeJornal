<?php

namespace App\Livewire;

use App\AuthActions;
use App\LogActions;
use App\Models\Role;
use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class UsersPage extends Component
{
    use LogActions;
    use AuthActions;
    public $filter_name;
    public $filter_town;
    public $filter_active;

    // Permisos de Usuario
    public $canRead = false;
    public $canWrite = false;
    public $canDelete = false;
    public $canDownload = false;
    public $isSuperAdmin = false;


    public $id_user;
    public $active;

    public $town_selected;
    public $towns;
    public $email;
    public $user;
    public $password;
    public $repeat_password;
    public $roles;
    public $role_selected;

    private $section = 'users';

    public function mount()
    {
        $user = Auth::user();
        $this->checkPerms($user);
        $this->isSuperAdmin = $this->isSuperAdmin($user);
        if ($this->isSuperAdmin) {
            $this->towns = Town::all();
        } else {
            $this->towns = Town::where('id', $user->town_id)->get();
        }
        $this->roles = Role::where('name', '!=', 'superadmin')->get();
        $this->insertLog(
            Auth::user()->id,
            null,
            __('logging.user'),         //Formulario
            __('logging.view'),       // Accion
            __('logging.view_users') // Descripcion
        );
    }

    private function checkPerms($user)
    {
        $this->canRead = $this->userCan($user, $this->section, 'r');
        $this->canWrite = $this->userCan($user, $this->section, 'w');
        $this->canDelete = $this->userCan($user, $this->section, 'd');
        $this->canDownload = $this->userCan($user, $this->section, 'x');
    }


    public function findUserById()
    {
        $user  = User::with('town:id,town', 'role:id,label')->select('id', 'name', 'email', 'town_id', 'active', 'role_id')->where('id', $this->id_user)->first();

        $this->email = $user->email;
        $this->active = $user->active;
        $this->user = $user->name;
        if ($user->town != null) {
            $this->town_selected = $user->town->id;
        } else {
            $this->town_selected = "";
        }

        if ($user->role != null) {
            $this->role_selected = $user->role_id;
        } else {
            $this->role_selected = "";
        }
        $this->insertLog(
            Auth::user()->id,
            null,
            __('logging.user'),         //Formulario
            __('logging.select'),       // Accion
            __('logging.select_register_user') . $this->id_user // Descripcion
        );
    }

    public function updateUser()
    {
        $user = User::find($this->id_user);
        $user->name = $this->user;
        $user->town_id = $this->town_selected;
        $user->active = $this->active;
        $user->role_id = $this->role_selected;
        $user->save();
        $this->insertLog(
            Auth::user()->id,   // usuario que hace la accion
            null,               // municipio al que se le hace la accion
            __('logging.user'), // modulo al que pertenece la accion
            __('logging.update'),                               //  Accion que se realiza
            __('logging.update_user') . $this->id_user         // Descripcion de la accion
        );
        $this->showAlert('success', 'update_user_ok', 'update_user', 'update');
    }

    public function insertUser()
    {
        try {

            $user = User::create([
                'name' => $this->user, // Mapea el campo 'user' al atributo 'name'
                'email' => $this->email,
                'town_id' => $this->town_selected, // Asigna la ciudad seleccionada
                'password' => Hash::make($this->password), // Hashea la contraseña
                'active' => $this->active, // Asigna el estado activo
                'role_id' => $this->role_selected,
            ]);
            $this->insertLog(
                Auth::user()->id,   // usuario que hace la accion
                null,               // municipio al que se le hace la accion
                __('logging.user'), // modulo al que pertenece la accion
                __('logging.insert'),                               //  Accion que se realiza
                __('logging.insert_user') . $this->user         // Descripcion de la accion
            );
            $this->showAlert('success', 'insert_user_ok', 'insert_user', 'insert');
        } catch (\Throwable $th) {
            Log::error('Error en UsersPage::insertUser', [
                'message' => $th->getMessage(),
            ]);
            return $this->showAlert('error', 'insert_user_error', 'insert_user', 'insert');
        }
    }

    public function deleteUser()
    {
        $user = User::find($this->id_user);
        $user->delete();
        $this->insertLog(
            Auth::user()->id,   // usuario que hace la accion
            null,               // municipio al que se le hace la accion
            __('logging.user'), // modulo al que pertenece la accion
            __('logging.delete'),                               //  Accion que se realiza
            __('logging.delete_user') . $this->user         // Descripcion de la accion
        );
        $this->showAlert('success', 'delete_user_ok', 'delete_user', 'delete');
    }

    public function showAlert($type, $message, $title, $event)
    {
        $this->dispatch('show-alert', [
            'type' => $type,
            'message' => $message,
            'title' => $title,
            'event' => $event
        ]);
    }

    public function render()
    {
        return view('livewire.users-page');
    }
}
