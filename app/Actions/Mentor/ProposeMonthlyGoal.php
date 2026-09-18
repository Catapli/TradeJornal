<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Models\Trade;
use Carbon\CarbonImmutable;

/**
 * El objetivo de mejora del mes, propuesto a partir del perfil (Fase 6 · P5).
 *
 * **Una sola cosa al mes.** Es la regla entera. Un mentor que pide cinco cambios
 * a la vez no consigue ninguno, y la aplicación ya tiene sitios de sobra donde
 * enseñar listas: el Laboratorio, los hallazgos, el semáforo de la cuenta. Aquí
 * sale una frase, con la cifra de la que se viene y a la que se apunta.
 *
 * La referencia es **el mes anterior completo**, no la media del semestre: es el
 * número que el usuario reconoce, el que acaba de vivir.
 */
class ProposeMonthlyGoal
{
    /** Operaciones mínimas del mes de referencia para que la cifra signifique algo. */
    public const MIN_REFERENCE_TRADES = 10;

    /**
     * @param  array<string, mixed>  $profile  salida de BuildTraderProfile
     * @return array<string, mixed>|null null si no hay nada honesto que proponer
     */
    public function execute(int $userId, array $profile, ?CarbonImmutable $today = null): ?array
    {
        $error = $profile['top'] ?? null;

        if (!($profile['has_enough_data'] ?? false) || !$error) {
            return null;
        }

        $hoy = $today ?? CarbonImmutable::today();
        $mes = $hoy->startOfMonth();
        $anterior = $mes->subMonth();

        $referencia = $this->monthTrades($userId, $anterior);
        $veces = $referencia->filter(
            fn (Trade $t): bool => $t->mistakes->contains('id', $error['id'])
        )->count();

        // Un mes de referencia con cuatro operaciones no da una cifra de la que
        // bajar. Se dice que falta rodaje en vez de inventarse un objetivo.
        if ($referencia->count() < self::MIN_REFERENCE_TRADES || $veces === 0) {
            return null;
        }

        return [
            'mistake_id' => (int) $error['id'],
            'mistake_name' => (string) $error['name'],
            'month' => $mes->toDateString(),
            'reference_month' => $anterior->toDateString(),
            'baseline' => $veces,
            'target' => $this->target($veces),
            'sample' => $referencia->count(),
        ];
    }

    /**
     * A cuántas veces se apunta.
     *
     * La mitad, redondeando hacia abajo. Pedir cero de golpe a quien viene de
     * nueve es pedirle que falle en la primera semana y abandone el objetivo;
     * a partir de dos veces, sí se pide cero.
     */
    private function target(int $baseline): int
    {
        return $baseline <= 2 ? 0 : (int) floor($baseline / 2);
    }

    /**
     * Operaciones cerradas dentro de un mes, con sus errores.
     *
     * @return \Illuminate\Support\Collection<int, Trade>
     */
    private function monthTrades(int $userId, CarbonImmutable $month): \Illuminate\Support\Collection
    {
        return Trade::forUserActiveAccounts($userId)
            ->whereBetween('exit_time', [$month->startOfMonth(), $month->endOfMonth()])
            ->with('mistakes')
            ->get();
    }
}
