<?php

declare(strict_types=1);

namespace App\Actions\Export;

use App\Actions\Dashboard\CalculateDashboardMetrics;
use App\Actions\Dashboard\DashboardTradeQuery;
use App\Actions\Mistakes\CalculateMistakeCost;
use App\Actions\Rules\CheckRulesCompliance;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Las cifras de un mes, listas para imprimir (Fase 5 · P10).
 *
 * No calcula nada por su cuenta: orquesta las acciones que ya pintan las
 * pantallas. Es deliberado. Un informe que sacara sus propios números acabaría
 * discrepando del dashboard, y entonces el usuario tendría dos verdades y
 * ninguna en la que apoyarse delante de una prop firm.
 *
 * El mes se recorta en la hora del usuario, no en la del servidor: el 1 de
 * agosto empieza cuando empieza para quien opera.
 */
final class BuildMonthlyReport
{
    public function __construct(
        private CalculateDashboardMetrics $metrics,
        private CalculateMistakeCost $mistakeCost,
        private CheckRulesCompliance $rules,
    ) {}

    /**
     * @param  CarbonImmutable  $month  cualquier día del mes que se quiere
     * @param  Account|null  $account  null = todas las cuentas del usuario
     * @return array<string, mixed>
     */
    public function execute(User $user, CarbonImmutable $month, ?Account $account = null): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        $query = $this->queryFor($user, $account, $start, $end);
        $trades = $query->filtered()->with(['mistakes', 'account:id,currency'])->get();

        $metrics = $this->metrics->execute($query);

        return [
            'generated_at' => now($user->preferredTimezone()),
            'user' => ['name' => $user->name],
            'month' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => ucfirst($start->translatedFormat('F Y')),
            ],
            'account' => $this->accountCard($account),
            'currency' => $this->currency($account, $trades),
            'metrics' => $metrics,
            'previous' => $this->previousMonth($user, $account, $start),
            'equity' => $metrics['evolutionChartData'] ?? ['categories' => [], 'data' => []],
            'calendar' => $this->calendar($trades, $start, $end),
            'mistake_cost' => $this->mistakeCost->fromTrades($trades),
            'rules' => $this->rules->execute($user->id, $account?->id, $trades),
            'trades_count' => $trades->count(),
            // Sin operaciones no hay informe: la pantalla lo comprueba antes de
            // generar un PDF de ceros que no le sirve a nadie.
            'has_activity' => $trades->isNotEmpty(),
        ];
    }

    private function queryFor(User $user, ?Account $account, CarbonImmutable $start, CarbonImmutable $end): DashboardTradeQuery
    {
        return new DashboardTradeQuery(
            accountIds: $account ? [$account->id] : ['all'],
            dateFrom: $start->toDateString(),
            dateTo: $end->toDateString(),
            userId: $user->id,
            // Una cuenta elegida a mano se informa aunque esté quemada; el "todas"
            // mantiene el criterio del dashboard y las deja fuera.
            includeBurned: $account !== null,
        );
    }

    /**
     * El mismo mes, un mes antes, con la misma fórmula.
     *
     * No se usa la comparativa que trae CalculateDashboardMetrics porque esa
     * compara contra los N días anteriores, y en un informe mensual "el mes
     * pasado" tiene que ser el mes pasado, no los últimos 31 días.
     *
     * @return array<string, mixed>|null
     */
    private function previousMonth(User $user, ?Account $account, CarbonImmutable $start): ?array
    {
        $prevStart = $start->subMonth()->startOfMonth();
        $query = $this->queryFor($user, $account, $prevStart, $prevStart->endOfMonth());

        if ($query->filtered()->doesntExist()) {
            return null; // sin mes anterior no hay comparativa honesta
        }

        $metrics = $this->metrics->execute($query);

        return [
            'label' => ucfirst($prevStart->translatedFormat('F Y')),
            'pnl' => round((float) ($metrics['pnlTotal'] ?? 0), 2),
            'win_rate' => (float) ($metrics['winRateChartData']['rate'] ?? 0),
            'trades' => (int) (($metrics['winRateChartData']['count_wins'] ?? 0)
                + ($metrics['winRateChartData']['count_losses'] ?? 0)),
            'profit_factor' => $metrics['extraKpis']['profit_factor'] ?? null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function accountCard(?Account $account): ?array
    {
        if ($account === null) {
            return null;
        }

        return [
            'name' => $account->name,
            'phase' => $account->phase_label,
            'status' => $account->status_formatted,
            'currency' => $account->currency,
            'balance' => (float) $account->current_balance,
        ];
    }

    /**
     * La divisa del informe.
     *
     * Con una cuenta elegida es la suya. Con "todas" puede haber varias, y sumar
     * euros con dólares poniéndole un símbolo sería mentir con el tipo de letra:
     * ahí se rotula sin símbolo salvo que todas coincidan.
     *
     * @param  Collection<int, Trade>  $trades
     */
    private function currency(?Account $account, Collection $trades): ?string
    {
        if ($account !== null) {
            return $account->currency;
        }

        $divisas = $trades->map(fn (Trade $t): ?string => $t->account?->currency)->filter()->unique();

        return $divisas->count() === 1 ? (string) $divisas->first() : null;
    }

    /**
     * El mes en rejilla de semanas, de lunes a domingo.
     *
     * Se agrupa por fecha de cierre igual que la curva de capital del dashboard.
     * Agrupar unos números por apertura y otros por cierre es la forma más
     * silenciosa de que dos bloques del mismo informe no cuadren.
     *
     * @param  Collection<int, Trade>  $trades
     * @return array<int, array<int, array<string, mixed>|null>>
     */
    private function calendar(Collection $trades, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $porDia = $trades
            ->filter(fn (Trade $t): bool => $t->exit_time !== null)
            ->groupBy(fn (Trade $t): string => $t->exit_time->toDateString());

        $semanas = [];
        $cursor = $start->startOfWeek();
        $ultimo = $end->endOfWeek();

        while ($cursor <= $ultimo) {
            $semana = [];

            for ($i = 0; $i < 7; $i++) {
                $dia = $cursor->addDays($i);
                $fecha = $dia->toDateString();

                // Los días de relleno del mes vecino van vacíos: la rejilla es
                // cuadrada, el mes no.
                if ($dia->month !== $start->month) {
                    $semana[] = null;

                    continue;
                }

                $delDia = $porDia->get($fecha);

                $semana[] = [
                    'day' => $dia->day,
                    'date' => $fecha,
                    'trades' => $delDia?->count() ?? 0,
                    'pnl' => $delDia ? round((float) $delDia->sum('pnl'), 2) : 0.0,
                ];
            }

            $semanas[] = $semana;
            $cursor = $cursor->addWeek();
        }

        return $semanas;
    }
}
