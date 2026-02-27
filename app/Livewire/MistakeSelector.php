<?php

namespace App\Livewire;

use App\Models\Mistake;
use App\Models\Trade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MistakeSelector extends Component
{
    public Trade $trade;
    public $availableMistakes = [];
    public $selectedMistakes = [];

    // CAMBIO: Ahora esto guardará arrays ['name' => 'FOMO', 'reason' => 'Motivo...']
    public $suggestions = [];

    public function mount(Trade $trade)
    {
        $this->trade = $trade;
        $this->loadData();
        $this->runFiscalAnalysis(); // Renombramos para que sea más épico
    }

    public function loadData()
    {
        $this->availableMistakes = Mistake::forUser(Auth::id())->get();
        $this->selectedMistakes = $this->trade->mistakes()->pluck('mistakes.id')->toArray();
    }

    public function runFiscalAnalysis()
    {
        $this->suggestions = []; // Reset

        // ---------------------------------------------------------
        // CASO 1: REVENGE TRADING (La Venganza)
        // ---------------------------------------------------------
        $prevTrade = Trade::whereHas('account', function ($q) {
            $q->where('user_id', Auth::id());
        })
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

                $this->suggestions[] = [
                    'name' => __('labels.revenge_trading'),
                    'reason' => __('labels.open_only', ['formattedDiff' => $formattedDiff])
                ];
            }
        }

        // ---------------------------------------------------------
        // CASO 2: OVERTRADING (Corregido: Lógica Secuencial)
        // Lógica: Es el trade número X del día.
        // Si el límite es 8, el trade nº 5 está limpio, pero el nº 9 es culpable.
        // ---------------------------------------------------------

        // Contamos cuántos trades hubo ese mismo día que cerraron ANTES o AL MISMO TIEMPO que este.
        // Esto nos da su "número de ticket" en la cola del día.
        $dailyOrder = Trade::whereHas('account', function ($q) {
            $q->where('user_id', Auth::id());
        })
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

        // Umbral: A partir de la operación número 9, empezamos a avisar.
        if ($dailyOrder > 4) {
            $this->suggestions[] = [
                'name' => __('labels.overtrading'),
                'reason' => __('labels.overtrading_explain', ['dailyOrder' => $dailyOrder])
            ];
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
                    $this->suggestions[] = [
                        'name' => __('labels.early_exit'),
                        'reason' => __('labels.early_exit_explain', ['pct' => $pct])
                    ];
                }
            }
        }

        // ---------------------------------------------------------
        // CASO 4: HOLDING LOSERS (La Esperanza)
        // Lógica: El precio fue MUCHO en contra (MAE), aguantaste, y cerraste en pérdida.
        // Asumimos que si MAE es 3 veces mayor que la pérdida final, hubo drawdown masivo.
        // ---------------------------------------------------------
        if ($this->trade->pnl < 0 && $this->trade->mae_price) {
            $lossDistance = abs($this->trade->exit_price - $this->trade->entry_price);
            $maeDistance = abs($this->trade->mae_price - $this->trade->entry_price);

            if ($lossDistance > 0 && ($maeDistance / $lossDistance) > 2.5) {
                $this->suggestions[] = [
                    'name' => __('labels.move_sl'),
                    'reason' => __('labels.move_sl_explain')
                ];
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
                $this->suggestions[] = [
                    'name' => __('labels.round_trip'),
                    'reason' => __('labels.round_trip_explain')
                ];
            }
        }
    }

    // ... (toggleMistake y render igual) ...
    public function toggleMistake($mistakeId)
    {
        if (in_array($mistakeId, $this->selectedMistakes)) {
            $this->trade->mistakes()->detach($mistakeId);
        } else {
            $this->trade->mistakes()->attach($mistakeId);
        }
        $this->loadData();
        $this->dispatch('trade-updated');
    }


    public function render()
    {
        return view('livewire.mistake-selector');
    }
}
