<?php

namespace App\Actions\Dashboard;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Los filtros activos del dashboard (cuentas + rango de fechas) convertidos en query.
 *
 * Vivía como dos métodos privados dentro de DashboardPage, lo que obligaba a que
 * todo el cálculo viviera también allí. Sacándolo, tanto el componente como
 * CalculateDashboardMetrics construyen exactamente la misma query.
 */
class DashboardTradeQuery
{
    public function __construct(
        private array $accountIds,
        private string $dateFrom = '',
        private string $dateTo = '',
        private ?int $userId = null,
        // El informe mensual de una cuenta concreta sí quiere las quemadas: una
        // cuenta reventada es justo la que hay que repasar. Por defecto siguen
        // fuera, que es lo que esperan el dashboard y sus tests.
        private bool $includeBurned = false,
    ) {}

    /** ¿La selección son cuentas concretas, o el «todas» por defecto? */
    private function hasExplicitSelection(): bool
    {
        return !in_array('all', $this->accountIds, true) && count($this->accountIds) > 0;
    }

    /**
     * Filtros de usuario y cuentas, SIN rango de fechas.
     * La comparativa con el periodo anterior necesita esta versión.
     */
    public function base(): Builder
    {
        $query = Trade::query();
        $userId = $this->userId ?? Auth::id();

        // Elegir cuentas a mano es pedirlas: si alguien marca una cuenta quemada
        // o archivada en el selector, quiere ver justo esa. El «todas» por
        // defecto sigue siendo solo las vivas, que es lo que se espera al abrir
        // el panel. El filtro de seguridad por usuario no se relaja nunca.
        if ($this->hasExplicitSelection()) {
            return $query->whereIn('account_id', $this->accountIds)
                ->whereHas('account', fn ($q) => $q->withTrashed()->where('user_id', $userId));
        }

        return $this->includeBurned
            ? $query->forUser($userId)
            : $query->forUserActiveAccounts($userId);
    }

    /** La query base más el rango de fechas, si está completo. */
    public function filtered(): Builder
    {
        $query = $this->base();

        // Solo si AMBAS fechas están definidas: un rango a medias no filtra nada.
        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $query->whereBetween('exit_time', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);
        }

        return $query;
    }

    public function hasDateRange(): bool
    {
        return !empty($this->dateFrom) && !empty($this->dateTo);
    }

    public function dateFrom(): string
    {
        return $this->dateFrom;
    }

    public function dateTo(): string
    {
        return $this->dateTo;
    }
}
