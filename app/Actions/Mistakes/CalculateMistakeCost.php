<?php

declare(strict_types=1);

namespace App\Actions\Mistakes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Lo que los errores marcados le han costado al usuario (Fase 5 · P2).
 *
 * El coste es **contrafactual**: lo que habría ganado sin esas operaciones menos
 * lo que ganó con ellas. No es lo mismo que «sumar lo perdido»: una operación
 * marcada que salió bien resta al coste, y tiene que restar, porque el error que
 * a veces sale bien es justo el que más cuesta dejar.
 *
 * De ahí sale un número que se puede decir en voz alta —«sin las de held_loser
 * llevarías 1.537 € más»— y que además coincide con el escenario del Laboratorio,
 * porque los dos hacen exactamente la misma cuenta.
 *
 * Todo va acompañado de la **cobertura**: sobre cuántas operaciones está
 * calculado. Un coste sacado de treinta operaciones repasadas de trescientas no
 * es mentira, pero presentado sin ese dato lo parece.
 */
class CalculateMistakeCost
{
    /** Franjas horarias del desglose, en hora local de la operación. */
    private const SLOTS = [
        'early' => [0, 7],
        'morning' => [8, 12],
        'afternoon' => [13, 17],
        'evening' => [18, 23],
    ];

    /**
     * @param  Builder  $query  la misma query que pinta la pantalla que lo llama
     * @return array<string, mixed>
     */
    public function execute(Builder $query): array
    {
        $trades = (clone $query)->with('mistakes')->get();

        return $this->fromTrades($trades);
    }

    /**
     * @param  Collection<int, \App\Models\Trade>  $trades
     * @return array<string, mixed>
     */
    public function fromTrades(Collection $trades): array
    {
        $total = $trades->count();

        if ($total === 0) {
            return $this->empty();
        }

        $marked = $trades->filter(fn ($t): bool => $t->mistakes->isNotEmpty());

        $realPnl = round((float) $trades->sum('pnl'), 2);
        $markedPnl = round((float) $marked->sum('pnl'), 2);

        // Quitar las marcadas es quitar su P&L: el coste es esa diferencia con el
        // signo dado la vuelta, para que «cuesta» sea un número positivo.
        $pnlWithout = round($realPnl - $markedPnl, 2);
        $cost = round(-$markedPnl, 2);

        $reviewed = $trades->filter(
            fn ($t): bool => $t->mistakes->isNotEmpty() || $t->mistakes_reviewed_at !== null
        )->count();

        return [
            'trades_total' => $total,
            'reviewed' => $reviewed,
            'coverage' => round($reviewed / $total * 100, 1),
            'marked_trades' => $marked->count(),
            'pending_review' => $this->pendingReview($trades),

            'real_pnl' => $realPnl,
            'pnl_without' => $pnlWithout,
            'cost' => $cost,

            'by_mistake' => $this->byMistake($marked),
            'by_slot' => $this->bySlot($marked),
        ];
    }

    /**
     * Perdedoras sin repasar: las que tiene sentido ponerle delante al usuario.
     *
     * Solo las perdedoras porque son donde está el dinero y donde el recuerdo
     * todavía escuece; repasar 300 ganadoras no lo hace nadie.
     *
     * @param  Collection<int, \App\Models\Trade>  $trades
     */
    private function pendingReview(Collection $trades): int
    {
        return $trades->filter(fn ($t): bool => (float) $t->pnl < 0
            && $t->mistakes->isEmpty()
            && $t->mistakes_reviewed_at === null)->count();
    }

    /**
     * Coste por error, de más caro a menos.
     *
     * Una operación con dos errores cuenta entera en los dos, así que la suma de
     * los desgloses no da el total. Es deliberado —repartir el coste entre los
     * errores de una misma operación sería inventarse una proporción— y la
     * interfaz lo advierte.
     *
     * @param  Collection<int, \App\Models\Trade>  $marked
     * @return array<int, array<string, mixed>>
     */
    private function byMistake(Collection $marked): array
    {
        $stats = [];

        foreach ($marked as $trade) {
            foreach ($trade->mistakes as $mistake) {
                $id = (int) $mistake->id;

                $stats[$id] ??= [
                    'id' => $id,
                    'slug' => $mistake->slug,
                    'name' => $mistake->display_name,
                    'color' => $mistake->color_hex,
                    'weight' => (int) $mistake->weight,
                    'count' => 0,
                    'pnl' => 0.0,
                ];

                $stats[$id]['count']++;
                $stats[$id]['pnl'] += (float) $trade->pnl;
            }
        }

        return collect($stats)
            ->map(function (array $row): array {
                $row['pnl'] = round($row['pnl'], 2);
                $row['cost'] = round(-$row['pnl'], 2);
                $row['avg_cost'] = round($row['cost'] / $row['count'], 2);

                return $row;
            })
            ->sortByDesc('cost')
            ->values()
            ->all();
    }

    /**
     * Coste por franja horaria.
     *
     * Sirve para la frase que de verdad cambia algo: no «cometes este error»,
     * sino «lo cometes por la tarde». Las franjas vacías no se devuelven.
     *
     * @param  Collection<int, \App\Models\Trade>  $marked
     * @return array<int, array<string, mixed>>
     */
    private function bySlot(Collection $marked): array
    {
        $stats = [];

        foreach ($marked as $trade) {
            if ($trade->entry_time === null) {
                continue;
            }

            $slot = $this->slotFor((int) $trade->entry_time->format('G'));

            $stats[$slot] ??= ['slot' => $slot, 'count' => 0, 'pnl' => 0.0];
            $stats[$slot]['count']++;
            $stats[$slot]['pnl'] += (float) $trade->pnl;
        }

        return collect($stats)
            ->map(function (array $row): array {
                $row['pnl'] = round($row['pnl'], 2);
                $row['cost'] = round(-$row['pnl'], 2);

                return $row;
            })
            ->sortByDesc('cost')
            ->values()
            ->all();
    }

    private function slotFor(int $hour): string
    {
        foreach (self::SLOTS as $slot => [$from, $to]) {
            if ($hour >= $from && $hour <= $to) {
                return $slot;
            }
        }

        return 'early';
    }

    /** @return array<string, mixed> */
    private function empty(): array
    {
        return [
            'trades_total' => 0,
            'reviewed' => 0,
            'coverage' => 0.0,
            'marked_trades' => 0,
            'pending_review' => 0,
            'real_pnl' => 0.0,
            'pnl_without' => 0.0,
            'cost' => 0.0,
            'by_mistake' => [],
            'by_slot' => [],
        ];
    }
}
