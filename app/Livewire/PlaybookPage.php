<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\Strategy;
use App\LogActions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaybookPage extends Component
{
    use WithFileUploads;
    use LogActions;

    // ── Solo Livewire puede manejar el file upload ──────────────────────────
    public $photo;

    // ── Filtros del listado ─────────────────────────────────────────────────
    public string $search  = '';
    public string $sortBy  = 'stats_total_pnl';
    public string $sortDir = 'desc';

    // ── Propiedades del formulario (vinculadas al modal) ────────────────────
    // Necesitan vivir en Livewire para que @error() funcione en Blade
    public string  $formName        = '';
    public ?string $formDescription = null;
    public string  $formTimeframe   = '';
    public string  $formColor       = '#4F46E5';
    public bool    $formIsmain      = false;
    public array   $formRules       = [];

    // ───────────────────────────────────────────────────────────────────────
    // REGLAS DE VALIDACIÓN CENTRALIZADAS
    // ───────────────────────────────────────────────────────────────────────

    protected function strategyValidationRules(): array
    {
        return [
            'photo'           => 'nullable|image|max:2048',
            'formName'        => 'required|string|max:255',
            'formTimeframe'   => 'required|string|max:10',
            'formColor'       => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'formDescription' => 'nullable|string|max:1000',
            'formRules'       => 'nullable|array',
            'formRules.*'     => 'string|max:500',
        ];
    }

    protected function strategyValidationMessages(): array
    {
        return [
            'formName.required'      => __('labels.name_required_strategy'),
            'formName.max'           => __('labels.name_too_long'),
            'formTimeframe.required' => __('labels.timeframe_required'),
            'formColor.required'     => __('labels.color_required'),
            'formColor.regex'        => __('labels.color_invalid'),
            'photo.image'            => __('labels.photo_invalid'),
            'photo.max'              => __('labels.photo_too_large'),
        ];
    }

    // ───────────────────────────────────────────────────────────────────────
    // HELPERS FORMULARIO — llamados desde Alpine para preparar el modal
    // ───────────────────────────────────────────────────────────────────────

    /**
     * Carga los datos de una estrategia en las propiedades del formulario.
     * Alpine lo llama antes de abrir el modal de edición.
     */
    public function loadForEdit(int $strategyId): void
    {
        try {
            $strategy = Strategy::where('user_id', Auth::id())->findOrFail($strategyId);

            $this->formName        = $strategy->name;
            $this->formDescription = $strategy->description;
            $this->formTimeframe   = $strategy->timeframe;
            $this->formColor       = $strategy->color;
            $this->formIsmain      = (bool) $strategy->is_main;
            $this->formRules       = is_array($strategy->rules)
                ? $strategy->rules
                : (json_decode($strategy->rules, true) ?? []);

            $this->resetErrorBag();
        } catch (\Throwable $e) {
            $this->logError($e, 'Load For Edit', 'PlaybookPage', "Failed to load strategy ID: {$strategyId}");
            $this->dispatch('show-alert', message: __('labels.error_loading_strategy'), type: 'error');
        }
    }

    /**
     * Limpia el formulario para la creación. Alpine lo llama al abrir el modal de creación.
     */
    public function resetStrategyForm(): void
    {
        $this->formName        = '';
        $this->formDescription = null;
        $this->formTimeframe   = '';
        $this->formColor       = '#4F46E5';
        $this->formIsmain      = false;
        $this->formRules       = [];
        $this->reset('photo');
        $this->resetErrorBag();
    }

    // ───────────────────────────────────────────────────────────────────────
    // COMPUTED
    // ───────────────────────────────────────────────────────────────────────

    #[Computed]
    public function strategies()
    {
        try {
            return Strategy::where('user_id', Auth::id())
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                ->select([
                    'id',
                    'name',
                    'description',
                    'timeframe',
                    'color',
                    'image_path',
                    'rules',
                    'is_main',
                    'stats_total_trades',
                    'stats_winning_trades',
                    'stats_total_pnl',
                    'stats_profit_factor',
                    'stats_max_drawdown_pct',
                    'stats_expectancy',
                    'stats_avg_rr',
                    'stats_by_day_of_week',
                    'stats_by_hour',
                    'stats_best_win_streak',
                    'stats_worst_loss_streak',
                    'stats_avg_mae_pct',
                    'stats_avg_mfe_pct',
                ])
                ->orderByDesc('is_main')
                ->when(
                    $this->sortBy === 'stats_winrate',
                    fn($q) => $q->orderByRaw('(stats_winning_trades / NULLIF(stats_total_trades, 0)) ' . $this->sortDir),
                    fn($q) => $q->orderBy($this->sortBy, $this->sortDir)
                )
                ->get()
                ->map(function ($strategy) {
                    $strategy->stats_winrate = $strategy->stats_total_trades > 0
                        ? round(($strategy->stats_winning_trades / $strategy->stats_total_trades) * 100, 1)
                        : 0;

                    $strategy->image_url = $strategy->image_path
                        ? Storage::url($strategy->image_path)
                        : null;

                    $strategy->chart_data = [
                        'days'  => is_string($strategy->stats_by_day_of_week)
                            ? json_decode($strategy->stats_by_day_of_week, true)
                            : ($strategy->stats_by_day_of_week ?? []),
                        'hours' => is_string($strategy->stats_by_hour)
                            ? json_decode($strategy->stats_by_hour, true)
                            : ($strategy->stats_by_hour ?? []),
                    ];

                    return $strategy;
                });
        } catch (\Throwable $e) {
            $this->logError($e, 'Read Strategies', 'PlaybookPage', 'Error loading computed strategies');
            return collect();
        }
    }

    public function render()
    {
        return view('livewire.playbook-page');
    }

    // ───────────────────────────────────────────────────────────────────────
    // CREAR ESTRATEGIA
    // ───────────────────────────────────────────────────────────────────────

    public function createStrategy(): void
    {
        // validate() lanza ValidationException si falla.
        // Livewire la captura automáticamente y puebla $errors en Blade.
        // El modal NO se cierra porque 'strategy-saved' no se emite.
        $this->validate(
            $this->strategyValidationRules(),
            $this->strategyValidationMessages()
        );

        try {
            DB::transaction(function () {
                if ($this->formIsmain) {
                    Strategy::where('user_id', Auth::id())->update(['is_main' => false]);
                }

                $strategyData = [
                    'user_id'     => Auth::id(),
                    'name'        => $this->formName,
                    'description' => $this->formDescription,
                    'timeframe'   => $this->formTimeframe,
                    'color'       => $this->formColor,
                    'rules'       => $this->formRules,
                    'is_main'     => $this->formIsmain,
                ];

                if ($this->photo) {
                    $strategyData['image_path'] = $this->photo->store('playbooks', 'public');
                }

                $strategy = Strategy::create($strategyData);

                $this->insertLog(
                    action: 'Create Strategy',
                    form: 'PlaybookPage',
                    description: "Created strategy ID: {$strategy->id} - {$strategy->name}",
                    type: 'success'
                );
            });

            $this->resetStrategyForm();
            // 'strategy-saved' es el único trigger para cerrar el modal en Alpine.
            // Si validate() falló, nunca llegamos aquí → modal sigue abierto.
            $this->dispatch('strategy-saved');
            $this->dispatch('show-alert', message: __('labels.strategy_created_ok'), type: 'success');
        } catch (\Throwable $e) {
            $this->logError($e, 'Create Strategy', 'PlaybookPage', 'Failed to create strategy');
            $this->dispatch('show-alert', message: __('labels.error_creating_strategy'), type: 'error');
        }
    }

    // ───────────────────────────────────────────────────────────────────────
    // ACTUALIZAR ESTRATEGIA
    // ───────────────────────────────────────────────────────────────────────

    public function updateStrategy(int $strategyId): void
    {
        $this->validate(
            $this->strategyValidationRules(),
            $this->strategyValidationMessages()
        );

        try {
            $strategy = Strategy::where('user_id', Auth::id())->findOrFail($strategyId);

            DB::transaction(function () use ($strategy) {
                if ($this->formIsmain) {
                    Strategy::where('user_id', Auth::id())
                        ->where('id', '!=', $strategy->id)
                        ->update(['is_main' => false]);
                }

                $strategyData = [
                    'name'        => $this->formName,
                    'description' => $this->formDescription,
                    'timeframe'   => $this->formTimeframe,
                    'color'       => $this->formColor,
                    'rules'       => $this->formRules,
                    'is_main'     => $this->formIsmain,
                ];

                if ($this->photo) {
                    if ($strategy->image_path) {
                        Storage::disk('public')->delete($strategy->image_path);
                    }
                    $strategyData['image_path'] = $this->photo->store('playbooks', 'public');
                }

                $strategy->update($strategyData);

                $this->insertLog(
                    action: 'Update Strategy',
                    form: 'PlaybookPage',
                    description: "Updated strategy ID: {$strategy->id}",
                    type: 'info'
                );
            });

            $this->resetStrategyForm();
            $this->dispatch('strategy-saved');
            $this->dispatch('show-alert', message: __('labels.strategy_update_ok'), type: 'success');
        } catch (\Throwable $e) {
            $this->logError($e, 'Update Strategy', 'PlaybookPage', "Failed to update strategy ID: {$strategyId}");
            $this->dispatch('show-alert', message: __('labels.error_updating_strategy'), type: 'error');
        }
    }

    // ───────────────────────────────────────────────────────────────────────
    // BORRAR ESTRATEGIA
    // ───────────────────────────────────────────────────────────────────────

    public function deleteStrategy(int $id): void
    {
        try {
            $strategy = Strategy::where('user_id', Auth::id())->findOrFail($id);
            $name = $strategy->name;

            if ($strategy->image_path) {
                Storage::disk('public')->delete($strategy->image_path);
            }

            $strategy->delete();

            $this->insertLog(
                action: 'Delete Strategy',
                form: 'PlaybookPage',
                description: "Deleted strategy ID: {$id} - Name: {$name}",
                type: 'warning'
            );

            $this->dispatch('show-alert', message: __('labels.strategy_deleted'), type: 'success');
        } catch (\Throwable $e) {
            $this->logError($e, 'Delete Strategy', 'PlaybookPage', "Failed to delete strategy ID: {$id}");
            $this->dispatch('show-alert', message: __('labels.error_deleting_strategy'), type: 'error');
        }
    }

    // ───────────────────────────────────────────────────────────────────────
    // DUPLICAR ESTRATEGIA
    // ───────────────────────────────────────────────────────────────────────

    public function duplicateStrategy(int $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $strategy    = Strategy::where('user_id', Auth::id())->findOrFail($id);
                $newStrategy = $strategy->replicate();

                $newStrategy->name    = $strategy->name . ' (Copia)';
                $newStrategy->is_main = false;

                // Reset stats numéricas a 0
                foreach (
                    [
                        'stats_total_trades',
                        'stats_winning_trades',
                        'stats_losing_trades',
                        'stats_total_pnl',
                        'stats_gross_profit',
                        'stats_gross_loss',
                        'stats_best_win_streak',
                        'stats_worst_loss_streak',
                    ] as $field
                ) {
                    $newStrategy->$field = 0;
                }

                // Reset stats calculadas a null
                foreach (
                    [
                        'stats_profit_factor',
                        'stats_avg_win',
                        'stats_avg_loss',
                        'stats_expectancy',
                        'stats_avg_rr',
                        'stats_max_drawdown_pct',
                        'stats_sharpe_ratio',
                        'stats_avg_mae_pct',
                        'stats_avg_mfe_pct',
                        'stats_by_day_of_week',
                        'stats_by_hour',
                        'stats_last_calculated_at',
                    ] as $field
                ) {
                    $newStrategy->$field = null;
                }

                $newStrategy->save();

                $this->insertLog(
                    action: 'Duplicate Strategy',
                    form: 'PlaybookPage',
                    description: "Duplicated ID: {$id} -> New ID: {$newStrategy->id}",
                    type: 'info'
                );
            });

            $this->dispatch('show-alert', message: __('labels.duplicate_strategy_ok'), type: 'success');
        } catch (\Throwable $e) {
            $this->logError($e, 'Duplicate Strategy', 'PlaybookPage', "Failed to duplicate strategy ID: {$id}");
            $this->dispatch('show-alert', message: __('labels.error_duplicate_strategy'), type: 'error');
        }
    }

    // ───────────────────────────────────────────────────────────────────────
    // CARGAR DETALLE DE ESTRATEGIA (para modal de análisis)
    // ───────────────────────────────────────────────────────────────────────

    public function loadStrategyDetails(int $strategyId): array
    {
        try {
            // Verificación de ownership antes de cualquier consulta
            $strategy = Strategy::where('user_id', Auth::id())->findOrFail($strategyId);

            return \App\Models\Trade::where('strategy_id', $strategy->id)
                ->latest('exit_time')
                ->take(100)
                ->get()
                ->map(fn($t) => [
                    'id'             => $t->id,
                    'ticket'         => $t->ticket,
                    'entry_time'     => $t->entry_time?->format('Y-m-d H:i') ?? '-',
                    'exit_time'      => $t->exit_time?->format('Y-m-d H:i') ?? '-',
                    'direction'      => ucfirst($t->direction),
                    'pnl'            => (float) $t->pnl,
                    'duration'       => $t->duration_minutes . ' min',
                    'screenshot_url' => $t->screenshot ? Storage::url($t->screenshot) : null,
                    'day_iso'        => $t->exit_time?->format('N'),
                    'hour'           => $t->exit_time?->format('H'),
                ])
                ->toArray();
        } catch (\Throwable $e) {
            $this->logError($e, 'Load Strategy Details', 'PlaybookPage', "Failed to load details for Strategy ID: {$strategyId}");
            return [];
        }
    }
}
