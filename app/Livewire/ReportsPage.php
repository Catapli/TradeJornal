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
        'only_shorts' => false, // Nuevo
        'remove_worst' => false,
        'max_daily_trades' => null,
        'fixed_sl' => null, // Nuevo (pips)
        'fixed_tp' => null, // Nuevo (pips)
    ];
    // Datos para la vista
    public $hourlyReportData = [];
    public $sessionReportData = [];
    public $scatterData = []; // Nuevo
    public $distributionData = []; // Nuevo
    public $efficiencyData = []; // NUEVO
    public $radarData = []; // NUEVO
    public $riskData = []; // NUEVO
    public $mistakesData = []; // NUEVO


    public function render(TradingAnalysisService $service)
    {
        // 1. Query Base
        $query = Trade::with('mistakes')->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
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
        $isActive = in_array(true, [
            $this->scenarios['no_fridays'],
            $this->scenarios['only_longs'],
            $this->scenarios['only_shorts'],
            $this->scenarios['remove_worst']
        ]) || !empty($this->scenarios['max_daily_trades'])
            || !empty($this->scenarios['fixed_sl'])
            || !empty($this->scenarios['fixed_tp']);

        if ($isActive) {
            $simTrades = $service->applyScenarios($allTrades, $this->scenarios);
            $simulatedCurve = $service->calculateEquityCurve($simTrades);
            $simulatedStats = $service->calculateSystemHealth($simTrades);
        }

        // 4. Reportes Avanzados
        $this->hourlyReportData = $service->analyzeByHour($allTrades);
        $this->sessionReportData = $service->analyzeBySession($allTrades);

        // Nuevos gráficos psicológicos
        // NUEVO: Eficiencia en lugar de Scatter
        $this->efficiencyData = $service->analyzeTradeEfficiency($allTrades);
        $this->distributionData = $service->analyzeDistribution($allTrades);
        // Calcular Radar
        $this->radarData = $service->analyzeTraderProfile($allTrades);
        // Si no hay datos suficientes, devolvemos un array con ceros para que no rompa
        if (!$this->radarData) {
            $this->radarData = ['Winrate' => 0, 'Rentabilidad' => 0, 'Ratio R:R' => 0, 'Consistencia' => 0, 'Experiencia' => 0];
        }

        // Calcular Balance Actual (Simplificado: Initial + PnL total)
        // En tu app real, esto vendría de $account->current_balance
        $currentBalance = 0;
        if ($this->accountId !== 'all') {
            $account = Account::find($this->accountId);
            $currentBalance = $account ? $account->current_balance : 10000;
        } else {
            // Suma de todas las cuentas o un default
            $currentBalance = Account::where('user_id', Auth::id())->sum('current_balance');
        }

        if ($currentBalance <= 0) $currentBalance = 10000; // Evitar errores en cuentas vacías

        // Llamada al servicio
        $this->riskData = $service->analyzeRiskOfRuin($allTrades, $currentBalance);

        $this->mistakesData = $service->analyzeMistakes($allTrades);

        return view('livewire.reports-page', [
            'accounts' => Account::where('user_id', Auth::id())->get(),
            'realCurve' => $realCurve,
            'realStats' => $realStats,
            'simulatedCurve' => $simulatedCurve,
            'simulatedStats' => $simulatedStats
        ]);
    }
}
