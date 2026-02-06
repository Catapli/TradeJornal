<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Logs extends Component
{
    use WithPagination;

    // Filtros
    public $search = '';
    public $type = '';      // '', 'error', 'info', 'warning', 'success'
    public $status = '';    // '', 'resolved', 'unresolved'
    public $user_id = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Estado para Modales (se pasan a Alpine, pero usamos propiedades aquí para acciones)
    public $selectedLogId;
    public $resolutionNotes = '';

    // Resetear paginación al filtrar
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedType()
    {
        $this->resetPage();
    }
    public function updatedStatus()
    {
        $this->resetPage();
    }
    public function updatedUserId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Log::with('user') // Eager loading
            ->latest();

        // 🔎 Filtro Búsqueda Texto
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('action', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('form', 'like', '%' . $this->search . '%')
                    ->orWhere('exception_message', 'like', '%' . $this->search . '%');
            });
        }

        // 🏷️ Filtro Tipo
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // ✅ Filtro Estado (Resuelto/No)
        if ($this->status === 'resolved') {
            $query->where('resolved', true);
        } elseif ($this->status === 'unresolved') {
            $query->where('resolved', false)->where('type', 'error');
        }

        // 👤 Filtro Usuario
        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        }

        // 📅 Filtro Fecha
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $logs = $query->paginate(20);

        // Usuarios para el select
        $users = User::orderBy('name')->pluck('name', 'id');

        return view('livewire.admin.logs', [
            'logs' => $logs,
            'users' => $users
        ]);
    }

    // ✅ Acción: Marcar como resuelto
    public function markAsResolved()
    {
        $this->validate([
            'selectedLogId' => 'required|exists:logs,id',
            'resolutionNotes' => 'required|string|min:5'
        ]);

        $log = Log::findOrFail($this->selectedLogId);

        $log->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $this->resolutionNotes . ' (Resuelto por: ' . Auth::user()->name . ')'
        ]);

        $this->reset(['selectedLogId', 'resolutionNotes']);
        $this->dispatch('close-resolve-modal');
        $this->dispatch('show-alert', message: 'Incidencia marcada como resuelta.', type: 'success');
    }

    // 🗑️ Acción: Eliminar Log (Limpieza)
    public function deleteLog($id)
    {
        Log::findOrFail($id)->delete();
        $this->dispatch('show-alert', message: 'Log eliminado.', type: 'success');
    }

    // 🧹 Acción: Limpiar logs antiguos (Info/Success > 30 días)
    public function clearOldLogs()
    {
        $count = Log::whereIn('type', ['info', 'success'])
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->dispatch('show-alert', message: "Limpieza completada. $count logs eliminados.", type: 'success');
    }
}
