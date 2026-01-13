<?php

namespace App\Livewire;

use App\Jobs\SyncAccountTrades;
use App\Jobs\SyncMt5Account;
use App\Livewire\Forms\AccountForm;
use App\Models\Account;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\PropFirm;
use App\Models\Trade;
use App\Services\Mt5Gateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AccountPage extends Component
{

    public $accounts;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedAccount;
    public $selectedAccountId;

    // ? Datos para el gráfico de balance
    public $balanceChartData = [
        'labels' => [],
        'datasets' => []
    ];
    // ? Estadisticas de cuenta
    public $totalPnl = 0; // PNL total de la cuenta
    public $winRate = 0; // % de trades ganadores
    public $totalTrades; // Total de trades
    public $firstTradeDate; // Fecha del primer trade
    public $avgDurationMinutes = 0;
    public $avgDurationFormatted = '0h 0m';
    public $maxWin = 0;      // Ganancia Máxima
    public $maxLoss = 0;     // Pérdida Máxima
    public $topAsset = 'N/A'; // Símbolo más operado
    public $tradingDays = 0; // Días de trading activos
    public $avgWinTrade = 0;    // €127.50
    public $avgLossTrade = 0;   // €55.20
    public $arr = 0;
    public $accountAgeDays = 0;
    public $accountAgeFormatted = '0 días';
    public $initialBalance = 0;
    public $totalProfitLoss = 0;
    public $profitPercentage = 0;

    public $profitFactor = 0;    // 2.15
    public $grossProfit = 0;     // €12,450
    public $grossLoss = 0;       // €5,780

    public $lastSyncedAccountId;
    public $isSyncing = false;  // idle, syncing, done
    public $syncStartTime = null; // 👇 Nueva propiedad para guardar cuándo empezamos
    public $selectedTimeframe = 'all'; // ← NUEVO
    public AccountForm $form;
    public $propFirmsData = [];





    public $timeframes = [  // ← ASEGÚRATE de tener esto
        '1h' => ['minutes' => 60, 'format' => 'H:i'],     // "14:30"
        '24h' => ['hours' => 24, 'format' => 'd H:i'],    // "08 14:30" 
        '7d' => ['days' => 7, 'format' => 'd M (D)'],   // "08 Jan (Dom)" ← ÚNICO
        'all' => ['all' => true, 'format' => 'd MMM yy']  // "08 Jan 26"
    ];

    public function mount()
    {
        $user = Auth::user();
        $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->orderBy('name')->get();
        $this->selectedAccount = $this->accounts->first(); // ← Array[0]
        $this->selectedAccountId = $this->selectedAccount?->id; // <--- ESTO ES CLAVE
        // $this->propFirms = PropFirm::select('id', 'name')->orderBy('name')->get();
        // Cargamos toda la jerarquía necesaria y la convertimos a Array
        // Esto es muy rápido si tienes < 5000 filas en total (que seguro que sí)
        $this->propFirmsData = PropFirm::with(['programs.levels' => function ($query) {
            $query->select('id', 'program_id', 'size', 'currency');
        }])
            ->orderBy('name')
            ->get() // Obtenemos colección
            ->toArray(); // Convertimos a Array para pasarlo al JS

        $this->updateData();
    }

    /**
     * 🔥 ESTA ES LA FUNCIÓN QUE QUERÍAS EJECUTAR
     * Aquí pones toda la lógica post-job.
     */
    public function onSyncCompleted()
    {
        // NO actualices last_sync aquí, el Job ya lo hizo.
        $this->updateData();
        // Verificamos si la cuenta se ha quemado tras la sincronización
        if ($this->selectedAccount->status === 'burned') {
            $this->showAlert('error', '🚨 CUENTA QUEMADA: El balance ha llegado a 0. La cuenta se ha marcado como perdida.');
            $this->isSyncing = false;

            // Opcional: Refrescar la lista de cuentas para que desaparezca o se vea el status
            $user = Auth::user();
            $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->get();
            $this->selectedAccount = $this->accounts->first(); // ← Array[0]
            $this->updateData();
            return;
        }

        $this->dispatch('timeframe-updated', timeframe: $this->selectedTimeframe);
        $this->showAlert('success', '✅ Sincronización finalizada correctamente.');
        // session()->flash('message', "✅ Sincronización finalizada correctamente.");
        Log::info("Livewire: Lógica post-sync ejecutada.");
    }


    //* Para modificar el timeframe del grafico
    public function setTimeframe($timeframe) // ← NUEVO MÉTODO
    {
        $this->selectedTimeframe = $timeframe;
        $this->loadBalanceChart(); // ← Recarga gráfico filtrado
        $this->dispatch('timeframe-updated', timeframe: $timeframe);
    }

    // public function refreshData()
    // {
    //     $this->updateData();  // Tu método existente
    //     $this->isSyncing = false;
    //     session()->flash('message', '✅ Sync completado');
    // }

    /**
     * Esta función es llamada automáticamente por wire:poll cada X segundos
     * MIENTRAS $isSyncing sea true.
     */
    public function checkSyncStatus()
    {
        $this->selectedAccount = $this->selectedAccount->fresh();

        // Si el mensaje sigue siendo nuestra bandera, el Job aún no ha escrito su resultado
        if ($this->selectedAccount->sync_error_message === 'WAITING_JOB') {
            // Log::info("El Job sigue trabajando o en cola...");
            return;
        }

        // Si llegamos aquí, es porque el Job terminó y cambió el mensaje (a null o al error de cURL)
        $updatedAt = Carbon::parse($this->selectedAccount->updated_at);
        $startTime = Carbon::parse($this->syncStartTime);

        // Solo actuamos si el cambio es posterior al inicio
        if ($updatedAt->greaterThan($startTime)) {

            $this->isSyncing = false;

            if ($this->selectedAccount->sync_error) {
                $this->showAlert('error', '🚫 Sync falló: ' . 'No se ha podido establecer conexión con el Servidor');
                $this->updateData();
                return;
            }

            // Si no hay error y el mensaje ya no es WAITING_JOB, es éxito
            $this->onSyncCompleted();
        }
    }

    private function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? sprintf('%dh %02dm', $hours, $mins) : $mins . 'm';
    }




    public function syncSelectedAccount(): void
    {
        // 1. Marcamos un estado interno en la base de datos
        $this->selectedAccount->update([
            'sync_error' => false,
            'sync_error_message' => 'WAITING_JOB', // <- Nuestra bandera
        ]);

        $this->isSyncing = true;

        // Guardamos el momento exacto DESPUÉS del update inicial
        $this->selectedAccount = $this->selectedAccount->fresh();
        $this->syncStartTime = $this->selectedAccount->updated_at;

        Log::info("Iniciando sync para cuenta ID: " . $this->selectedAccount->id);

        SyncMt5Account::dispatch($this->selectedAccount);
    }

    public function changeAccount($accountId)
    {
        $this->selectedAccount = $this->accounts->firstWhere('id', $accountId);
        $this->updateData();
        $this->dispatch('timeframe-updated', timeframe: 'all');
    }



    // * Actualizar la data
    private function updateData()
    {
        if ($this->selectedAccount) {
            $this->totalPnl = $this->selectedAccount->trades()->sum('pnl');
            $this->initialBalance = $this->selectedAccount->initial_balance;


            // 2. Calculamos el balance teórico
            $theoreticalBalance = $this->initialBalance + $this->totalPnl;

            if (is_null($this->selectedAccount->last_sync)) {
                if ($this->selectedAccount->current_balance != $theoreticalBalance) {
                    $this->selectedAccount->update([
                        'current_balance' => $theoreticalBalance
                    ]);
                }
            }
            // $newBalance = $this->selectedAccount->initial_balance + $this->totalPnl;

            // SOLO guarda si realmente hay un cambio de balance
            // if ($this->selectedAccount->current_balance != $newBalance) {
            //     $this->selectedAccount->current_balance = $newBalance;
            //     $this->selectedAccount->save();
            // }

            $this->calculateStatistics();
            $this->loadBalanceChart();
        }
        $this->selectedAccountId = $this->selectedAccount?->id; // <--- ESTO ES CLAVE
        $this->dispatch('account-updated', timeframe: 'all');
    }

    private function calculateStatistics()
    {
        $trades = $this->selectedAccount->trades();

        // Query eficiente UNA SOLA VEZ para todas las stats
        $stats = $trades->selectRaw('
        COUNT(*) as total_trades,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
        AVG(duration_minutes) as avg_duration_minutes,
        MAX(pnl) as max_win,
        MIN(pnl) as max_loss')->first();

        $this->totalTrades = $stats->total_trades; // Total de Trades
        $this->winRate = $this->totalTrades > 0 ? round(($stats->winning_trades / $this->totalTrades) * 100, 1) : 0; // % de trades ganadores

        // Tiempo medio retención
        $this->avgDurationMinutes = round($stats->avg_duration_minutes ?? 0);
        $this->avgDurationFormatted = $this->formatDuration($this->avgDurationMinutes);

        // 🆕 Ganancia y pérdida más grandes
        $this->maxWin = $stats->max_win ?? 0;
        $this->maxLoss = abs($stats->max_loss ?? 0); // Positivo para mostrar

        // 🆕 1. SÍMBOLO MÁS OPERADO
        $topAsset = $this->selectedAccount->trades()
            ->join('trade_assets', 'trades.trade_asset_id', '=', 'trade_assets.id')
            ->whereNotNull('trades.exit_time')
            ->selectRaw('trade_assets.symbol, COUNT(*) as trade_count')
            ->groupBy('trade_assets.id', 'trade_assets.symbol')
            ->orderByDesc('trade_count')
            ->first();

        $this->topAsset = $topAsset ? $topAsset->symbol : 'N/A';

        // 🆕 DÍAS DE TRADING (día con al menos 1 entry_time)
        $tradingDays = $this->selectedAccount->trades()
            ->whereNotNull('entry_time')
            ->selectRaw('COUNT(DISTINCT DATE(entry_time)) as trading_days')
            ->value('trading_days');

        $this->tradingDays = $tradingDays ?? 0;

        // 🆕 Ganancia y Pérdida MEDIA (sin ARRR)
        $avgStats = $this->selectedAccount->trades()
            ->whereNotNull('exit_time')
            ->whereNotNull('pnl')
            ->selectRaw('
            AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
            AVG(CASE WHEN pnl < 0 THEN ABS(pnl) END) as avg_loss_abs
        ')
            ->first();

        $this->avgWinTrade = round($avgStats->avg_win ?? 0, 2);
        $this->avgLossTrade = round($avgStats->avg_loss_abs ?? 0, 2);

        // ARRR calculado a partir de medias
        $this->arr = $this->avgLossTrade > 0 ?
            round($this->avgWinTrade / $this->avgLossTrade, 2) : 0;

        // 🆕 ANTIGÜEDAD DE LA CUENTA (días desde funded_date)
        if ($this->selectedAccount->funded_date) {
            $accountAgeDays = Carbon::parse($this->selectedAccount->funded_date)
                ->diffInDays(now());

            $this->accountAgeDays = $accountAgeDays;
            $this->accountAgeFormatted = $this->formatAge($accountAgeDays);
        } else {
            $this->accountAgeDays = 0;
            $this->accountAgeFormatted = 'N/A';
        }


        // 🆕 FACTOR DE BENEFICIO (Profit Factor)
        $profitFactorStats = $this->selectedAccount->trades()
            ->whereNotNull('exit_time')
            ->whereNotNull('pnl')
            ->selectRaw('
            SUM(CASE WHEN pnl > 0 THEN pnl ELSE 0 END) as gross_profit,
            SUM(CASE WHEN pnl < 0 THEN ABS(pnl) ELSE 0 END) as gross_loss
        ')
            ->first();

        $this->grossProfit = round($profitFactorStats->gross_profit ?? 0, 2);
        $this->grossLoss = round($profitFactorStats->gross_loss ?? 0, 2);

        // Profit Factor = Gross Profit / Gross Loss
        $this->profitFactor = $this->grossLoss > 0 ?
            round($this->grossProfit / $this->grossLoss, 4) : 0;  // 4 decimales como 0.7892

        //  Primer trade
        $firstTrade = $this->selectedAccount->trades()
            ->whereNotNull('entry_time')
            ->orderBy('entry_time', 'asc')
            ->select('entry_time')->first();

        $this->firstTradeDate = $firstTrade ? Carbon::parse($firstTrade->entry_time) : null;


        // PNL Total y % de beneficio
        // 1. Cálculo de Beneficio/Pérdida Absoluto
        $initial = (float) $this->selectedAccount->initial_balance;
        $current = (float) $this->selectedAccount->current_balance;

        // 1. Cálculo de Beneficio/Pérdida Absoluto
        $this->totalProfitLoss = $current - $initial;

        // 2. Cálculo de Porcentaje
        // Fórmula: ((Actual - Inicial) / Inicial) * 100
        if ($initial > 0) {
            $this->profitPercentage = ($this->totalProfitLoss / $initial) * 100;
        } else {
            $this->profitPercentage = 0;
        }
    }

    private function formatAge($days)
    {
        if ($days >= 365) {
            $years = floor($days / 365);
            return $years . 'a ' . ($days % 365) . 'd';
        }
        if ($days >= 30) {
            $months = floor($days / 30);
            return $months . 'm ' . ($days % 30) . 'd';
        }
        return $days . ' días';
    }

    private function loadBalanceChart()
    {
        $trades = $this->selectedAccount->trades()
            ->when($this->selectedTimeframe !== 'all', function ($query) {
                $config = $this->timeframes[$this->selectedTimeframe];
                if (isset($config['minutes'])) {
                    $query->where('exit_time', '>=', now()->subMinutes($config['minutes']));
                } elseif (isset($config['hours'])) {
                    $query->where('exit_time', '>=', now()->subHours($config['hours']));
                } elseif (isset($config['days'])) {
                    $query->where('exit_time', '>=', now()->subDays($config['days']));
                }
            })
            ->orderBy('exit_time')
            ->get();

        $labels = ['Inicio'];
        $balanceData = [$this->selectedAccount->initial_balance];
        $currentBalance = $this->selectedAccount->initial_balance;

        // ← PUNTOS FANTASMA si no hay trades
        if ($trades->isEmpty()) {
            $format = $this->timeframes[$this->selectedTimeframe]['format'] ?? 'H:i';
            $finalBalance = $this->selectedAccount->initial_balance; // Mismo balance

            if ($this->selectedTimeframe === '1h') {
                $labels = array_merge($labels, [
                    now()->subMinutes(40)->format($format),
                    now()->subMinutes(20)->format($format),
                    now()->format($format)
                ]);
                $balanceData = [$finalBalance, $finalBalance, $finalBalance, $finalBalance];
            } elseif ($this->selectedTimeframe === '24h') {
                $labels = array_merge($labels, [
                    now()->subHours(16)->format($format),
                    now()->subHours(8)->format($format),
                    now()->format($format)
                ]);
                $balanceData = [$finalBalance, $finalBalance, $finalBalance, $finalBalance];
            } elseif ($this->selectedTimeframe === '7d') {
                $labels = array_merge($labels, [
                    now()->subDays(4)->format($format),
                    now()->subDays(2)->format($format),
                    now()->format($format)
                ]);
                $balanceData = [$finalBalance, $finalBalance, $finalBalance, $finalBalance];
            } else { // 'all'
                $labels[] = 'Sin trades';
                $balanceData[] = $finalBalance;
            }
        } else {
            // ← TU LÓGICA ORIGINAL (funciona perfecto)
            $dailyBalances = [];
            foreach ($trades as $trade) {
                $dateKey = $this->selectedTimeframe === 'all'
                    ? $trade->exit_time->format('d M Y')
                    : $trade->exit_time->format($this->timeframes[$this->selectedTimeframe]['format'] ?? 'd/m H:i');
                $dailyBalances[$dateKey] = ($dailyBalances[$dateKey] ?? 0) + $trade->pnl;
            }

            foreach ($dailyBalances as $date => $pnlDay) {
                $currentBalance += $pnlDay;
                $labels[] = $date;
                $balanceData[] = $currentBalance;
            }
        }

        $this->balanceChartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $trades->isEmpty() ? 'Sin trades' : 'Balance',
                    'data' => $balanceData,
                    'borderColor' => $trades->isEmpty() ? 'rgb(156, 163, 175)' : 'rgb(16, 185, 129)',
                    'backgroundColor' => $trades->isEmpty() ? 'rgba(156, 163, 175, 0.1)' : 'rgba(16, 185, 129, 0.3)',
                    'fill' => 'origin',
                    'tension' => 0.4,
                    'pointBackgroundColor' => $trades->isEmpty() ? 'rgb(156, 163, 175)' : 'rgb(16, 185, 129)'
                ],
            ]
        ];
    }

    public function showAlert($type, $message)
    {
        $this->dispatch('show-alert', [
            'type' => $type,
            'message' => $message
        ]);
    }

    public function insertAccount()
    {

        $level = ProgramLevel::with('program')->findOrFail($this->form->programLevelID);

        // 3. Determinar el Objetivo Inicial (Fase 1 o Directo a Live)
        // Esto depende de si el programa tiene fases o es "Instant Funded"
        $initialPhase = 1; // Por defecto empezamos en Fase 1


        if ($level->program->step_count === 0) {
            // Si el programa es de 0 pasos (Instant Funded), empezamos en Fase 0 (Live)
            $initialPhase = 0;
        }

        // Buscamos el objetivo correspondiente en la BD
        $objective = $level->objectives()
            ->where('phase_number', $initialPhase)
            ->first();

        if (!$objective) {
            // Seguridad por si el Seeder falló o faltan datos
            throw new \Exception("No se encontraron las reglas (Objetivos) para la Fase $initialPhase de este nivel.");
        }

        // 4. Crear la cuenta
        $account = Account::create([
            'user_id' => Auth::user()->id,
            'name' => $this->form->name, // El nombre que puso el usuario
            'type' => 'prop_firm',
            'status' => 'active',

            // Vinculaciones Clave
            'program_level_id' => $level->id,
            'program_objective_id' => $objective->id, // <--- Aquí guardamos las reglas actuales

            // Datos Técnicos (MT5)
            'platform' => $this->form->platformBroker ?? 'mt5',
            'mt5_login' => $this->form->loginPlatform,
            'mt5_password' => encrypt($this->form->passwordPlatform),
            'mt5_server' => $level->program->propFirm->server, // Viene del JS automático
            'broker_name' => $level->program->propFirm->name, // Opcional, o sacarlo por relación

            // Datos Financieros (Vienen del Nivel, no del usuario)
            'currency' => $level->currency,
            'initial_balance' => $level->size,
            'current_balance' => $level->size, // Al principio son iguales

            // Fechas
        ]);

        $this->form->reset();

        $user = Auth::user();
        $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->orderBy('name')->get();
        $this->selectedAccount = $account; // ← Array[0]
        $this->dispatch('account-created');
        $this->updateData();
    }


    // public function onChangeSelectPropFirm()
    // {
    //     if ($this->form->selectedPropFirmID == null) {
    //         $this->programsFirms = [];
    //     } else {
    //         $propFirm = PropFirm::find($this->form->selectedPropFirmID);
    //         $this->programsFirms = $propFirm->programs;
    //     }
    // }

    // public function onChangeSelectProgram()
    // {
    //     // 1. Obtenemos los tamaños ÚNICOS y ordenados
    //     // Usamos DB query directa para ser más eficientes que cargar todos los modelos
    //     $this->sizes = ProgramLevel::where('program_id', $this->form->selectedProgramID)
    //         ->select('size')
    //         ->distinct() // <--- MAGIA: Evita duplicados (100k USD y 100k EUR cuentan como uno)
    //         ->orderBy('size', 'asc')
    //         ->pluck('size')
    //         ->toArray();
    // }

    // public function onChangeSelectBalance()
    // {
    //     $this->currencies = ProgramLevel::where('program_id', $this->form->selectedProgramID)
    //         ->where('size', $this->form->size)->pluck('currency', 'id');  // El tamaño que acaba de elegir
    // }





    public function render()
    {
        return view('livewire.account-page');
    }
}
