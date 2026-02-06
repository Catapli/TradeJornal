<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\Strategy;
use App\LogActions; // ✅ Trait importado
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // Para transacciones si fuera necesario

class PlaybookPage extends Component
{
    use WithFileUploads;
    use LogActions; // ✅ Uso del Trait

    // ✅ SOLO el archivo (Alpine no puede manejar files)
    public $photo;

    // ✅ Filtros
    public $search = '';
    public $sortBy = 'stats_total_pnl'; // 'stats_total_pnl', 'stats_winrate', 'created_at'
    public $sortDir = 'desc';

    // ✅ Computed para cargar estrategias (solo lectura BD)
    #[Computed]
    public function strategies()
    {
        // En métodos de lectura (Computed) generalmente no ponemos try-catch 
        // porque si fallan, la vista entera suele romperse y Livewire lo maneja.
        // Pero si quisieras blindarlo, deberías retornar una colección vacía en caso de error.
        try {
            return Strategy::where('user_id', Auth::id())
                // 🔎 Filtro de Búsqueda
                ->when($this->search, function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->select([
                    'id',
                    'name',
                    'description',
                    'timeframe',
                    'color',
                    'image_path',
                    'rules',
                    'is_main',
                    // ✅ Stats ya calculadas
                    'stats_total_trades',
                    'stats_winning_trades',
                    'stats_total_pnl',
                    'stats_profit_factor',
                    'stats_max_drawdown_pct',
                    'stats_expectancy',
                    'stats_avg_rr',
                    'stats_by_day_of_week', // JSON
                    'stats_by_hour',        // JSON
                    'stats_best_win_streak',
                    'stats_worst_loss_streak',
                    'stats_avg_mae_pct',
                    'stats_avg_mfe_pct'
                ])
                ->orderByDesc('is_main')
                ->when($this->sortBy === 'stats_winrate', function ($q) {
                    $q->orderByRaw('(stats_winning_trades / NULLIF(stats_total_trades, 0)) ' . $this->sortDir);
                }, function ($q) {
                    $q->orderBy($this->sortBy, $this->sortDir);
                })
                ->get()
                ->map(function ($strategy) {
                    $strategy->stats_winrate = $strategy->stats_total_trades > 0
                        ? round(($strategy->stats_winning_trades / $strategy->stats_total_trades) * 100, 1)
                        : 0;

                    $strategy->image_url = $strategy->image_path
                        ? Storage::url($strategy->image_path)
                        : null;

                    // ✅ Decodificar JSONs para pasarlos como objetos a Alpine
                    $strategy->chart_data = [
                        'days' => is_string($strategy->stats_by_day_of_week) ? json_decode($strategy->stats_by_day_of_week, true) : ($strategy->stats_by_day_of_week ?? []),
                        'hours' => is_string($strategy->stats_by_hour) ? json_decode($strategy->stats_by_hour, true) : ($strategy->stats_by_hour ?? []),
                    ];

                    return $strategy;
                });
        } catch (\Exception $e) {
            // Log silencioso para no romper la UI, pero avisar al dev
            $this->logError($e, 'Read Strategies', 'PlaybookPage', 'Error loading computed strategies');
            return collect(); // Retornar colección vacía para que el frontend no pete
        }
    }

    public function render()
    {
        return view('livewire.playbook-page');
    }

    // ============================================
    // ✅ MÉTODOS DE ACCIÓN (Blindados con Logs)
    // ============================================

    public function createStrategy($data)
    {
        try {
            $this->validate([
                'photo' => 'nullable|image|max:2048',
            ]);

            // Validación manual del payload de Alpine
            $this->validateStrategyData($data);

            // Transacción DB para asegurar integridad si algo falla a mitad
            DB::transaction(function () use ($data) {
                // Si marca como principal, desmarcar las demás
                if ($data['is_main']) {
                    Strategy::where('user_id', Auth::id())->update(['is_main' => false]);
                }

                $strategyData = [
                    'user_id' => Auth::id(),
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'timeframe' => $data['timeframe'],
                    'color' => $data['color'],
                    'rules' => $data['rules'] ?? [],
                    'is_main' => $data['is_main'],
                ];

                if ($this->photo) {
                    $strategyData['image_path'] = $this->photo->store('playbooks', 'public');
                }

                $strategy = Strategy::create($strategyData);

                // ✅ Log de Éxito (Auditoría)
                $this->insertLog(
                    action: 'Create Strategy',
                    form: 'PlaybookPage',
                    description: "Created strategy ID: {$strategy->id} - {$strategy->name}",
                    type: 'success'
                );
            });

            $this->reset('photo');
            $this->dispatch('show-alert', message: 'Playbook creado correctamente.', type: 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación no son bugs del sistema, no los logueamos como 'error' critico
            // Opcional: Log info
            $this->dispatch('show-alert', message: $e->getMessage(), type: 'error');
            throw $e; // Re-lanzar para que Livewire muestre errores en inputs si usas wire:model
        } catch (\Throwable  $e) {
            // ❌ Log de Error Crítico
            $this->logError(
                exception: $e,
                action: 'Create Strategy',
                form: 'PlaybookPage',
                description: 'Failed to create strategy payload: ' . json_encode($data)
            );

            $this->dispatch('show-alert', message: 'Error al crear la estrategia. El equipo técnico ha sido notificado.', type: 'error');
        }
    }

    public function updateStrategy($data)
    {
        try {
            $strategy = Strategy::where('user_id', Auth::id())->findOrFail($data['strategy_id']);

            $this->validate([
                'photo' => 'nullable|image|max:2048',
            ]);

            $this->validateStrategyData($data);

            DB::transaction(function () use ($data, $strategy) {
                if ($data['is_main']) {
                    Strategy::where('user_id', Auth::id())
                        ->where('id', '!=', $strategy->id)
                        ->update(['is_main' => false]);
                }

                $strategyData = [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'timeframe' => $data['timeframe'],
                    'color' => $data['color'],
                    'rules' => $data['rules'] ?? [],
                    'is_main' => $data['is_main'],
                ];

                if ($this->photo) {
                    // Borrar foto anterior
                    if ($strategy->image_path) {
                        Storage::disk('public')->delete($strategy->image_path);
                    }
                    $strategyData['image_path'] = $this->photo->store('playbooks', 'public');
                }

                $strategy->update($strategyData);

                // ✅ Log de Éxito
                $this->insertLog(
                    action: 'Update Strategy',
                    form: 'PlaybookPage',
                    description: "Updated strategy ID: {$strategy->id}",
                    type: 'info'
                );
            });

            $this->reset('photo');
            $this->dispatch('show-alert', message: 'Playbook actualizado correctamente.', type: 'success');
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Update Strategy',
                form: 'PlaybookPage',
                description: 'Failed to update strategy ID: ' . ($data['strategy_id'] ?? 'unknown')
            );
            $this->dispatch('show-alert', message: 'Error al actualizar. Inténtalo de nuevo.', type: 'error');
        }
    }

    public function deleteStrategy($id)
    {
        try {
            $strategy = Strategy::where('user_id', Auth::id())->findOrFail($id);
            $name = $strategy->name; // Guardar nombre para log

            if ($strategy->image_path) {
                Storage::disk('public')->delete($strategy->image_path);
            }

            $strategy->delete();

            // ✅ Log de Acción
            $this->insertLog(
                action: 'Delete Strategy',
                form: 'PlaybookPage',
                description: "Deleted strategy ID: {$id} - Name: {$name}",
                type: 'warning'
            );

            $this->dispatch('show-alert', message: 'Playbook eliminado.', type: 'success');
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Delete Strategy',
                form: 'PlaybookPage',
                description: "Failed to delete strategy ID: {$id}"
            );
            $this->dispatch('show-alert', message: 'No se pudo eliminar la estrategia.', type: 'error');
        }
    }

    public function duplicateStrategy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $strategy = Strategy::where('user_id', Auth::id())->findOrFail($id);

                $newStrategy = $strategy->replicate();
                $newStrategy->name = $strategy->name . ' (Copia)';
                $newStrategy->is_main = false;

                // Reset de Stats
                $newStrategy->stats_total_trades = 0;
                $newStrategy->stats_winning_trades = 0;
                $newStrategy->stats_losing_trades = 0;
                $newStrategy->stats_total_pnl = 0;
                $newStrategy->stats_gross_profit = 0;
                $newStrategy->stats_gross_loss = 0;
                $newStrategy->stats_profit_factor = null;
                $newStrategy->stats_avg_win = null;
                $newStrategy->stats_avg_loss = null;
                $newStrategy->stats_expectancy = null;
                $newStrategy->stats_avg_rr = null;
                $newStrategy->stats_max_drawdown_pct = null;
                $newStrategy->stats_sharpe_ratio = null;
                $newStrategy->stats_avg_mae_pct = null;
                $newStrategy->stats_avg_mfe_pct = null;
                $newStrategy->stats_by_day_of_week = null;
                $newStrategy->stats_by_hour = null;
                $newStrategy->stats_best_win_streak = 0;
                $newStrategy->stats_worst_loss_streak = 0;
                $newStrategy->stats_last_calculated_at = null;

                $newStrategy->save();

                $this->insertLog(
                    action: 'Duplicate Strategy',
                    form: 'PlaybookPage',
                    description: "Duplicated ID: {$id} -> New ID: {$newStrategy->id}",
                    type: 'info'
                );
            });

            $this->dispatch('show-alert', message: 'Estrategia duplicada.', type: 'success');
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Duplicate Strategy',
                form: 'PlaybookPage',
                description: "Failed to duplicate strategy ID: {$id}"
            );
            $this->dispatch('show-alert', message: 'Error al duplicar la estrategia.', type: 'error');
        }
    }

    public function loadStrategyDetails($strategyId)
    {
        try {
            $trades = \App\Models\Trade::where('strategy_id', $strategyId)
                ->latest('exit_time')
                ->take(100)
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'ticket' => $t->ticket,
                        'entry_time' => $t->entry_time ? $t->entry_time->format('Y-m-d H:i') : '-',
                        'exit_time' => $t->exit_time ? $t->exit_time->format('Y-m-d H:i') : '-',
                        'direction' => ucfirst($t->direction),
                        'pnl' => (float) $t->pnl,
                        'duration' => $t->duration_minutes . ' min',
                        'screenshot_url' => $t->screenshot ? Storage::url($t->screenshot) : null,
                        'day_iso' => $t->exit_time ? $t->exit_time->format('N') : null,
                        'hour' => $t->exit_time ? $t->exit_time->format('H') : null,
                    ];
                });

            return $trades;
        } catch (\Exception $e) {
            $this->logError(
                exception: $e,
                action: 'Load Strategy Details',
                form: 'PlaybookPage',
                description: "Failed to load details for Strategy ID: {$strategyId}"
            );

            // Retornamos array vacío para que el frontend no rompa al iterar
            return [];
        }
    }

    // ============================================
    // Helper de validación
    // ============================================
    private function validateStrategyData($data)
    {
        // No necesitamos try-catch aquí porque la excepción se captura 
        // en el método padre (create/update) que llama a este helper.
        if (empty($data['name']) || strlen($data['name']) > 255) {
            throw new \Exception('El nombre es requerido y no puede superar 255 caracteres.');
        }

        if (empty($data['timeframe']) || strlen($data['timeframe']) > 10) {
            throw new \Exception('El timeframe es requerido.');
        }

        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $data['color'])) {
            throw new \Exception('El color debe ser un código hexadecimal válido.');
        }
    }
}
