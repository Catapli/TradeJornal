<?php

namespace App\Livewire;

use App\AuthActions;
use App\Livewire\Forms\Lists\CollabListForm;
use App\Livewire\Forms\Lists\InterestListForm;
use App\LogActions;
use App\Models\CollaborativeList;
use App\Models\InterestList;
use App\Models\Lists;
use App\Models\LogsInterestList;
use App\Models\Reason;
use App\Models\Town;
use Exception;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class InterestListPage extends Component
{
    use LogActions;
    use AuthActions;

    // Permisos de Usuario
    public $canRead = false;
    public $canWrite = false;
    public $canDelete = false;
    public $canDownload = false;
    public $isSuperAdmin = false;

    public $plate_filter;
    public $town_filter;
    public $reason_filter;

    public InterestListForm $form;

    public $towns;
    public $reasons;
    public $lists;

    private $section = 'lists';


    public function mount()
    {
        $user = Auth::user();
        $this->checkPerms($user);
        $this->isSuperAdmin = $this->isSuperAdmin($user);
        if ($this->isSuperAdmin) {
            $this->towns = Town::where('entity', false)->get();
        } else {
            $this->towns = Town::where('entity', false)->where('id', $user->town_id)->get();
        }
        $this->reasons = Reason::all();
        $this->lists = CollaborativeList::all();

        $this->insertLog(
            Auth::user()->id,
            null,
            __('logging.lists'), //Formulario
            __('logging.view'), // Accion
            __('logging.view_interest_list'), // Descripcion
        );
    }

    private function checkPerms($user)
    {
        $this->canRead = $this->userCan($user, $this->section, 'r');
        $this->canWrite = $this->userCan($user, $this->section, 'w');
        $this->canDelete = $this->userCan($user, $this->section, 'd');
        $this->canDownload = $this->userCan($user, $this->section, 'x');
    }

    public function showAlert($type, $message, $title, $event)
    {
        $this->dispatch('show-alert-list', [
            'type' => $type,
            'message' => $message,
            'title' => $title,
            'event' => $event
        ]);
    }

    public function insert()
    {
        try {
            $interestList = $this->form->create();
            $this->insertLog(
                Auth::user()->id,
                null,
                __('logging.lists'), //Formulario
                __('logging.insert'), // Accion
                __('logging.insert_list'), // Descripcion
            );
            $interestListCopy = InterestList::find($interestList->id);
            $this->insertLogSincronize($interestListCopy->id, 'insert', $interestListCopy->collaborative_list_id, $interestListCopy->toJson());
            return $this->showAlert('success', 'insert_list_ok', 'insert_list', 'insert');
        } catch (Exception $e) {
            Log::error('Error en InterestListPage::insert', [
                'message' => $e->getMessage(),
            ]);
            return  $this->showAlert('error', 'insert_list_error', 'insert_list', 'insert');
        }
    }

    public function update()
    {
        try {
            $list = InterestList::find($this->form->id);
            $list->update($this->form->toArray());
            $this->insertLog(
                Auth::user()->id,
                null,
                __('logging.lists'), //Formulario
                __('logging.update'), // Accion
                __('logging.update_list') . $this->form->id, // Descripcion
            );
            $this->insertLogSincronize($list->id, 'update', $list->collaborative_list_id, $list->toJson());
            return $this->dispatch('update_list');
        } catch (Exception $e) {
            Log::error('Error en InterestListPage::update', [
                'message' => $e->getMessage(),
            ]);
            return  $this->showAlert('error', 'update_list_error', 'insert_list', 'insert');
        }
    }


    public function findByID($id)
    {
        try {
            $list = InterestList::with(['town', 'reason', 'collaborative_list'])->find($id);
            $this->form->fill($list);
            $this->insertLog(
                Auth::user()->id,
                null,
                __('logging.lists'),         //Formulario
                __('logging.select'),       // Accion
                __('logging.select_interest_list') . $id,        // Descripcion
            );

            $this->dispatch('updateMyDatePicker', date: $list->expir_date);
        } catch (Exception $th) {
            return  $this->showAlert('error', 'find_list_error', 'find_list', 'insert');
        }
    }

    private function insertLogSincronize($idList, $changeType, $collaborative_list_id, $json)
    {
        $logSinc = LogsInterestList::create([
            'interest_list_id' => $idList,
            'collaborative_list_id' => $collaborative_list_id,
            'change_type' => $changeType,
            'change_data' => $json,
            'town_id' =>  Auth::user()->town_id,
        ]);
    }

    public function listExist($name, $excludeId = null)
    {
        $query = InterestList::where('name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function deleteList()
    {
        $list = InterestList::find($this->form->id);
        $listCopy = $list;
        $list->delete();
        $this->insertLog(
            Auth::user()->id,   // usuario que hace la accion
            null,               // municipio al que se le hace la accion
            __('logging.user'), // modulo al que pertenece la accion
            __('logging.delete'),                               //  Accion que se realiza
            __('logging.delete_list') . $this->form->list_name         // Descripcion de la accion
        );
        $this->insertLogSincronize($listCopy->id, 'delete', $listCopy->collaborative_list_id, $listCopy->toJson());
        $this->dispatch("delete_list");
        $this->showAlert('success', 'delete_list_ok', 'delete_list', 'delete');
    }

    public function render()
    {
        return view('livewire.interest-list-page');
    }
}
