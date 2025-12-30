<?php

namespace App\Livewire;

use App\AuthActions;
use App\LogActions;
use App\Models\Role;
use App\Models\RoleSectionPermission;
use App\Models\Section;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;

class RolsPage extends Component
{
    use LogActions;
    use AuthActions;
    // public $rols;
    public $sections;


    public $id_rol;
    public $name;
    public $label;

    public $permissions = [];

    // Permisos de Usuario
    public $canRead = false;
    public $canWrite = false;
    public $canDelete = false;
    public $canDownload = false;
    public $isSuperAdmin = false;
    private $section = 'rols';


    public function mount()
    {
        $this->sections = Section::all();
        $user = Auth::user();
        $this->checkPerms($user);
        $this->permissions = [];
        foreach ($this->sections as $section) {
            $this->permissions[$section->id] = [
                'can_read' => false,
                'can_write' => false,
                'can_delete' => false,
                'can_download' => false,
            ];
        }
        $this->insertLog(
            Auth::user()->id,
            null,
            __('logging.rols'), //Formulario
            __('logging.view'), // Accion
            __('logging.view_rols'), // Descripcion
        );
    }

    private function checkPerms($user)
    {
        $this->canRead = $this->userCan($user, $this->section, 'r');
        $this->canWrite = $this->userCan($user, $this->section, 'w');
        $this->canDelete = $this->userCan($user, $this->section, 'd');
        $this->canDownload = $this->userCan($user, $this->section, 'x');
    }

    // Método cuando se pulsa sobre un registro en la tabla
    public function findByID($id)
    {
        try {
            $role = Role::where('id', $id)->first();
            $this->id_rol = $role->id;
            $this->name = $role->name;
            $this->label = $role->label;

            // ✅ Paso 1: Inicializar TODAS las secciones con false
            $this->permissions = [];
            foreach ($this->sections as $section) {
                $this->permissions[$section->id] = [
                    'can_read' => false,
                    'can_write' => false,
                    'can_delete' => false,
                    'can_download' => false,
                ];
            }

            // ✅ Paso 2: Sobrescribir con los permisos reales del rol
            foreach ($role->permissions as $perm) {
                // Solo si la sección existe en tu lista (por si hay basura en la BD)
                if (isset($this->permissions[$perm->section_id])) {
                    $this->permissions[$perm->section_id] = [
                        'can_read' => $perm->can_read,
                        'can_write' => $perm->can_write,
                        'can_delete' => $perm->can_delete,
                        'can_download' => $perm->can_download,
                    ];
                }
            }
            $this->insertLog(
                Auth::user()->id,
                null,
                __('logging.rols'), //Formulario
                __('logging.select'), // Accion
                __('logging.select_rols'), // Descripcion
            );
        } catch (Exception $th) {
            Log::error('Error en RolsPage::findByID', [
                'message' => $th->getMessage(),
            ]);
            return  $this->showAlert('error', 'find_role_error', 'find_role', 'insert');
        }
    }

    public function insertRol()
    {
        try {
            $nameRol = str_replace(' ', '_', strtolower($this->name));
            $rolExist = Role::where('name', $nameRol)->first();
            if ($rolExist) {
                return $this->showAlert('error', 'name_rol_repeat', 'insert_role', 'insert');
            }
            $rol = Role::create([
                'name' => $nameRol, // Mapea el campo 'user' al atributo 'name'
                'label' => $this->label, // Asigna la ciudad seleccionada
            ]);

            foreach ($this->permissions as $sectionId => $perms) {
                RoleSectionPermission::create([
                    'role_id' => $rol->id,
                    'section_id' => $sectionId,
                    'can_read' => $perms['can_read'] ?? false,
                    'can_write' => $perms['can_write'] ?? false,
                    'can_delete' => $perms['can_delete'] ?? false,
                    'can_download' => $perms['can_download'] ?? false,
                ]);
            }


            $this->insertLog(
                Auth::user()->id,   // usuario que hace la accion
                null,               // municipio al que se le hace la accion
                __('logging.rols'), // modulo al que pertenece la accion
                __('logging.insert'),                               //  Accion que se realiza
                __('logging.insert_role') . $this->name         // Descripcion de la accion
            );
            $this->showAlert('success', 'insert_rol_ok', 'insert_role', 'insert');
        } catch (\Throwable $th) {
            Log::error('Error en RolsPage::insertRol', [
                'message' => $th->getMessage(),
            ]);
            $this->showAlert('error', 'insert_rol_error', 'insert_role', 'insert');
        }
    }

    public function updateRol()
    {
        try {
            $nameRol = str_replace(' ', '_', strtolower($this->name));
            $rolExist = Role::where('name', $nameRol)->where('id', '!=', $this->id_rol)->first();
            if ($rolExist) {
                return $this->showAlert('error', 'name_rol_repeat', 'update_role', 'insert');
            }
            $rol = Role::where('id', $this->id_rol)->first();
            $rol->update([
                'name' => $nameRol,
                'label' => $this->label,
            ]);


            // 🔁 Sincronizar permisos: eliminar los que ya no están, actualizar los existentes, crear los nuevos
            $permisosNuevos = [];
            foreach ($this->permissions as $sectionId => $perms) {
                $permisosNuevos[$sectionId] = [
                    'can_read' => $perms['can_read'] ?? false,
                    'can_write' => $perms['can_write'] ?? false,
                    'can_delete' => $perms['can_delete'] ?? false,
                    'can_download' => $perms['can_download'] ?? false,
                ];
            }

            $sectionIdsNuevos = array_keys($permisosNuevos);

            // Eliminar permisos que ya no están en la lista
            $rol->permissions()->whereNotIn('section_id', $sectionIdsNuevos)->delete();

            // Actualizar o crear los permisos actuales
            foreach ($permisosNuevos as $sectionId => $data) {
                RoleSectionPermission::updateOrCreate(
                    ['role_id' => $rol->id, 'section_id' => $sectionId],
                    $data
                );
            }

            $this->insertLog(
                Auth::user()->id,   // usuario que hace la accion
                null,               // municipio al que se le hace la accion
                __('logging.rols'), // modulo al que pertenece la accion
                __('logging.update'),                               //  Accion que se realiza
                __('logging.update_role') . $this->name         // Descripcion de la accion
            );
            $this->showAlert('success', 'update_rol_ok', 'update_role', 'insert');
        } catch (\Throwable $th) {
            Log::error('Error en RolsPage::updateRol', [
                'message' => $th->getMessage(),
            ]);
            $this->showAlert('error', 'update_rol_error', 'update_role', 'insert');
        }
    }

    // Mostrar Alertas
    public function showAlert($type, $message, $title, $event)
    {
        $this->dispatch('show-alert', [
            'type' => $type,
            'message' => $message,
            'title' => $title,
            'event' => $event
        ]);
    }

    public function deleteRol()
    {

        try {
            $role = Role::where('id', $this->id_rol)->first();
            $count = $role->hasUsers()->count();
            if ($count > 0) {
                return $this->showAlert('error', 'exist_users_with_rol', 'delete_role', 'insert');
            }

            $role->delete();
            $this->insertLog(
                Auth::user()->id,   // usuario que hace la accion
                null,               // municipio al que se le hace la accion
                __('logging.rols'), // modulo al que pertenece la accion
                __('logging.delete'),                               //  Accion que se realiza
                __('logging.delete_role') . $this->name         // Descripcion de la accion
            );
            return $this->showAlert('success', 'delete_rol_ok', 'delete_role', 'insert');
        } catch (\Throwable $th) {
            Log::error('Error en RolsPage::deleteRol', [
                'message' => $th->getMessage(),
            ]);
            $this->showAlert('error', 'delete_rol_error', 'delete_role', 'insert');
        }
    }




    public function render()
    {
        return view('livewire.rols-page');
    }
}
