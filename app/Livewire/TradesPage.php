<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Trade;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\Mistake;
use Illuminate\Support\Facades\Auth;

class TradesPage extends Component
{
    use WithPagination;

    // --- BÚSQUEDA Y FILTROS ---
    public $search = '';

    // Inicializamos todas las claves para evitar errores, pero usaremos protección en la query
    public $filters = [
        'account_id' => '',
        'strategy_id' => '',
        'mistake_id' => '',
        'result' => '',
        'direction' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    public $showFilters = false;

    // --- SELECCIÓN Y EDICIÓN MASIVA ---
    public $selectedTrades = [];
    public $selectAll = false;

    public $showBulkModal = false;
    public $bulkStrategyId = '';
    public $bulkMistakes = []; // Array de IDs de errores

    // --- LISTENERS Y RESET ---
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset('filters'); // Resetea a los valores por defecto definidos arriba
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Selecciona solo los IDs visibles en la página actual (rendimiento)
            $this->selectedTrades = $this->trades->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedTrades = [];
        }
    }

    // --- QUERY MAESTRA ---
    public function getTradesProperty()
    {
        return Trade::query()
            // 1. Seguridad: Solo trades del usuario
            ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))

            // 2. Optimización: Cargar relaciones necesarias
            ->with(['account', 'strategy', 'tradeAsset', 'mistakes'])

            // 3. Buscador Global
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('ticket', 'like', '%' . $this->search . '%')
                        ->orWhereHas('tradeAsset', fn($a) => $a->where('name', 'like', '%' . $this->search . '%'));
                });
            })

            // 4. Filtros Blindados (Usamos ?? null para evitar errores si falta la clave)
            ->when($this->filters['account_id'] ?? null, fn($q) => $q->where('account_id', $this->filters['account_id']))
            ->when($this->filters['strategy_id'] ?? null, fn($q) => $q->where('strategy_id', $this->filters['strategy_id']))

            // Filtro Especial: Trades que tengan X error específico
            ->when($this->filters['mistake_id'] ?? null, function ($q) {
                // Buscamos dentro de la relación 'mistakes' donde el ID coincida
                $q->whereHas('mistakes', fn($m) => $m->where('mistakes.id', $this->filters['mistake_id']));
            })

            ->when($this->filters['direction'] ?? null, fn($q) => $q->where('direction', $this->filters['direction']))
            ->when($this->filters['date_from'] ?? null, fn($q) => $q->whereDate('entry_time', '>=', $this->filters['date_from']))
            ->when($this->filters['date_to'] ?? null, fn($q) => $q->whereDate('entry_time', '<=', $this->filters['date_to']))
            ->when($this->filters['result'] ?? null, function ($q) {
                $res = $this->filters['result'];
                if ($res === 'win') $q->where('pnl', '>', 0);
                if ($res === 'loss') $q->where('pnl', '<', 0);
            })

            ->orderBy('exit_time', 'desc')
            ->paginate(20);
    }

    // --- EJECUCIÓN MASIVA ---
    public function executeBulkUpdate()
    {
        if (empty($this->selectedTrades)) return;

        // 1. Actualizar Estrategia
        if ($this->bulkStrategyId) {
            Trade::whereIn('id', $this->selectedTrades)
                ->update(['strategy_id' => $this->bulkStrategyId]);
        }

        // 2. Añadir Errores (Mistakes)
        if (!empty($this->bulkMistakes)) {
            $trades = Trade::whereIn('id', $this->selectedTrades)->get();
            foreach ($trades as $trade) {
                // syncWithoutDetaching añade los nuevos sin borrar los viejos
                $trade->mistakes()->syncWithoutDetaching($this->bulkMistakes);
            }
        }

        // Limpieza final
        $this->showBulkModal = false;
        $this->selectedTrades = [];
        $this->selectAll = false;
        $this->bulkStrategyId = '';
        $this->bulkMistakes = [];

        $this->dispatch('notify', 'Operaciones actualizadas correctamente.');
    }

    public function render()
    {
        // Cargar auxiliares para los selectores
        $mistakes = Mistake::where('user_id', Auth::id())
            ->orWhereNull('user_id')
            ->orderBy('name')
            ->get();

        return view('livewire.trades-page', [
            'accounts' => Account::where('user_id', Auth::id())->get(),
            'strategiesList' => Strategy::where('user_id', Auth::id())->orderBy('name')->get(),
            'mistakesList' => $mistakes,
        ]);
    }
}
