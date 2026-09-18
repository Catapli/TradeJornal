<?php

namespace App\Livewire;

use App\Actions\Retention\CalculateStreaks;
use App\LogActions;
use App\Models\Mistake;
use App\Models\Trade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MistakeSelector extends Component
{
    use LogActions;

    private const COMPONENT_FORM = 'MistakeSelector';

    public Trade $trade;

    public $availableMistakes = [];

    public $selectedMistakes = [];

    /** Cada sugerencia es ['slug' => ..., 'name' => ..., 'reason' => ...] */
    public $suggestions = [];

    // ── Formulario de errores personalizados ────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formName = '';

    public ?string $formDescription = null;

    public string $formColor = 'rose';

    public int $formWeight = 1;

    /**
     * Presentación compacta.
     *
     * En el detalle de la operación el selector es una sección más de una ficha
     * larga: va plegado, con su caja de sugerencias y su nota al pie. En el
     * repaso es lo único a lo que se ha entrado y **el alto de la pantalla es
     * oro**, así que va siempre abierto, sin plegable y con las sugerencias
     * como una línea de pastillas en vez de una caja.
     */
    public bool $compact = false;

    public function mount(Trade $trade, bool $compact = false)
    {
        $this->trade = $trade;
        $this->compact = $compact;
        $this->loadData();
        $this->runFiscalAnalysis(); // Renombramos para que sea más épico
    }

    public function loadData()
    {
        // Ordenamos por el nombre visible (traducido), no por la columna cruda.
        $this->availableMistakes = Mistake::forUser(Auth::id())
            ->get()
            ->sortBy(fn (Mistake $m) => mb_strtolower($m->display_name))
            ->values();

        $this->selectedMistakes = $this->trade->mistakes()->pluck('mistakes.id')->toArray();
    }

    public function runFiscalAnalysis()
    {
        $this->suggestions = []; // Reset

        // ---------------------------------------------------------
        // CASO 1: REVENGE TRADING (La Venganza)
        // ---------------------------------------------------------
        $prevTrade = Trade::forUser()
            ->where('exit_time', '<', $this->trade->entry_time)
            ->orderBy('exit_time', 'desc')
            ->first();

        if ($prevTrade) {
            // 1. Obtenemos el número entero (FLOAT/INT)
            $minutesDiff = Carbon::parse($prevTrade->exit_time)->diffInMinutes($this->trade->entry_time);

            // 2. Comparamos con el número PURO (Aquí 1406 no será menor que 15)
            if ($minutesDiff < 15 && $prevTrade->pnl < 0) {

                // 3. Formateamos solo para el mensaje visual
                $formattedDiff = number_format($minutesDiff);

                $this->addSuggestion(
                    'revenge_trading',
                    __('labels.open_only', ['formattedDiff' => $formattedDiff])
                );
            }
        }

        // ---------------------------------------------------------
        // CASO 2: OVERTRADING (Corregido: Lógica Secuencial)
        // Lógica: Es el trade número X del día.
        // Si el límite es 8, el trade nº 5 está limpio, pero el nº 9 es culpable.
        // ---------------------------------------------------------

        // Contamos cuántos trades hubo ese mismo día que cerraron ANTES o AL MISMO TIEMPO que este.
        // Esto nos da su "número de ticket" en la cola del día.
        $dailyOrder = Trade::forUser()
            ->whereDate('exit_time', $this->trade->exit_time)
            ->where(function ($query) {
                // Condición A: Hora de salida anterior
                $query->where('exit_time', '<', $this->trade->exit_time)
                    // Condición B: Misma hora exacta, pero ID menor (para desempatar si cierras 2 a la vez)
                    ->orWhere(function ($q) {
                        $q->where('exit_time', $this->trade->exit_time)
                            ->where('id', '<=', $this->trade->id);
                    });
            })
            ->count();

        // Umbral: A partir de la operación número 5, empezamos a avisar.
        if ($dailyOrder > 4) {
            $this->addSuggestion(
                'overtrading',
                __('labels.overtrading_explain', ['dailyOrder' => $dailyOrder])
            );
        }

        // ---------------------------------------------------------
        // CASO 3: SALIDA PREMATURA (Paper Hands / Miedo)
        // Lógica: Ganaste dinero, pero el precio llegó muchísimo más lejos (MFE).
        // Capturaste menos del 30% del movimiento disponible.
        // ---------------------------------------------------------
        if ($this->trade->pnl > 0 && $this->trade->mfe_price && $this->trade->entry_price > 0) {

            // Calculamos distancia absoluta en precio
            $captured = abs($this->trade->exit_price - $this->trade->entry_price);
            $potential = abs($this->trade->mfe_price - $this->trade->entry_price);

            // Evitar división por cero
            if ($potential > 0) {
                $efficiency = $captured / $potential; // 0.10 = 10% capturado

                if ($efficiency < 0.30) {
                    $pct = round($efficiency * 100);
                    $this->addSuggestion(
                        'early_exit',
                        __('labels.early_exit_explain', ['pct' => $pct])
                    );
                }
            }
        }

        // ---------------------------------------------------------
        // CASO 4: HOLDING LOSERS (La Esperanza)
        // Lógica: El precio fue MUCHO en contra (MAE), aguantaste, y cerraste en pérdida.
        // Asumimos que si MAE es 2.5 veces mayor que la pérdida final, hubo drawdown masivo.
        // ---------------------------------------------------------
        if ($this->trade->pnl < 0 && $this->trade->mae_price) {
            $lossDistance = abs($this->trade->exit_price - $this->trade->entry_price);
            $maeDistance = abs($this->trade->mae_price - $this->trade->entry_price);

            if ($lossDistance > 0 && ($maeDistance / $lossDistance) > 2.5) {
                $this->addSuggestion('held_loser', __('labels.held_loser_explain'));
            }
        }

        // ---------------------------------------------------------
        // CASO 5: ROUND TRIP (Ganador a Perdedor)
        // Lógica: El trade terminó en pérdida (PnL < 0), pero en algún momento
        // estuvo ganando MÁS de lo que acabó perdiendo.
        // ---------------------------------------------------------
        if ($this->trade->pnl < 0 && $this->trade->mfe_price && $this->trade->entry_price > 0) {

            // Distancia máxima que estuvo a favor (Lo que pudiste ganar)
            $maxProfitDist = abs($this->trade->mfe_price - $this->trade->entry_price);

            // Distancia final de pérdida (Lo que perdiste)
            $finalLossDist = abs($this->trade->exit_price - $this->trade->entry_price);

            // Umbral: Si llegaste a ir ganando más de lo que perdiste (Ratio 1:1 implícito)
            // Ejemplo: Ibas +200€ y cerraste -150€. Claramente debiste proteger.
            if ($maxProfitDist > $finalLossDist) {
                $this->addSuggestion('round_trip', __('labels.round_trip_explain'));
            }
        }
    }

    /** Las sugerencias se referencian por slug para no depender del idioma activo. */
    private function addSuggestion(string $slug, string $reason): void
    {
        $this->suggestions[] = [
            'slug' => $slug,
            'name' => __("mistakes.{$slug}.name"),
            'reason' => $reason,
        ];
    }

    public function toggleMistake($mistakeId)
    {
        // Sólo puede marcar errores globales o suyos.
        if (!Mistake::forUser(Auth::id())->whereKey($mistakeId)->exists()) {
            return;
        }

        if (in_array($mistakeId, $this->selectedMistakes)) {
            $this->trade->mistakes()->detach($mistakeId);
        } else {
            $this->trade->mistakes()->attach($mistakeId);
        }

        // Marcar o desmarcar un error grave cambia la racha de días limpios.
        CalculateStreaks::forget((int) Auth::id());

        $this->loadData();
        $this->dispatch('trade-updated');
    }

    // ───────────────────────────────────────────────────────────────────────
    // CRUD DE ERRORES PERSONALIZADOS
    // ───────────────────────────────────────────────────────────────────────

    protected function mistakeValidationRules(): array
    {
        return [
            'formName' => [
                'required',
                'string',
                'max:60',
                Rule::unique('mistakes', 'name')
                    ->where(fn ($q) => $q->where('user_id', Auth::id()))
                    ->ignore($this->editingId),
            ],
            'formDescription' => 'nullable|string|max:500',
            'formColor' => ['required', Rule::in(array_keys(Mistake::COLORS))],
            'formWeight' => 'required|integer|min:1|max:3',
        ];
    }

    protected function mistakeValidationMessages(): array
    {
        return [
            'formName.required' => __('labels.mistake_name_required'),
            'formName.max' => __('labels.mistake_name_required'),
            'formName.unique' => __('labels.mistake_name_duplicated'),
        ];
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $mistakeId): void
    {
        $mistake = Mistake::custom(Auth::id())->find($mistakeId);

        if (!$mistake) {
            return; // Los errores del catálogo global no se editan.
        }

        $this->resetValidation();
        $this->editingId = $mistake->id;
        $this->formName = $mistake->name;
        $this->formDescription = $mistake->description;
        $this->formColor = array_key_exists((string) $mistake->color, Mistake::COLORS) ? $mistake->color : 'rose';
        $this->formWeight = $mistake->weight ?: 1;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function saveMistake(): void
    {
        $this->validate($this->mistakeValidationRules(), $this->mistakeValidationMessages());

        try {
            $data = [
                'name' => $this->formName,
                'description' => $this->formDescription,
                'color' => $this->formColor,
                'weight' => $this->formWeight,
            ];

            if ($this->editingId) {
                Mistake::custom(Auth::id())->findOrFail($this->editingId)->update($data);
            } else {
                Mistake::create($data + ['user_id' => Auth::id(), 'slug' => null]);
            }

            $this->cancelForm();
            $this->loadData();
            $this->dispatch('show-alert', message: __('labels.mistake_saved'), type: 'success');
        } catch (\Throwable $e) {
            $this->logError($e, 'Save Mistake', self::COMPONENT_FORM, "Mistake ID: {$this->editingId}");
            $this->dispatch('show-alert', message: __('labels.mistake_save_error'), type: 'error');
        }
    }

    public function deleteMistake(int $mistakeId): void
    {
        try {
            // El pivote trade_mistake cae en cascada: se desmarca de todos los trades.
            Mistake::custom(Auth::id())->findOrFail($mistakeId)->delete();

            $this->cancelForm();
            $this->loadData();
            $this->dispatch('trade-updated');
            $this->dispatch('show-alert', message: __('labels.mistake_deleted'), type: 'success');
        } catch (\Throwable $e) {
            $this->logError($e, 'Delete Mistake', self::COMPONENT_FORM, "Mistake ID: {$mistakeId}");
            $this->dispatch('show-alert', message: __('labels.mistake_delete_error'), type: 'error');
        }
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->formName = '';
        $this->formDescription = null;
        $this->formColor = 'rose';
        $this->formWeight = 1;
    }

    public function render()
    {
        return view('livewire.mistake-selector', [
            'palette' => Mistake::COLORS,
        ]);
    }
}
