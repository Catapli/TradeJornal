<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Models\ImprovementGoal;
use App\Models\Trade;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Seguimiento del objetivo del mes (Fase 6 · P5).
 *
 * Hace dos cosas, y la primera importa más que la segunda:
 *
 * 1. **Cierra los objetivos cuyo mes ya pasó**, con el número real delante. Un
 *    objetivo que nadie cierra es un propósito de año nuevo; lo que convierte
 *    esto en seguimiento es que el mes acabe con un sí o un no escrito.
 * 2. Mide el objetivo vivo contra lo que va del mes.
 *
 * El cierre no se deshace. Reabrir un objetivo para que salga bien es la manera
 * más rápida de que dejen de significar nada.
 */
class TrackMonthlyGoal
{
    /**
     * Cierra todo objetivo vencido del usuario y devuelve cuántos ha cerrado.
     */
    public function closeFinished(int $userId, ?CarbonImmutable $today = null): int
    {
        $hoy = $today ?? CarbonImmutable::today();
        $mesActual = $hoy->startOfMonth();

        $vencidos = ImprovementGoal::where('user_id', $userId)
            ->where('status', ImprovementGoal::STATUS_ACTIVE)
            ->whereDate('month', '<', $mesActual)
            ->get();

        foreach ($vencidos as $objetivo) {
            $veces = $this->countInMonth($userId, $objetivo);

            $objetivo->update([
                'result' => $veces,
                'status' => $veces <= $objetivo->target
                    ? ImprovementGoal::STATUS_ACHIEVED
                    : ImprovementGoal::STATUS_MISSED,
                'closed_at' => $hoy,
            ]);
        }

        return $vencidos->count();
    }

    /**
     * Cómo va el objetivo vivo.
     *
     * @return array<string, mixed>
     */
    public function progress(ImprovementGoal $goal, ?CarbonImmutable $today = null): array
    {
        $hoy = $today ?? CarbonImmutable::today();
        $veces = $goal->isOpen()
            ? $this->countInMonth((int) $goal->user_id, $goal)
            : (int) $goal->result;

        $fin = $goal->endsAt();
        $quedan = $goal->isOpen() ? max(0, (int) $hoy->diffInDays($fin)) : 0;

        return [
            'so_far' => $veces,
            'target' => $goal->target,
            'baseline' => $goal->baseline,
            'left' => max(0, $goal->target - $veces),
            // Se pasa del objetivo: se dice ya, sin esperar a fin de mes. Enterarse
            // el día 30 de que se rompió el día 4 no sirve para corregir nada.
            'blown' => $veces > $goal->target,
            'days_left' => $quedan,
            // Sobre el margen que había: de `baseline` a `target`.
            'progress' => $goal->baseline > 0
                ? min(100, (int) round($veces / max(1, $goal->baseline) * 100))
                : 0,
        ];
    }

    /** Veces que se cometió el error del objetivo dentro de su mes. */
    private function countInMonth(int $userId, ImprovementGoal $goal): int
    {
        if (!$goal->mistake_id) {
            return 0;
        }

        $mes = CarbonImmutable::parse($goal->month);

        return $this->monthTrades($userId, $mes)
            ->filter(fn (Trade $t): bool => $t->mistakes->contains('id', $goal->mistake_id))
            ->count();
    }

    /** @return Collection<int, Trade> */
    private function monthTrades(int $userId, CarbonImmutable $month): Collection
    {
        return Trade::forUserActiveAccounts($userId)
            ->whereBetween('exit_time', [$month->startOfMonth(), $month->endOfMonth()])
            ->with('mistakes')
            ->get();
    }
}
