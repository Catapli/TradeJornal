<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Trade;
use App\Models\Account;
use App\Services\TradingAnalysisService;
use Illuminate\Support\Facades\Auth;

class ReportsPage extends Component
{
    public $accountId = 'all';

    // Escenarios What-If
    public $scenarios = [
        'no_fridays' => false,
        'only_longs' => false,
        'remove_worst' => false,
        'max_daily_trades' => null,
    ];

    // Datos para la vista
    public $hourlyReportData = [];
    public $sessionReportData = [];
    public $scatterData = []; // Nuevo
    public $distributionData = []; // Nuevo

    public function render(TradingAnalysisService $service)
    {
        // 1. Query Base
        $query = Trade::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
            ->orderBy('entry_time', 'asc');

        if ($this->accountId !== 'all') {
            $query->where('account_id', $this->accountId);
        }

        $allTrades = $query->get();

        // 2. Curvas y Stats (Realidad)
        $realCurve = $service->calculateEquityCurve($allTrades);
        $realStats = $service->calculateSystemHealth($allTrades);

        // 3. Simulación
        $simulatedCurve = [];
        $simulatedStats = null;
        $isActive = in_array(true, $this->scenarios) || !empty($this->scenarios['max_daily_trades']);

        if ($isActive) {
            $simTrades = $service->applyScenarios($allTrades, $this->scenarios);
            $simulatedCurve = $service->calculateEquityCurve($simTrades);
            $simulatedStats = $service->calculateSystemHealth($simTrades);
        }

        // 4. Reportes Avanzados
        $this->hourlyReportData = $service->analyzeByHour($allTrades);
        $this->sessionReportData = $service->analyzeBySession($allTrades);

        // Nuevos gráficos psicológicos
        $this->scatterData = $service->analyzeDurationScatter($allTrades);
        $this->distributionData = $service->analyzeDistribution($allTrades);

        return view('livewire.reports-page', [
            'accounts' => Account::where('user_id', Auth::id())->get(),
            'realCurve' => $realCurve,
            'realStats' => $realStats,
            'simulatedCurve' => $simulatedCurve,
            'simulatedStats' => $simulatedStats
        ]);
    }
}
