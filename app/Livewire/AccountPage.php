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
use Livewire\WithPagination;

class AccountPage extends Component
{

    use WithPagination;

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

    // Propiedad Computada para los trades
    public function getHistoryTradesProperty()
    {
        return Trade::query()
            ->where('account_id', $this->selectedAccountId)
            ->with('tradeAsset') // Carga impaciente para optimizar
            ->orderBy('exit_time', 'desc') // Orden por fecha de salida
            ->paginate(10); // Paginación de 15 elementos
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
        $this->resetPage();
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

            $this->calculateStatistics();
            $this->loadBalanceChart();
        }
        $this->selectedAccountId = $this->selectedAccount?->id; // <--- ESTO ES CLAVE
        $this->dispatch('account-change', timeframe: 'all');
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
        // 1. Limpiamos los decimales (6.62 -> 6)
        $days = (int) floor($days);

        // 2. Lógica de Años
        if ($days >= 365) {
            $years = floor($days / 365);
            $remainingDays = $days % 365;
            return $years . 'a ' . $remainingDays . 'd';
        }

        // 3. Lógica de Meses (aprox 30 días)
        if ($days >= 30) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            return $months . 'm ' . $remainingDays . 'd';
        }

        // 4. Días sueltos
        return $days . ' días';
    }




    private function loadBalanceChart()
    {
        // 1. Configurar Fecha de Corte (Igual que antes)
        $cutoffDate = null;
        if ($this->selectedTimeframe !== 'all') {
            $config = $this->timeframes[$this->selectedTimeframe];
            if (isset($config['minutes'])) $cutoffDate = now()->subMinutes($config['minutes']);
            elseif (isset($config['hours'])) $cutoffDate = now()->subHours($config['hours']);
            elseif (isset($config['days'])) $cutoffDate = now()->subDays($config['days']);
        }

        // 2. Calcular Balance Inicial (Igual que antes)
        if ($cutoffDate) {
            $priorPnl = $this->selectedAccount->trades()
                ->where('exit_time', '<', $cutoffDate)
                ->sum('pnl');

            $startBalance = $this->selectedAccount->initial_balance + $priorPnl;
            $startLabel = $cutoffDate->format('H:i');
        } else {
            $startBalance = $this->selectedAccount->initial_balance;
            $startLabel = 'Inicio';
        }

        // 3. Obtener Trades
        $trades = $this->selectedAccount->trades()
            ->when($cutoffDate, fn($q) => $q->where('exit_time', '>=', $cutoffDate))
            ->orderBy('exit_time', 'asc')
            ->get();

        // 4. Inicializar Arrays para las 3 Líneas
        $labels = [$startLabel];
        $balanceData = [(float) round($startBalance, 2)];
        $minEquityData = [(float) round($startBalance, 2)]; // MAE (Suelo)
        $maxEquityData = [(float) round($startBalance, 2)]; // MFE (Techo)

        $runningBalance = $startBalance;

        if ($trades->isNotEmpty()) {
            // Agrupar trades según timeframe
            $groupedTrades = $trades->groupBy(function ($trade) {
                return match ($this->selectedTimeframe) {
                    '1h' => $trade->exit_time->format('H:i'),
                    '24h' => $trade->exit_time->format('H:00'),
                    '7d' => $trade->exit_time->format('d/m H:00'),
                    default => $trade->exit_time->format('d M'),
                };
            });

            foreach ($groupedTrades as $timeLabel => $group) {

                $intervalPnl = $group->sum('pnl');

                // Inicializamos los extremos con el balance actual antes de cerrar este grupo
                $currentMinEquity = $runningBalance;
                $currentMaxEquity = $runningBalance;

                foreach ($group as $trade) {
                    $priceDiff = abs($trade->exit_price - $trade->entry_price);

                    // Solo calculamos si hubo movimiento y tenemos datos MAE/MFE
                    if ($priceDiff > 0 && abs($trade->pnl) > 0) {

                        // MATEMÁTICAS: Calculamos cuánto vale 1 punto de precio en dinero real
                        // Fórmula: PnL Total / Distancia Recorrida
                        $valuePerPoint = abs($trade->pnl) / $priceDiff;

                        // --- LÓGICA MAE (Riesgo / Miedo) ---
                        // ¿Cuánto dinero llegué a ir perdiendo?
                        if ($trade->mae_price) {
                            $distToMae = abs($trade->entry_price - $trade->mae_price);
                            $floatingLoss = -1 * ($distToMae * $valuePerPoint);

                            // El balance más bajo posible fue mi saldo actual + la pérdida flotante máxima
                            $potentialLow = $runningBalance + $floatingLoss;
                            if ($potentialLow < $currentMinEquity) $currentMinEquity = $potentialLow;
                        }

                        // --- LÓGICA MFE (Potencial / Avaricia) ---
                        // ¿Cuánto dinero llegué a ir ganando?
                        if ($trade->mfe_price) {
                            $distToMfe = abs($trade->entry_price - $trade->mfe_price);
                            $floatingProfit = $distToMfe * $valuePerPoint;

                            // El balance más alto posible fue mi saldo actual + la ganancia flotante máxima
                            $potentialHigh = $runningBalance + $floatingProfit;
                            if ($potentialHigh > $currentMaxEquity) $currentMaxEquity = $potentialHigh;
                        }
                    }
                }

                // Avanzamos el balance "Real" (Línea Verde)
                $runningBalance += $intervalPnl;

                // Guardamos los puntos en el gráfico
                $labels[] = $timeLabel;
                $balanceData[] = round($runningBalance, 2);

                // Guardamos los extremos (Equity)
                // Usamos min/max para asegurar que la línea roja no supere a la verde en caso de gaps raros
                // y que la línea azul no esté por debajo de la verde.
                $minEquityData[] = round(min($currentMinEquity, $runningBalance), 2);
                $maxEquityData[] = round(max($currentMaxEquity, $runningBalance), 2);
            }
        } else {
            // Línea plana si no hay trades
            $labels[] = now()->format('H:i');
            $balanceData[] = round($startBalance, 2);
            $minEquityData[] = round($startBalance, 2);
            $maxEquityData[] = round($startBalance, 2);
        }

        // 6. Empaquetar para ApexCharts
        $this->balanceChartData = [
            'categories' => $labels,
            'series' => [
                [
                    'name' => 'Max. Potencial (MFE)',
                    'data' => $maxEquityData
                ],
                [
                    'name' => 'Balance Real',
                    'data' => $balanceData
                ],
                [
                    'name' => 'Min. Riesgo (MAE)',
                    'data' => $minEquityData
                ]
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
            'sync' => $this->form->sync,

            // Fechas
        ]);

        $this->form->reset();

        $user = Auth::user();
        $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->orderBy('name')->get();
        $this->selectedAccount = $account; // ← Array[0]
        $this->dispatch('account-created');
        $this->updateData();
    }

    public function editAccount($id)
    {
        // 1. Buscamos la cuenta y sus relaciones
        $account = Account::with('programLevel.program.propFirm')->findOrFail($id);

        // 2. Rellenamos el Form Object
        $this->form->name = $account->name;
        $this->form->sync = $account->sync;
        $this->form->platformBroker = $account->platform;
        $this->form->loginPlatform = $account->mt5_login;
        $this->form->server = $account->mt5_server;
        // No enviamos la password por seguridad, si la deja vacía no se cambia
        $this->form->passwordPlatform = '';

        // 3. Recuperamos los IDs para los Selects en Cascada
        // Account -> Level -> Program -> Firm
        $level = $account->programLevel;

        $this->form->selectedPropFirmID = $level->program->prop_firm_id;
        $this->form->selectedProgramID = $level->program_id;
        $this->form->size = $level->size; // Ojo, asegúrate de que 'size' en el select sea el valor numérico
        $this->form->programLevelID = $level->id;

        // 4. Enviamos evento al Frontend para abrir modal y llenar Alpine
        $this->dispatch('open-modal-edit', [
            'data' => [
                'accountId' => $account->id,
                'name' => $this->form->name,
                'firmId' => $this->form->selectedPropFirmID,
                'programId' => $this->form->selectedProgramID,
                'size' => $this->form->size,
                'levelId' => $this->form->programLevelID,
                'sync' => $this->form->sync,
                'platform' => $this->form->platformBroker,
                'login' => $this->form->loginPlatform,
                'server' => $this->form->server
            ]
        ]);
    }

    public function updateAccount($id)
    {
        // Lógica de validación y update...
        $account = Account::find($id);

        $level = ProgramLevel::with('program')->findOrFail($this->form->programLevelID);

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

        // ... update ...
        $account->update([
            'name' => $this->form->name,
            'program_level_id' => $level->id,
            'program_objective_id' => $objective->id,
            'platform' => $this->form->platformBroker ?? 'mt5',
            'mt5_login' => $this->form->loginPlatform,
            'mt5_server' => $level->program->propFirm->server, // Viene del JS automático
            'broker_name' => $level->program->propFirm->name, // Opcional, o sacarlo por relación

            'currency' => $level->currency,
            'initial_balance' => $level->size,
            'current_balance' => $level->size, // Al principio son iguales
            'sync' => $this->form->sync,

            // ... resto de campos ...
        ]);
        $account->save();


        if ($this->form->passwordPlatform) {
            $account->mt5_password = encrypt($this->form->passwordPlatform);
            $account->save();
        }

        $this->dispatch('account-updated', timeframe: 'all'); // Cerrar modal y refrescar
        $this->updateData();
    }

    public function deleteAccount($id)
    {
        // 1. Seguridad: Verificar que sea del usuario
        $account = Account::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$account) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => 'Cuenta no encontrada.']);
            return;
        }

        // 2. Borrar (Soft Delete si lo tienes configurado, o Delete normal)
        $account->delete();

        // 3. Lógica Post-Borrado
        // Si la cuenta borrada era la seleccionada, cambiamos a la primera disponible
        if ($this->selectedAccount && $this->selectedAccount->id == $id) {
            $this->selectedAccount = Account::where('status', '!=', 'burned')
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->first();

            $this->selectedAccountId = $this->selectedAccount?->id;
        }

        // 4. Refrescar datos y avisar
        $user = Auth::user();
        $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->orderBy('name')->get();
        $this->selectedAccount = $this->accounts->first(); // ← Array[0]
        $this->selectedAccountId = $this->selectedAccount?->id; // <--- ESTO ES CLAVE

        $this->updateData(); // Recalcular gráficas con la nueva cuenta seleccionada
        $this->dispatch('account-updated', timeframe: 'all'); // Recargar tabla y charts
        $this->dispatch('show-alert', ['type' => 'success', 'message' => 'Cuenta eliminada correctamente.']);
    }







    public function render()
    {
        return view('livewire.account-page');
    }
}
