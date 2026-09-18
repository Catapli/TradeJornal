<?php

declare(strict_types=1);

namespace App\Actions\Rules;

use App\Actions\Mistakes\CalculateMistakeCost;
use App\Models\TradingRule;
use Illuminate\Support\Collection;

/**
 * Convierte los datos del Laboratorio en frases que se pueden cumplir (Fase 5 · P7).
 *
 * El Laboratorio enseña gráficos; un gráfico no es una decisión. Aquí sale lo
 * contrario: «a partir de la cuarta operación pierdes 1.240 €», con la cifra, la
 * muestra y una regla lista para adoptar.
 *
 * Dos cosas que no se negocian:
 *
 * 1. **Muestra mínima.** Nada se enseña por debajo de MIN_SAMPLE operaciones.
 *    Una conclusión sacada de tres días es una casualidad con formato de consejo,
 *    y aquí se convierte en una regla que el usuario va a obedecer.
 * 2. **Cada hallazgo declara su muestra.** Va con la frase, no en una nota al pie,
 *    y viaja hasta la regla creada para que dentro de un mes se sepa de dónde salió.
 */
class DetectFindings
{
    /** Operaciones mínimas del grupo para que el hallazgo se muestre. */
    public const MIN_SAMPLE = 12;

    /** Pérdida mínima (en dinero) para que merezca la pena una regla. */
    private const MIN_COST = 100.0;

    /**
     * @param  Collection<int, \App\Models\Trade>  $trades
     * @return array<int, array<string, mixed>>
     */
    public function execute(Collection $trades): array
    {
        if ($trades->count() < self::MIN_SAMPLE) {
            return [];
        }

        $findings = array_merge(
            $this->fromMistakes($trades),
            $this->fromTimeOfDay($trades),
            $this->fromTradesPerDay($trades),
            $this->fromWeekday($trades),
        );

        // Lo más caro primero: es lo que hay que arreglar antes.
        usort($findings, fn (array $a, array $b): int => $b['cost'] <=> $a['cost']);

        return $findings;
    }

    /**
     * El error que más dinero se lleva.
     *
     * @param  Collection<int, \App\Models\Trade>  $trades
     * @return array<int, array<string, mixed>>
     */
    private function fromMistakes(Collection $trades): array
    {
        $cost = app(CalculateMistakeCost::class)->fromTrades($trades);
        $top = $cost['by_mistake'][0] ?? null;

        if (!$top || $top['cost'] < self::MIN_COST || $top['count'] < 3) {
            return [];
        }

        // Aquí la muestra son las operaciones marcadas con ese error, no todas:
        // exigir MIN_SAMPLE por error dejaría fuera casi todo etiquetado real.
        return [[
            'key' => 'mistake_cost',
            'kind' => TradingRule::KIND_MISTAKE,
            'cost' => $top['cost'],
            'sample' => $top['count'],
            'params' => ['name' => $top['name'], 'amount' => $top['cost'], 'count' => $top['count']],
            'config' => ['mistake_id' => $top['id']],
        ]];
    }

    /**
     * La franja horaria que da dinero en contra.
     *
     * @param  Collection<int, \App\Models\Trade>  $trades
     * @return array<int, array<string, mixed>>
     */
    private function fromTimeOfDay(Collection $trades): array
    {
        $slots = [
            'early' => [0, 7],
            'morning' => [8, 12],
            'afternoon' => [13, 17],
            'evening' => [18, 23],
        ];

        $stats = [];

        foreach ($trades as $trade) {
            if ($trade->entry_time === null) {
                continue;
            }

            $hour = (int) $trade->entry_time->format('G');

            foreach ($slots as $slot => [$from, $to]) {
                if ($hour >= $from && $hour <= $to) {
                    $stats[$slot] ??= ['count' => 0, 'pnl' => 0.0];
                    $stats[$slot]['count']++;
                    $stats[$slot]['pnl'] += (float) $trade->pnl;
                    break;
                }
            }
        }

        $peor = collect($stats)
            ->filter(fn (array $s): bool => $s['count'] >= self::MIN_SAMPLE && $s['pnl'] < -self::MIN_COST)
            ->sortBy('pnl')
            ->take(1);

        if ($peor->isEmpty()) {
            return [];
        }

        $slot = (string) $peor->keys()->first();
        $datos = $peor->first();

        // La regla no es «no operar por la tarde», es «operar solo en la ventana
        // que queda»: el plan de la cuenta guarda un horario, no una exclusión.
        // Solo cuentan las franjas en las que de verdad operas: una ventana
        // construida sobre horas en las que nunca has abierto nada no dice nada.
        $usadas = array_intersect_key($slots, $stats);
        $ventana = $this->windowExcluding($slot, $usadas);

        return [[
            'key' => 'time_of_day',
            'kind' => $ventana ? TradingRule::KIND_TIME_WINDOW : TradingRule::KIND_MISTAKE,
            'cost' => round(-$datos['pnl'], 2),
            'sample' => $datos['count'],
            'params' => [
                'slot' => $slot,
                'amount' => round(-$datos['pnl'], 2),
                'count' => $datos['count'],
                'from' => $ventana['from'] ?? '',
                'to' => $ventana['to'] ?? '',
            ],
            'config' => $ventana ?? [],
        ]];
    }

    /**
     * La operación del día a partir de la cual el día se tuerce.
     *
     * @param  Collection<int, \App\Models\Trade>  $trades
     * @return array<int, array<string, mixed>>
     */
    private function fromTradesPerDay(Collection $trades): array
    {
        $porDia = $trades
            ->filter(fn ($t): bool => $t->entry_time !== null)
            ->groupBy(fn ($t): string => $t->entry_time->format('Y-m-d'));

        // Posición dentro del día: la 1ª, la 2ª… y lo que dejó cada una.
        $porPosicion = [];

        foreach ($porDia as $delDia) {
            foreach ($delDia->sortBy('entry_time')->values() as $i => $trade) {
                $pos = $i + 1;
                $porPosicion[$pos] ??= ['count' => 0, 'pnl' => 0.0];
                $porPosicion[$pos]['count']++;
                $porPosicion[$pos]['pnl'] += (float) $trade->pnl;
            }
        }

        ksort($porPosicion);

        // Se busca el primer corte a partir del cual todo lo que viene después
        // suma pérdidas, con muestra suficiente para no ser una anécdota.
        foreach (array_keys($porPosicion) as $corte) {
            // El corte tiene que ser una posición que pierda por sí misma: si la
            // segunda operación del día te da dinero, el problema no empieza ahí
            // aunque el acumulado desde ella salga negativo.
            if ($corte < 2 || $porPosicion[$corte]['pnl'] >= 0) {
                continue;
            }

            $despues = array_filter($porPosicion, fn ($k): bool => $k >= $corte, ARRAY_FILTER_USE_KEY);
            $muestra = array_sum(array_column($despues, 'count'));
            $pnl = array_sum(array_column($despues, 'pnl'));

            if ($muestra >= self::MIN_SAMPLE && $pnl < -self::MIN_COST) {
                return [[
                    'key' => 'trades_per_day',
                    'kind' => TradingRule::KIND_MAX_TRADES,
                    'cost' => round(-$pnl, 2),
                    'sample' => $muestra,
                    'params' => ['position' => $corte, 'limit' => $corte - 1, 'amount' => round(-$pnl, 2), 'count' => $muestra],
                    'config' => ['limit' => $corte - 1],
                ]];
            }
        }

        return [];
    }

    /**
     * El día de la semana que sale caro.
     *
     * @param  Collection<int, \App\Models\Trade>  $trades
     * @return array<int, array<string, mixed>>
     */
    private function fromWeekday(Collection $trades): array
    {
        $stats = [];

        foreach ($trades as $trade) {
            if ($trade->entry_time === null) {
                continue;
            }

            $dia = (int) $trade->entry_time->dayOfWeek;

            $stats[$dia] ??= ['count' => 0, 'pnl' => 0.0];
            $stats[$dia]['count']++;
            $stats[$dia]['pnl'] += (float) $trade->pnl;
        }

        $peor = collect($stats)
            ->filter(fn (array $s): bool => $s['count'] >= self::MIN_SAMPLE && $s['pnl'] < -self::MIN_COST)
            ->sortBy('pnl')
            ->take(1);

        if ($peor->isEmpty()) {
            return [];
        }

        $dia = (int) $peor->keys()->first();
        $datos = $peor->first();

        return [[
            'key' => 'weekday',
            'kind' => TradingRule::KIND_WEEKDAY,
            'cost' => round(-$datos['pnl'], 2),
            'sample' => $datos['count'],
            'params' => ['weekday' => $dia, 'amount' => round(-$datos['pnl'], 2), 'count' => $datos['count']],
            'config' => ['weekday' => $dia],
        ]];
    }

    /**
     * La ventana horaria que queda al quitar una franja.
     *
     * Solo sale ventana si la franja mala está en un extremo de las que usas:
     * quitar una de en medio dejaría dos trozos, y un plan guarda una hora de
     * inicio y una de fin, no una lista. Ahí la regla se queda en recordatorio.
     *
     * @param  array<string, array{0: int, 1: int}>  $slots  las franjas que el usuario usa
     * @return array{from: string, to: string}|null
     */
    private function windowExcluding(string $peor, array $slots): ?array
    {
        $restantes = array_keys(array_filter(
            $slots,
            fn (string $slot): bool => $slot !== $peor,
            ARRAY_FILTER_USE_KEY
        ));

        if ($restantes === [] || !isset($slots[$peor])) {
            return null;
        }

        $desde = min(array_map(fn (string $s): int => $slots[$s][0], $restantes));
        $hasta = max(array_map(fn (string $s): int => $slots[$s][1], $restantes));

        // Contigua solo si la franja mala no parte el intervalo por la mitad.
        [$peorDesde, $peorHasta] = $slots[$peor];

        if ($peorDesde > $desde && $peorHasta < $hasta) {
            return null;
        }

        return [
            'from' => sprintf('%02d:00', $desde),
            'to' => sprintf('%02d:59', $hasta),
        ];
    }
}
