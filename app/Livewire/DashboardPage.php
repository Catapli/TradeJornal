<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Alert;
use App\Models\Trade;
use App\Models\Traffic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPage extends Component
{
    // ? Variables Nuevas
    public $selectedAccounts = []; // Aquí se guardarán los IDs (ej: [1, 5, 8])
    public $availableAccounts = [];
    // Datos para el gráfico
    public $winRateChartData = [];
    public $user;

    public $avgPnLChartData = []; // Variable para el gráfico
    public $dailyWinLossData = []; // Diario Ganancias Perdidas
    public $pnlTotal = 0;
    // Estado del Calendario
    public $calendarDate; // Fecha de referencia (ej: 2026-01-01)
    public $calendarGrid = []; // Array con los datos para la vista

    public function mount()
    {
        $this->user = Auth::user();
        // Cargamos las cuentas con un formato amigable para el componente
        $this->availableAccounts = Account::where('user_id', $this->user->id)->where('status', '!=', 'burned')
            ->get()
            ->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'subtext' => $acc->login . ' (' . $acc->broker_name . ')' // Opcional
                ];
            });

        // Seleccionar 'all' por defecto o dejar vacío según prefieras
        $this->selectedAccounts = ['all'];

        $this->calculateStats();
        $this->generateCalendar(); // Generamos el grid
    }

    public function getTradesQuery()
    {
        $query = Trade::query();

        // 1. Si hay cuentas específicas seleccionadas (y no es 'all')
        if (!in_array('all', $this->selectedAccounts) && count($this->selectedAccounts) > 0) {
            $query->whereIn('account_id', $this->selectedAccounts);
        }

        // 2. Filtro de seguridad por usuario Y CONSISTENCIA DE ESTADO
        $query->whereHas('account', function ($q) {
            $q->where('user_id', $this->user->id);

            // 👇 AQUÍ ESTÁ EL FIX:
            // Debemos excluir las cuentas quemadas igual que hiciste en el mount().
            // De lo contrario, "ALL" incluye cuentas zombis que no están en el select.
            $q->where('status', '!=', 'burned');
        });

        return $query;
    }

    private function calculateStats()
    {
        // ... (Tu query anterior se mantiene igual) ...
        $query = $this->getTradesQuery();

        $stats = $query->selectRaw('
        COUNT(*) as total_trades,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades
    ')->first();

        $total = $stats->total_trades ?? 0;
        $wins = $stats->winning_trades ?? 0;
        $losses = $total - $wins;
        $winRate = $total > 0 ? round(($wins / $total) * 100, 2) : 0;

        $this->winRateChartData = [
            'series' => [(int)$wins, (int)$losses],
            'rate' => $winRate,
            'count_wins' => (int)$wins,     // 👈 Nuevo: Para la pastilla verde
            'count_losses' => (int)$losses  // 👈 Nuevo: Para la pastilla roja
        ];

        // ------------------------------------------------------
        // 1. CÁLCULO DE PNL TOTAL (Optimizado usando Accounts)
        // ------------------------------------------------------

        // Iniciamos la query sobre la tabla CUENTAS
        $accountsQuery = Account::where('user_id', $this->user->id);

        // Filtramos según la selección del multiselect
        if (!in_array('all', $this->selectedAccounts) && count($this->selectedAccounts) > 0) {
            // Si hay selección específica
            $accountsQuery->whereIn('id', $this->selectedAccounts);
        } else {
            // Si es 'all', aplicamos el mismo filtro que usaste en el mount 
            // (para ser coherentes con lo que ve el usuario)
            $accountsQuery->where('status', '!=', 'burned');
        }

        // Hacemos una única consulta a la base de datos para sumar balances
        $sums = $accountsQuery->selectRaw('
        SUM(current_balance) as total_current, 
        SUM(initial_balance) as total_initial
    ')->first();

        // El PnL es simplemente la diferencia: (Lo que tengo ahora - Lo que tenía al principio)
        $this->pnlTotal = ($sums->total_current ?? 0) - ($sums->total_initial ?? 0);


        // ------------------------------------------------------
        // 3. CÁLCULO DE MEDIAS (AVG WIN vs AVG LOSS)
        // ------------------------------------------------------
        // Reutilizamos la query de trades (con los filtros de cuentas aplicados)
        $avgs = $query->selectRaw('
        AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
        AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss
    ')->first();

        $avgWin = $avgs->avg_win ? round($avgs->avg_win, 2) : 0;
        $avgLoss = $avgs->avg_loss ? round($avgs->avg_loss, 2) : 0; // Esto será negativo (ej: -50.00)

        // CÁLCULO DEL RATIO RIESGO/BENEFICIO
        // Fórmula: Ganancia Media / Valor Absoluto de Pérdida Media
        // Si avg_loss es 0, el ratio es 0 (o infinito, pero ponemos 0 para no romper)
        $rrRatio = ($avgLoss != 0) ? abs($avgWin / $avgLoss) : 0;

        $this->avgPnLChartData = [
            'avg_win' => $avgWin,
            'avg_loss' => $avgLoss,   // Lo enviamos negativo para que se pinte el texto "-50€"
            'rr_ratio' => round($rrRatio, 2) // Lo enviamos calculado (ej: 1.5)
        ];

        // ------------------------------------------------------
        // 4. CÁLCULO DE DÍAS GANADORES VS PERDEDORES
        // ------------------------------------------------------

        $query = $this->getTradesQuery();

        // Hacemos la agrupación directamente en la base de datos
        // Esto devuelve una lista de días con su PnL total: 
        // [ {date: "2026-01-13", daily_pnl: 500}, {date: "2026-01-14", daily_pnl: -200} ]
        $dailyStats = $query->selectRaw('DATE(exit_time) as trade_date, SUM(pnl) as daily_pnl')
            ->whereNotNull('exit_time')
            ->groupByRaw('DATE(exit_time)') // groupByRaw funciona en MySQL y Postgres
            ->get();

        // Ahora contamos sobre los resultados agrupados
        $winDays = $dailyStats->where('daily_pnl', '>', 0)->count();
        $lossDays = $dailyStats->where('daily_pnl', '<', 0)->count();
        // $breakEvenDays = $dailyStats->where('daily_pnl', '=', 0)->count();

        $totalDays = $winDays + $lossDays;

        // Evitamos división por cero
        $dailyWinRate = $totalDays > 0 ? round(($winDays / $totalDays) * 100, 2) : 0;

        $this->dailyWinLossData = [
            'series' => [(int)$winDays, (int)$lossDays],
            'rate' => $dailyWinRate,
            'count_wins' => $winDays,
            'count_losses' => $lossDays
        ];
    }

    // Hook de Livewire: Se ejecuta cuando cambia el multiselect
    public function updatedSelectedAccounts()
    {
        $this->calculateStats();
        $this->generateCalendar();
        // Avisamos a Alpine que hay nuevos datos para redibujar gráficos
        $this->dispatch('dashboard-updated');
    }

    // Métodos de Navegación del Calendario
    public function nextMonth()
    {
        $this->calendarDate = Carbon::parse($this->calendarDate)->addMonth()->format('Y-m-d');
        $this->generateCalendar();
    }

    public function prevMonth()
    {
        $this->calendarDate = Carbon::parse($this->calendarDate)->subMonth()->format('Y-m-d');
        $this->generateCalendar();
    }

    public function generateCalendar()
    {
        $date = Carbon::parse($this->calendarDate);

        // 1. Definir rango visual (Lunes de la primera semana - Domingo de la última)
        $startOfCalendar = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar   = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // 2. Obtener Trades en ese rango
        $query = $this->getTradesQuery();

        // Agrupamos por día para obtener el PnL diario
        $trades = $query->whereBetween('exit_time', [$startOfCalendar, $endOfCalendar])
            ->selectRaw('DATE(exit_time) as date, SUM(pnl) as daily_pnl')
            ->groupByRaw('DATE(exit_time)')
            ->get()
            ->keyBy('date'); // Indexamos por fecha para búsqueda rápida

        // 3. Construir el Grid
        $grid = [];
        $currentDay = $startOfCalendar->copy();

        while ($currentDay <= $endOfCalendar) {
            $dayString = $currentDay->format('Y-m-d');

            // Buscamos si hubo trades ese día
            // Nota: En la DB la fecha puede venir como '2026-01-13' (string)
            $dayData = $trades->get($dayString);
            $pnl = $dayData ? $dayData->daily_pnl : null;

            $grid[] = [
                'day' => $currentDay->format('d'),
                'date' => $dayString,
                'pnl' => $pnl,
                'is_current_month' => $currentDay->month === $date->month,
                'is_today' => $currentDay->isToday(),
            ];

            $currentDay->addDay();
        }

        $this->calendarGrid = $grid;
    }





    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
