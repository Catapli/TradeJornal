<?php

namespace App\Livewire;

use App\MoneyHelper;
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
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Actions\Accounts\GenerateBalanceChartData;
use Illuminate\Support\Facades\DB;

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
    public $syncStartTime = null; // 👇 Nueva propiedad para guardar cuándo empezamos
    public $selectedTimeframe = 'all'; // ← NUEVO
    public AccountForm $form;
    public $propFirmsData = [];

    public $editingAccountId = null;

    // Campos del plan
    public $rules_max_loss_percent;
    public $rules_profit_target_percent;
    public $rules_max_trades;
    public $rules_start_time;
    public $rules_end_time;

    public $currency;

    public $lastKnownSync = null;
    public $syncCheckEnabled = true; // Por si quieres desactivarlo


    public $timeframes = [  // ← ASEGÚRATE de tener esto
        '1h' => ['minutes' => 60, 'format' => 'H:i'],     // "14:30"
        '24h' => ['hours' => 24, 'format' => 'd H:i'],    // "08 14:30" 
        '7d' => ['days' => 7, 'format' => 'd M (D)'],   // "08 Jan (Dom)" ← ÚNICO
        'all' => ['all' => true, 'format' => 'd MMM yy']  // "08 Jan 26"
    ];

    protected $listeners = [
        'echo:account.{selectedAccountId},sync.completed' => 'handleSyncCompleted'
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

        $this->changeCurrency();

        $this->updateData();
        $this->lastKnownSync = $this->selectedAccount?->last_sync;
    }

    /**
     * Carga las reglas de una cuenta y dispara evento para que Alpine abra el modal
     */
    public function openRules($accountId)
    {
        try {
            $account = Account::with('tradingPlan')->findOrFail($accountId);
            $plan = $account->tradingPlan;

            // Cargar datos en propiedades públicas (solo lectura/escritura)
            $this->editingAccountId = $accountId;
            $this->rules_max_loss_percent = $plan?->max_daily_loss_percent;
            $this->rules_profit_target_percent = $plan?->daily_profit_target_percent;
            $this->rules_max_trades = $plan?->max_daily_trades;
            $this->rules_start_time = $plan?->start_time;
            $this->rules_end_time = $plan?->end_time;

            // Alpine abre el modal (no Livewire)
            $this->dispatch('open-rules-modal');
        } catch (Exception $e) {
            $this->logError($e, 'openRules', 'AccountPage', "Error abriendo reglas para cuenta {$accountId}");

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al cargar las reglas de la cuenta.'
            ]);
        }
    }


    public function handleSyncCompleted($data)
    {
        try {
            Log::info("🔔 Evento sync.completed recibido", $data);

            // Invalidar caché (por si acaso)
            Cache::forget("account_stats_{$this->selectedAccountId}");
            Cache::forget("balance_chart_{$this->selectedAccountId}_all");

            // Refrescar datos
            $this->selectedAccount->refresh(); // Recargar desde BD
            $this->updateData();

            // Notificar al usuario
            $this->dispatch('show-alert', [
                'type' => 'success',
                'message' => "Sincronización completada: {$data['trades_inserted']} operaciones actualizadas."
            ]);
        } catch (\Exception $e) {
            Log::error("Error manejando sync.completed: " . $e->getMessage());
        }
    }


    /**
     * Guarda las reglas en BD y dispara evento de éxito
     * Alpine cierra el modal y resetea las variables
     */
    public function saveRules()
    {
        try {
            $account = Account::findOrFail($this->editingAccountId);

            $data = [
                'max_daily_loss_percent' => $this->rules_max_loss_percent === '' ? null : $this->rules_max_loss_percent,
                'daily_profit_target_percent' => $this->rules_profit_target_percent === '' ? null : $this->rules_profit_target_percent,
                'max_daily_trades' => $this->rules_max_trades === '' ? null : $this->rules_max_trades,
                'start_time' => $this->rules_start_time === '' ? null : $this->rules_start_time,
                'end_time' => $this->rules_end_time === '' ? null : $this->rules_end_time,
                'is_active' => true
            ];

            $account->tradingPlan()->updateOrCreate([], $data);

            // Disparar evento de éxito (Alpine cierra el modal)
            $this->dispatch('rules-saved');

            $this->dispatch('show-alert', [
                'type' => 'success',
                'message' => 'Plan de trading actualizado correctamente.'
            ]);
        } catch (Exception $e) {
            $this->logError($e, 'saveRules', 'AccountPage', "Error guardando reglas para cuenta {$this->editingAccountId}");

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al guardar las reglas.'
            ]);
        }
    }


    // Propiedad Computada para los trades
    public function getHistoryTradesProperty()
    {
        try {
            return Trade::query()
                ->where('account_id', $this->selectedAccountId)
                ->with('tradeAsset') // Carga impaciente para optimizar
                ->orderBy('exit_time', 'desc') // Orden por fecha de salida
                ->paginate(10); // Paginación de 15 elementos
        } catch (Exception $e) {
            $this->logError($e, 'getHistoryTrades', 'AccountPage', "Error al obtener trades de cuenta {$this->selectedAccountId}");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al cargar los trades de la cuenta. Mostrando la primera disponible.'
            ]);
        }
    }



    private function changeCurrency()
    {
        try {
            $isoCode = $this->selectedAccount ? $this->selectedAccount->currency : 'USD';
            $this->currency = MoneyHelper::getSymbol($isoCode);
        } catch (Exception $e) {
            $this->logError($e, 'changeCurrency', 'AccountPage', "Error al cambiar moneda de cuenta {$this->selectedAccountId}");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al cargar la moneda de la cuenta. Mostrando la primera disponible.'
            ]);
        }
    }


    //* Para modificar el timeframe del grafico
    public function setTimeframe($timeframe) // ← NUEVO MÉTODO
    {
        try {
            $this->selectedTimeframe = $timeframe;
            $this->loadBalanceChart(); // ← Recarga gráfico filtrado
            $this->dispatch('timeframe-updated', timeframe: $timeframe);
        } catch (Exception $e) {
            $this->logError($e, 'setTimeframe', 'AccountPage', "Error al cambiar timeframe a {$timeframe}");

            // Fallback seguro
            $this->selectedTimeframe = 'all';
            $this->loadBalanceChart();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al cargar el timeframe. Mostrando todos los datos.'
            ]);
        }
    }

    /**
     * Verifica si hubo una sincronización reciente y actualiza datos
     * Se ejecuta cada 5 segundos desde el frontend
     */
    public function checkSyncStatus()
    {
        try {
            // Salir si no hay cuenta seleccionada
            if (!$this->selectedAccount) {
                return;
            }

            // Salir si el polling está deshabilitado
            if (!$this->syncCheckEnabled) {
                return;
            }

            // Obtener el timestamp actual de last_sync
            $currentSync = $this->selectedAccount->last_sync;

            // Si no hay sincronización registrada, salir
            if (!$currentSync) {
                return;
            }

            // Verificar si last_sync cambió desde la última verificación
            if ($currentSync != $this->lastKnownSync) {

                // Solo actualizar si la sincronización fue reciente (últimos 5 minutos)
                if ($currentSync->greaterThan(now()->subMinutes(5))) {

                    Log::info("🔄 Sincronización detectada para cuenta {$this->selectedAccount->id}");

                    // ✅ INVALIDAR CACHÉ
                    Cache::forget("account_stats_{$this->selectedAccount->id}");
                    Cache::forget("balance_chart_{$this->selectedAccount->id}_all");
                    Cache::forget("balance_chart_{$this->selectedAccount->id}_1h");
                    Cache::forget("balance_chart_{$this->selectedAccount->id}_24h");
                    Cache::forget("balance_chart_{$this->selectedAccount->id}_7d");

                    // ✅ REFRESCAR MODELO DESDE BD
                    $this->selectedAccount->refresh();

                    // ✅ RECALCULAR TODO
                    $this->updateData();

                    // ✅ GUARDAR TIMESTAMP PARA NO REPETIR
                    $this->lastKnownSync = $currentSync;

                    // ✅ NOTIFICAR AL USUARIO
                    $this->dispatch('show-alert', [
                        'type' => 'success',
                        'message' => 'Nuevas operaciones detectadas. Datos actualizados automáticamente.'
                    ]);

                    Log::info("✅ Datos actualizados para cuenta {$this->selectedAccount->id}");
                }
            }
        } catch (Exception $e) {
            Log::error('checkSyncStatus ERROR:', [
                'message' => $e->getMessage(),
                'account_id' => $this->selectedAccount?->id
            ]);
            // No mostramos error al usuario para no interrumpir la UX
        }
    }




    public function changeAccount($accountId)
    {
        try {
            $this->selectedAccount = $this->accounts->firstWhere('id', $accountId);
            // ✅ RESETEAR TIMESTAMP AL CAMBIAR CUENTA
            $this->lastKnownSync = $this->selectedAccount?->last_sync;
            $this->changeCurrency();
            $this->updateData();
            $this->resetPage();
            $this->dispatch('timeframe-updated', timeframe: 'all');
        } catch (Exception $e) {
            $this->logError($e, 'changeAccount', 'AccountPage', "Error al cambiar a cuenta {$accountId}");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al cargar la cuenta. Mostrando la primera disponible.'
            ]);
        }
    }



    /**
     * Actualiza todos los datos de la cuenta seleccionada
     * Con DB Transaction y Caché
     */
    private function updateData()
    {
        try {
            if (!$this->selectedAccount) {
                return;
            }

            // ========================================
            // 1. ACTUALIZAR BALANCE TEÓRICO (DB Transaction)
            // ========================================
            DB::transaction(function () {
                $this->totalPnl = $this->selectedAccount->trades()->sum('pnl');
                $this->initialBalance = $this->selectedAccount->initial_balance;
                $theoreticalBalance = $this->initialBalance + $this->totalPnl;

                // Solo actualizar si no hay sincronización activa
                if (is_null($this->selectedAccount->last_sync)) {
                    if ($this->selectedAccount->current_balance != $theoreticalBalance) {
                        $this->selectedAccount->update([
                            'current_balance' => $theoreticalBalance
                        ]);
                    }
                }
            });

            // ========================================
            // 2. CALCULAR ESTADÍSTICAS (Con Caché)
            // ========================================
            $this->calculateStatistics();

            // ========================================
            // 3. CARGAR GRÁFICO (Con Caché)
            // ========================================
            $this->loadBalanceChart();

            // ========================================
            // 4. BALANCE TOTAL Y % BENEFICIO
            // ========================================
            $initial = (float) $this->selectedAccount->initial_balance;
            $current = (float) $this->selectedAccount->current_balance;

            $this->totalProfitLoss = $current - $initial;

            if ($initial > 0) {
                $this->profitPercentage = ($this->totalProfitLoss / $initial) * 100;
            } else {
                $this->profitPercentage = 0;
            }

            // ========================================
            // 5. ACTUALIZAR ID Y DISPARAR EVENTO
            // ========================================
            $this->selectedAccountId = $this->selectedAccount->id;
            $this->dispatch('account-change', timeframe: 'all');
        } catch (Exception $e) {
            $this->logError($e, 'updateData', 'AccountPage', 'Error actualizando datos de cuenta');

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al cargar los datos de la cuenta. Por favor, recarga la página.'
            ]);
        }
    }

    private function calculateStatistics()
    {

        try {
            $action = new CalculateAccountStatistics();
            $stats = $action->execute($this->selectedAccount);

            // Mapear resultados a propiedades públicas
            $this->totalTrades = $stats['totalTrades'];
            $this->winRate = $stats['winRate'];
            $this->avgDurationMinutes = $stats['avgDurationMinutes'];
            $this->avgDurationFormatted = $stats['avgDurationFormatted'];
            $this->maxWin = $stats['maxWin'];
            $this->maxLoss = $stats['maxLoss'];
            $this->avgWinTrade = $stats['avgWinTrade'];
            $this->avgLossTrade = $stats['avgLossTrade'];
            $this->arr = $stats['arr'];
            $this->topAsset = $stats['topAsset'];
            $this->tradingDays = $stats['tradingDays'];
            $this->grossProfit = $stats['grossProfit'];
            $this->grossLoss = $stats['grossLoss'];
            $this->profitFactor = $stats['profitFactor'];
            $this->firstTradeDate = $stats['firstTradeDate'];
            $this->accountAgeDays = $stats['accountAgeDays'];
            $this->accountAgeFormatted = $stats['accountAgeFormatted'];
        } catch (Exception $e) {
            $this->logError($e, 'calculateStatistics', 'AccountPage', 'Error calculando estadísticas');

            // Valores seguros por defecto
            $this->totalTrades = 0;
            $this->winRate = 0;
            $this->topAsset = 'N/A';
        }
    }

    /**
     * Carga datos del gráfico usando Action Class (Optimizado con SQL)
     */
    private function loadBalanceChart()
    {
        try {
            $action = new GenerateBalanceChartData();
            $this->balanceChartData = $action->execute($this->selectedAccount, $this->selectedTimeframe);
        } catch (Exception $e) {
            $this->logError($e, 'loadBalanceChart', 'AccountPage', 'Error generando gráfico de balance');

            // Gráfico vacío seguro
            $this->balanceChartData = [
                'categories' => ['Inicio'],
                'series' => [
                    ['name' => 'Balance', 'data' => [0]]
                ]
            ];
        }
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

        try {
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
                throw new Exception(__('labels.objectives_not_found'));
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
                'mt5_server' => $level->program->propFirm->server, // Viene del JS automático
                'broker_name' => $level->program->propFirm->name, // Opcional, o sacarlo por relación

                // Datos Financieros (Vienen del Nivel, no del usuario)
                'currency' => $level->currency,
                'initial_balance' => $level->size,
                'current_balance' => $level->size, // Al principio son iguales
                'sync' => $this->form->sync,

                // Fechas
            ]);

            // ✅ INVALIDAR CACHÉ
            Cache::forget("account_stats_{$account->id}");
            Cache::forget("balance_chart_{$account->id}_all");
            Cache::forget("balance_chart_{$account->id}_{$this->selectedTimeframe}");

            $this->form->reset();

            $user = Auth::user();
            $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->orderBy('name')->get();
            $this->selectedAccount = $account; // ← Array[0]
            $this->changeCurrency();
            $this->dispatch('account-created');
            $this->updateData();
        } catch (Exception $e) {
            $this->logError($e, 'insertAccount', 'AccountPage', "Error al insertar cuenta");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al crear una nueva cuenta. Mostrando la primera disponible.'
            ]);
        }
    }

    public function editAccount($id)
    {

        try {
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
        } catch (Exception $e) {
            $this->logError($e, 'editAccount', 'AccountPage', "Error al editar cuenta {$id}");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al editar la cuenta. Mostrando la primera disponible.'
            ]);
        }
    }

    public function updateAccount($id)
    {

        try {
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
                throw new Exception(__('labels.objectives_not_found'));
            }

            // Calcular el nuevo current_balance basado en el cambio de initial_balance
            $oldInitialBalance = $account->initial_balance;
            $newInitialBalance = $level->size;
            $balanceDifference = $newInitialBalance - $oldInitialBalance;

            // Ajustar current_balance proporcionalmente
            $newCurrentBalance = $account->current_balance + $balanceDifference;

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
                'current_balance' => $newCurrentBalance, // ✅ Ajustado, no reseteado
                'sync' => $this->form->sync,

                // ... resto de campos ...
            ]);
            $account->save();


            // if ($this->form->passwordPlatform) {
            //     $account->mt5_password = encrypt($this->form->passwordPlatform);
            //     $account->save();
            // }

            // ✅ INVALIDAR CACHÉ
            Cache::forget("account_stats_{$account->id}");
            Cache::forget("balance_chart_{$account->id}_all");
            Cache::forget("balance_chart_{$account->id}_1h");
            Cache::forget("balance_chart_{$account->id}_24h");
            Cache::forget("balance_chart_{$account->id}_7d");

            $user = Auth::user();
            $this->accounts = Account::where('status', '!=', 'burned')->where('user_id', $user->id)->orderBy('name')->get();
            $this->selectedAccount = $account; // ← Array[0]

            $this->dispatch('account-updated', timeframe: 'all'); // Cerrar modal y refrescar
            $this->updateData();
        } catch (Exception $e) {
            $this->logError($e, 'updateAccount', 'AccountPage', "Error al actualizar cuenta {$id}");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al actualizar la cuenta. Mostrando la primera disponible.'
            ]);
        }
    }

    public function deleteAccount($id)
    {
        try {
            // 1. Seguridad: Verificar que sea del usuario
            $account = Account::where('id', $id)->where('user_id', Auth::id())->first();

            if (!$account) {
                $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.account_not_found')]);
                return;
            }

            // ✅ INVALIDAR CACHÉ ANTES DE BORRAR
            Cache::forget("account_stats_{$account->id}");
            Cache::forget("balance_chart_{$account->id}_all");
            Cache::forget("balance_chart_{$account->id}_1h");
            Cache::forget("balance_chart_{$account->id}_24h");
            Cache::forget("balance_chart_{$account->id}_7d");

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
            $this->changeCurrency();

            $this->updateData(); // Recalcular gráficas con la nueva cuenta seleccionada
            $this->dispatch('account-updated', timeframe: 'all'); // Recargar tabla y charts
            $this->dispatch('show-alert', ['type' => 'success', 'message' => __('labels.account_deleted')]);
        } catch (Exception $e) {
            $this->logError($e, 'deleteAccount', 'AccountPage', "Error al borrar cuenta {$id}");

            // Fallback seguro
            $this->selectedAccount = $this->accounts->first();
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Error al borrar la cuenta. Mostrando la primera disponible.'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.account-page');
    }
}
