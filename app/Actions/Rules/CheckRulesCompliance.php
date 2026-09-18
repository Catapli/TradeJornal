<?php

declare(strict_types=1);

namespace App\Actions\Rules;

use App\Models\Trade;
use App\Models\TradingRule;
use Illuminate\Support\Collection;

/**
 * Cuánto se han respetado las reglas adoptadas en un periodo (P10).
 *
 * El semáforo de la sesión en vivo dice si estás rompiendo una regla *ahora*.
 * Esto responde a la otra pregunta, la que solo tiene sentido a mes vencido:
 * cuántas veces la rompiste.
 *
 * Solo se mide lo que la máquina puede comprobar sola. Un recordatorio sobre un
 * error sin `mistake_id` sale en el informe sin número, porque inventarle un
 * porcentaje de cumplimiento sería justo la clase de cifra que nadie puede
 * discutir y nadie debería creerse.
 */
final class CheckRulesCompliance
{
    /**
     * @param  Collection<int, Trade>  $trades  las operaciones del periodo, ya filtradas
     * @return array<int, array<string, mixed>>
     */
    public function execute(int $userId, ?int $accountId, Collection $trades): array
    {
        $rules = TradingRule::query()
            ->active()
            ->forAccount($userId, $accountId)
            ->orderBy('id')
            ->get();

        return $rules
            ->map(fn (TradingRule $rule): array => $this->check($rule, $trades))
            ->all();
    }

    /** @param  Collection<int, Trade>  $trades */
    private function check(TradingRule $rule, Collection $trades): array
    {
        $config = $rule->config ?? [];

        $result = match ($rule->kind) {
            TradingRule::KIND_MAX_TRADES => $this->maxTrades($trades, (int) ($config['limit'] ?? 0)),
            TradingRule::KIND_TIME_WINDOW => $this->timeWindow($trades, $config),
            TradingRule::KIND_WEEKDAY => $this->weekday($trades, $config),
            TradingRule::KIND_MISTAKE => $this->mistake($trades, $config),
            default => null,
        };

        return [
            'id' => $rule->id,
            'kind' => $rule->kind,
            'text' => $rule->text,
            'is_global' => $rule->isGlobal(),
            // null = regla que se cumple a mano y no se puede puntuar sola.
            'breaches' => $result['breaches'] ?? null,
            'scope' => $result['scope'] ?? null,
            'unit' => $result['unit'] ?? null,
            'rate' => $this->rate($result),
        ];
    }

    /**
     * Tope de operaciones al día: se rompe por días, no por operaciones.
     *
     * @param  Collection<int, Trade>  $trades
     */
    private function maxTrades(Collection $trades, int $limit): ?array
    {
        if ($limit < 1) {
            return null;
        }

        $porDia = $trades
            ->filter(fn (Trade $t): bool => $t->entry_time !== null)
            ->countBy(fn (Trade $t): string => $t->entry_time->toDateString());

        return [
            'breaches' => $porDia->filter(fn (int $n): bool => $n > $limit)->count(),
            'scope' => $porDia->count(),
            'unit' => 'days',
        ];
    }

    /**
     * Ventana horaria: rompe cada operación abierta fuera de ella.
     *
     * @param  Collection<int, Trade>  $trades
     */
    private function timeWindow(Collection $trades, array $config): ?array
    {
        $from = $config['from'] ?? null;
        $to = $config['to'] ?? null;

        if (!is_string($from) || !is_string($to)) {
            return null;
        }

        $conHora = $trades->filter(fn (Trade $t): bool => $t->entry_time !== null);

        return [
            'breaches' => $conHora->filter(function (Trade $t) use ($from, $to): bool {
                $hora = $t->entry_time->format('H:i');

                return $hora < $from || $hora > $to;
            })->count(),
            'scope' => $conHora->count(),
            'unit' => 'trades',
        ];
    }

    /**
     * Día evitado: rompe cada operación abierta ese día.
     *
     * @param  Collection<int, Trade>  $trades
     */
    private function weekday(Collection $trades, array $config): ?array
    {
        if (!isset($config['weekday'])) {
            return null;
        }

        $dia = (int) $config['weekday'];
        $conHora = $trades->filter(fn (Trade $t): bool => $t->entry_time !== null);

        return [
            'breaches' => $conHora->filter(fn (Trade $t): bool => (int) $t->entry_time->dayOfWeek === $dia)->count(),
            'scope' => $conHora->count(),
            'unit' => 'trades',
        ];
    }

    /**
     * Recordatorio sobre un error concreto: rompe cada operación marcada con él.
     *
     * @param  Collection<int, Trade>  $trades
     */
    private function mistake(Collection $trades, array $config): ?array
    {
        $mistakeId = $config['mistake_id'] ?? null;

        if ($mistakeId === null) {
            return null;
        }

        return [
            'breaches' => $trades->filter(
                fn (Trade $t): bool => $t->mistakes->contains('id', (int) $mistakeId)
            )->count(),
            'scope' => $trades->count(),
            'unit' => 'trades',
        ];
    }

    /** Porcentaje respetado. Sin universo que medir no hay porcentaje. */
    private function rate(?array $result): ?float
    {
        if ($result === null || ($result['scope'] ?? 0) < 1) {
            return null;
        }

        return round((1 - $result['breaches'] / $result['scope']) * 100, 1);
    }
}
