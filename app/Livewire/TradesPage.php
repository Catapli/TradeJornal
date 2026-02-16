<?php

namespace App\Livewire;

use App\Jobs\RecalculateStrategyStatsJob;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Trade;
use App\Models\Account;
use App\Models\Strategy;
use App\Models\Mistake;
use App\Models\TradeAsset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use App\LogActions; // <-- IMPORTANTE: Tu Trait
use Illuminate\Support\Facades\Log;

class TradesPage extends Component
{
    use WithPagination;
    use LogActions; // <-- Uso del Trait

    // --- BÚSQUEDA Y FILTROS ---
    public $search = '';

    public $filters = [
        'account_id' => '',
        'strategy_id' => '',
        'mistake_id' => '',
        'result' => '',
        'direction' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    // --- SELECCIÓN Y EDICIÓN MASIVA ---
    public $selectedTrades = [];
    public $selectAll = false;

    public $bulkStrategyId = '';
    public $bulkMistakes = [];

    // --- ESTADO DEL FORMULARIO CRUD ---
    public $isEditMode = false;
    public $editingTradeId = null;

    public $form = [
        'account_id' => '',
        'trade_asset_id' => '',
        'strategy_id' => '',
        'ticket' => '',
        'direction' => 'long',
        'entry_price' => '',
        'exit_price' => '',
        'size' => '',
        'pnl' => '',
        'entry_time' => '',
        'exit_time' => '',
        'notes' => '',
        'mae_price' => '',
        'mfe_price' => '',
    ];

    // Constante para logs
    const COMPONENT_FORM = 'TradesPage';

    protected function rules()
    {
        return [
            'form.account_id' => 'required|exists:accounts,id',
            'form.trade_asset_id' => 'required|exists:trade_assets,id',
            'form.strategy_id' => 'nullable|exists:strategies,id',
            'form.direction' => 'required|in:long,short',
            'form.entry_price' => 'required|numeric|min:0',
            'form.exit_price' => 'required|numeric|min:0',
            'form.size' => 'required|numeric|min:0',
            'form.pnl' => 'required|numeric',
            'form.entry_time' => 'required|date',
            'form.exit_time' => 'required|date|after_or_equal:form.entry_time',
            'form.notes' => 'nullable|string',
            'form.mae_price' => 'nullable|numeric',
            'form.mfe_price' => 'nullable|numeric',
        ];
    }

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
        $this->reset('filters');
        $this->resetPage();
    }

    // --- LÓGICA CRUD ---

    // 1. CREAR
    public function create()
    {
        // Aunque Alpine hace el reset visual, aquí aseguramos limpieza de errores
        $this->resetValidation();
        $this->reset('form', 'editingTradeId', 'isEditMode');

        // Defaults seguros
        $this->form['entry_time'] = now()->format('Y-m-d\TH:i');
        $this->form['direction'] = 'long';
    }

    // 2. EDITAR
    public function edit($id)
    {
        $this->resetValidation();

        try {
            $trade = Trade::where('id', $id)
                ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                ->firstOrFail();

            $this->editingTradeId = $trade->id;
            $this->isEditMode = true;

            $this->form = $trade->only([
                'account_id',
                'trade_asset_id',
                'strategy_id',
                'ticket',
                'direction',
                'entry_price',
                'exit_price',
                'size',
                'pnl',
                'notes',
                'mae_price',
                'mfe_price'
            ]);

            $this->form['entry_time'] = $trade->entry_time ? \Carbon\Carbon::parse($trade->entry_time)->format('Y-m-d\TH:i') : '';
            $this->form['exit_time'] = $trade->exit_time ? \Carbon\Carbon::parse($trade->exit_time)->format('Y-m-d\TH:i') : '';

            $this->dispatch('open-form-modal');
        } catch (\Throwable $e) {
            $this->logError($e, 'Edit', self::COMPONENT_FORM, "ID solicitado: $id");
            $this->dispatch('error', __('labels.error_loading_trade'));
        }
    }

    // 3. GUARDAR
    public function save()
    {
        // La validación lanza su propia excepción que Livewire captura automáticamente para mostrar errores en UI.
        // No la envolvemos en try-catch porque no es un "error de sistema".
        $this->validate();

        try {
            // Lógica de Negocio
            $duration = 0;
            if (!empty($this->form['exit_time'])) {
                $entry = \Carbon\Carbon::parse($this->form['entry_time']);
                $exit = \Carbon\Carbon::parse($this->form['exit_time']);
                $duration = $exit->diffInMinutes($entry);
            }

            // SOLUCIÓN: Limpiar campos vacíos (convertir '' a null)
            $cleanData = collect($this->form)->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

            // Calculamos % antes de guardar
            $account = Account::find($this->form['account_id']);
            $balance = $account->initial_balance > 0 ? $account->initial_balance : 1;

            $data = array_merge($cleanData, ['duration_minutes' => $duration]);

            // Inyectamos el cálculo en el array de datos
            $data['pnl_percentage'] = ($this->form['pnl'] / $balance) * 100;

            if ($this->isEditMode) {
                $trade = Trade::findOrFail($this->editingTradeId);

                // Seguridad adicional: Verificar propiedad
                if ($trade->account->user_id !== Auth::id()) {
                    throw new \Exception(__('labels.error_loading_trade') . $this->editingTradeId);
                }

                $trade->update($data);

                $actionName = 'Update Trade';
                $msg = 'Operación actualizada correctamente.';
            } else {
                Trade::create($data);
                $actionName = 'Create Trade';
                $msg = 'Operación creada correctamente.';
            }

            // Auditoría de éxito
            $this->insertLog($actionName, self::COMPONENT_FORM, "Ticket: {$this->form['ticket']} | PnL: {$this->form['pnl']}");

            // UI Feedback
            $this->dispatch('close-form-modal');
            $this->dispatch('notify', $msg);
        } catch (\Throwable $e) {
            // Manejo de Errores
            Log::error($e);
            $this->logError($e, 'Save Trade', self::COMPONENT_FORM, json_encode($this->form));
            $this->dispatch('error', __('labels.error_saving_trade'));
        }
    }

    // 4. ELIMINAR
    public function delete($id)
    {
        try {
            $trade = Trade::where('id', $id)
                ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                ->firstOrFail();

            $ticket = $trade->ticket;
            $trade->delete();

            $this->insertLog('Delete Trade', self::COMPONENT_FORM, "ID: $id | Ticket: $ticket");
            $this->dispatch('notify', __('labels.trade_deleted_ok'));

            $this->selectedTrades = array_diff($this->selectedTrades, [$id]);
        } catch (\Throwable $e) {
            $this->logError($e, 'Delete Trade', self::COMPONENT_FORM, "ID: $id");
            $this->dispatch('error', __('labels.error_delete_trade'));
        }
    }

    public function updatedSelectAll($value)
    {
        try {
            if ($value) {
                // Usamos la property con try-catch interno, pero aquí accedemos a la query cruda para eficiencia
                // si falla getTradesProperty, esto fallaría, así que lo protegemos
                $this->selectedTrades = $this->trades->pluck('id')->map(fn($id) => (string)$id)->toArray();
            } else {
                $this->selectedTrades = [];
            }
        } catch (\Throwable $e) {
            $this->logError($e, 'Select All Trades', self::COMPONENT_FORM);
            $this->dispatch('error', __('labels.error_selecting_elements'));
        }
    }

    // --- QUERY MAESTRA BLINDADA ---
    public function getTradesProperty()
    {
        try {
            return Trade::query()
                ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                ->with(['account', 'strategy', 'tradeAsset', 'mistakes'])
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('ticket', 'like', '%' . $this->search . '%')
                            ->orWhereHas('tradeAsset', fn($a) => $a->where('name', 'like', '%' . $this->search . '%'));
                    });
                })
                ->when($this->filters['account_id'] ?? null, fn($q) => $q->where('account_id', $this->filters['account_id']))
                ->when($this->filters['strategy_id'] ?? null, fn($q) => $q->where('strategy_id', $this->filters['strategy_id']))
                ->when($this->filters['mistake_id'] ?? null, function ($q) {
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
        } catch (\Throwable $e) {
            // CRÍTICO: Si falla la query principal, la página se pone en blanco (WSOD).
            // Retornamos un paginador vacío y logueamos el error.
            $this->logError($e, 'Get Trades Query', self::COMPONENT_FORM);

            return new LengthAwarePaginator([], 0, 20);
        }
    }

    // --- EJECUCIÓN MASIVA ---
    public function executeBulkUpdate()
    {
        if (empty($this->selectedTrades)) return;

        try {
            $count = count($this->selectedTrades);
            $affectedStrategyIds = [];

            if ($this->bulkStrategyId) {
                // 1. Captura estrategias antiguas ANTES del update (solo las que van a cambiar)
                $oldStrategyIds = Trade::whereIn('id', $this->selectedTrades)
                    ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                    ->where('strategy_id', '!=', $this->bulkStrategyId) // Solo trades que SÍ cambian
                    ->whereNotNull('strategy_id')
                    ->distinct()
                    ->pluck('strategy_id')
                    ->toArray();

                // 2. Update masivo
                Trade::whereIn('id', $this->selectedTrades)
                    ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                    ->update(['strategy_id' => $this->bulkStrategyId]);

                // 3. Estrategias afectadas = antiguas + nueva
                $affectedStrategyIds = array_unique(array_merge($oldStrategyIds, [$this->bulkStrategyId]));
            }

            if (!empty($this->bulkMistakes)) {
                $trades = Trade::whereIn('id', $this->selectedTrades)
                    ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                    ->get();

                foreach ($trades as $trade) {
                    $trade->mistakes()->syncWithoutDetaching($this->bulkMistakes);
                }
            }

            // 4. Dispara recálculo en segundo plano para todas las estrategias afectadas
            foreach ($affectedStrategyIds as $strategyId) {
                $strategy = \App\Models\Strategy::find($strategyId);
                if ($strategy) {
                    \App\Jobs\RecalculateStrategyStatsJob::dispatch($strategy);
                }
            }

            $this->insertLog('Bulk Update', self::COMPONENT_FORM, "Items: $count, Strategies recalculadas: " . count($affectedStrategyIds));

            // Limpieza UI (State PHP)
            $this->selectedTrades = [];
            $this->selectAll = false;
            $this->bulkStrategyId = '';
            $this->bulkMistakes = [];

            // Limpieza UI (Alpine) y Notificación
            $this->dispatch('close-bulk-modal');
            $this->dispatch('notify', __('labels.update_operations_ok'));
        } catch (\Throwable $e) {
            $this->logError($e, 'Bulk Update', self::COMPONENT_FORM, "Selected IDs: " . json_encode($this->selectedTrades));
            $this->dispatch('error', __('labels.update_operations_error'));
        }
    }


    // --- ACCIÓN DE BORRADO MASIVO ---
    public function executeBulkDelete()
    {
        if (empty($this->selectedTrades)) return;

        try {
            $count = count($this->selectedTrades);

            // Borrado masivo seguro: Verificamos que los IDs pertenezcan a cuentas del usuario
            Trade::whereIn('id', $this->selectedTrades)
                ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
                ->delete();

            $this->insertLog('Bulk Delete', self::COMPONENT_FORM, "Eliminados $count trades");

            // Limpieza de estado
            $this->selectedTrades = [];
            $this->selectAll = false;

            // Feedback UI
            $this->dispatch('close-bulk-delete-modal'); // Cierra modal en JS
            $this->dispatch('notify', $count .  __('labels.delete_operations_ok'));
        } catch (\Throwable $e) {
            $this->logError($e, 'Bulk Delete', self::COMPONENT_FORM, "IDs: " . json_encode($this->selectedTrades));
            $this->dispatch('error', __('labels.delete_operations_error'));
        }
    }

    public function render()
    {
        try {
            $mistakes = Mistake::where('user_id', Auth::id())
                ->orWhereNull('user_id')
                ->orderBy('name')
                ->get();

            $assets = TradeAsset::orderBy('symbol')->get();

            return view('livewire.trades-page', [
                'accounts' => Account::where('user_id', Auth::id())->get(),
                'strategiesList' => Strategy::where('user_id', Auth::id())->orderBy('name')->get(),
                'mistakesList' => $mistakes,
                'assetsList' => $assets,
            ]);
        } catch (\Throwable $e) {
            $this->logError($e, 'Render Trades Page', self::COMPONENT_FORM);

            // Retornamos vista con arrays vacíos para evitar error 500 visual
            return view('livewire.trades-page', [
                'accounts' => [],
                'strategiesList' => [],
                'mistakesList' => [],
                'assetsList' => [],
            ]);
        }
    }
}
