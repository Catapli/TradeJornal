<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Mistakes\CalculateMistakeCost;
use App\Models\Trade;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * El perfil acumulado del trader (Fase 6 · P5).
 *
 * Hasta aquí la aplicación producía **auditorías sueltas**: cada operación
 * recibía su veredicto y ninguna recordaba la anterior. Eso no es un mentor, es
 * un corrector. Lo que convierte una cosa en la otra es la memoria: qué error se
 * repite, cuánto se repite y si este mes va a mejor o a peor.
 *
 * Dos cautelas heredadas de lo que costó el sistema R:
 *
 * 1. **Muestra mínima declarada.** Por debajo de MIN_MARKS marcas de error no
 *    hay perfil. Con cuatro marcas, «tu error recurrente» es una casualidad con
 *    nombre propio, y aquí se convierte en el objetivo del mes de alguien.
 * 2. **La cobertura viaja siempre.** Un perfil sacado de treinta operaciones
 *    repasadas de trescientas no miente, pero sin ese dato al lado lo parece.
 */
class BuildTraderProfile
{
    /** Meses de historial que entran en el perfil. */
    public const MONTHS = 6;

    /** Marcas de error mínimas para que el perfil diga algo. */
    public const MIN_MARKS = 12;

    /**
     * @return array<string, mixed>
     */
    public function execute(int $userId, ?CarbonImmutable $today = null): array
    {
        $hoy = $today ?? CarbonImmutable::today();
        $desde = $hoy->startOfMonth()->subMonths(self::MONTHS - 1);
        $mesActual = $hoy->startOfMonth();

        $trades = Trade::forUserActiveAccounts($userId)
            ->where('exit_time', '>=', $desde)
            ->with('mistakes')
            ->get();

        $coste = app(CalculateMistakeCost::class)->fromTrades($trades);
        $marcas = $trades->sum(fn (Trade $t): int => $t->mistakes->count());

        $perfil = [
            'from' => $desde->toDateString(),
            'months' => self::MONTHS,
            'trades' => $trades->count(),
            'reviewed' => $coste['reviewed'],
            'coverage' => $coste['coverage'],
            'marks' => $marcas,
            'min_marks' => self::MIN_MARKS,
            'has_enough_data' => $marcas >= self::MIN_MARKS,
            'mistakes' => [],
            'top' => null,
        ];

        if (!$perfil['has_enough_data']) {
            return $perfil + ['missing_marks' => self::MIN_MARKS - $marcas];
        }

        $esteMes = $trades->filter(
            fn (Trade $t): bool => CarbonImmutable::parse($t->exit_time)->startOfMonth()->equalTo($mesActual)
        );
        $anteriores = $trades->reject(
            fn (Trade $t): bool => CarbonImmutable::parse($t->exit_time)->startOfMonth()->equalTo($mesActual)
        );

        $errores = collect($coste['by_mistake'])
            ->map(function (array $error) use ($esteMes, $anteriores): array {
                $error['this_month'] = $this->countMistake($esteMes, $error['id']);
                $error['before'] = $this->countMistake($anteriores, $error['id']);

                // Se compara la frecuencia por cada cien operaciones, no el número
                // suelto: un mes con la mitad de operaciones tiene menos marcas sin
                // que nadie haya mejorado nada.
                $error['rate_now'] = $this->ratePerHundred($error['this_month'], $esteMes->count());
                $error['rate_before'] = $this->ratePerHundred($error['before'], $anteriores->count());
                $error['trend'] = $this->trend($error['rate_now'], $error['rate_before']);

                return $error;
            })
            ->values()
            ->all();

        $perfil['mistakes'] = $errores;
        $perfil['top'] = $this->pickRecurring($errores);
        $perfil['this_month_trades'] = $esteMes->count();
        $perfil['month'] = $mesActual->toDateString();

        return $perfil;
    }

    /**
     * El error sobre el que trabajar.
     *
     * No es el más caro sino el **más recurrente entre los caros**: un error de
     * una sola vez que costó 800 € no se corrige con un hábito, y el objetivo del
     * mes es precisamente un hábito.
     *
     * @param  array<int, array<string, mixed>>  $errores
     * @return array<string, mixed>|null
     */
    private function pickRecurring(array $errores): ?array
    {
        $candidatos = array_filter($errores, fn (array $e): bool => $e['count'] >= 3 && $e['cost'] > 0);

        if ($candidatos === []) {
            return null;
        }

        usort($candidatos, fn (array $a, array $b): int => $b['count'] <=> $a['count']
            ?: $b['cost'] <=> $a['cost']);

        return $candidatos[0];
    }

    /** @param  Collection<int, Trade>  $trades */
    private function countMistake(Collection $trades, int $mistakeId): int
    {
        return $trades->filter(
            fn (Trade $t): bool => $t->mistakes->contains('id', $mistakeId)
        )->count();
    }

    private function ratePerHundred(int $veces, int $operaciones): float
    {
        return $operaciones === 0 ? 0.0 : round($veces / $operaciones * 100, 1);
    }

    /**
     * Hacia dónde va el error.
     *
     * El margen de dos puntos evita cantar «vas mejor» por una diferencia que en
     * la práctica es ruido de dos operaciones.
     */
    private function trend(float $ahora, float $antes): string
    {
        if ($antes === 0.0 && $ahora === 0.0) {
            return 'flat';
        }

        $diferencia = $ahora - $antes;

        return match (true) {
            $diferencia <= -2.0 => 'down',
            $diferencia >= 2.0 => 'up',
            default => 'flat',
        };
    }
}
