<?php

namespace App\Livewire;

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Actions\Accounts\GenerateBalanceChartData;
use App\Concerns\AuthorizesOwnership;
use App\Livewire\Forms\AccountForm;
use App\LogActions;
use App\Models\Account;
use App\Models\ProgramLevel;
use App\Models\PropFirm;
use App\Models\Trade;
use App\MoneyHelper;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AccountPage extends Component
{
    use AuthorizesOwnership;
    use LogActions;
    use WithPagination;

    // ? Estado de selección: ÚNICA propiedad persistida en el snapshot de Livewire.
    //   accounts / selectedAccount / propFirmsData son computed (no viajan en el payload).
    public $showCreateModal = false;

    public $showEditModal = false;

    public $selectedAccountId;

    // ? Datos para el gráfico de balance
    public $balanceChartData = [
        'labels' => [],
        'datasets' => [],
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

    /**
     * Cuentas activas (no quemadas) del usuario. Computed: se resuelve por
     * request y NO se serializa en el snapshot de Livewire.
     */
    #[Computed]
    public function accounts()
    {
        return Account::where('user_id', Auth::id())
            ->where('status', '!=', 'burned')
            // El aviso de archivado dice cuántas operaciones se apartan: sin el
            // número, «se archiva el histórico» no significa nada.
            ->withCount('trades')
            ->orderBy('name')
            ->get();
    }

    /**
     * Cuenta seleccionada. Deriva de selectedAccountId con fallback a la primera.
     */
    #[Computed]
    public function selectedAccount()
    {
        return $this->accounts->firstWhere('id', $this->selectedAccountId)
            ?? $this->accounts->first();
    }

    /**
     * Jerarquía PropFirm → programas → niveles para los selects en cascada (JS).
     * Cacheada: cambia rara vez y solo se usa al abrir los modales.
     */
    #[Computed]
    public function propFirmsData()
    {
        return Cache::remember(PropFirm::CACHE_KEY, now()->addHours(6), function () {
            return PropFirm::with(['programs.levels' => function ($query) {
                $query->select('id', 'program_id', 'size', 'currency');
            }])
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Invalida la caché de las computed de cuentas tras una mutación.
     */
    private function loadAccounts(): void
    {
        unset($this->accounts, $this->selectedAccount);
    }

    public function mount()
    {
        $this->selectedAccountId = $this->accounts->first()?->id; // ← cuenta por defecto
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
            $this->authorize('view', $account);
            $plan = $account->tradingPlan;
            $this->editingAccountId = $accountId;
            $this->rules_max_loss_percent = $plan?->max_daily_loss_percent;
            $this->rules_profit_target_percent = $plan?->daily_profit_target_percent;
            $this->rules_max_trades = $plan?->max_daily_trades;
            $this->rules_start_time = $plan?->start_time;
            $this->rules_end_time = $plan?->end_time;

            // Alpine abre el modal (no Livewire)
            $this->dispatch('open-rules-modal');
        } catch (AuthorizationException $e) {
            // Un fallo de permisos no es un error de la aplicacion: que suba y
            // responda 403 en vez de acabar en el log como si algo se hubiera roto.
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'openRules', 'AccountPage', "Error abriendo reglas para cuenta {$accountId}");

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_loading_rules'),
            ]);
        }
    }

    /**
     * Guarda las reglas en BD y dispara evento de éxito
     * Alpine cierra el modal y resetea las variables
     */
    public function saveRules()
    {
        try {
            // `editingAccountId` es una propiedad pública: la manda el cliente, así que
            // sin findOwned se podían reescribir los límites de riesgo de cualquier cuenta.
            $account = $this->findOwned(Account::class, $this->editingAccountId, 'update');

            $data = [
                'max_daily_loss_percent' => $this->rules_max_loss_percent === '' ? null : $this->rules_max_loss_percent,
                'daily_profit_target_percent' => $this->rules_profit_target_percent === '' ? null : $this->rules_profit_target_percent,
                'max_daily_trades' => $this->rules_max_trades === '' ? null : $this->rules_max_trades,
                'start_time' => $this->rules_start_time === '' ? null : $this->rules_start_time,
                'end_time' => $this->rules_end_time === '' ? null : $this->rules_end_time,
                'is_active' => true,
            ];

            $account->tradingPlan()->updateOrCreate([], $data);

            // Disparar evento de éxito (Alpine cierra el modal)
            $this->dispatch('rules-saved');

            $this->dispatch('show-alert', [
                'type' => 'success',
                'message' => __('labels.trading_plan_ok'),
            ]);
        } catch (AuthorizationException $e) {
            // Un fallo de permisos no es un error de la aplicacion: que suba y
            // responda 403 en vez de acabar en el log como si algo se hubiera roto.
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'saveRules', 'AccountPage', "Error guardando reglas para cuenta {$this->editingAccountId}");

            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_saving_rules'),
            ]);
        }
    }

    // Propiedad Computada para los trades
    public function getHistoryTradesProperty()
    {
        try {
            // Igual que en checkSyncStatus/openTradeDetail: el id sale del computed
            // (filtrado por Auth::id()). Con `selectedAccountId` a pelo se podía pintar
            // la tabla de trades completa de una cuenta ajena.
            return Trade::query()
                ->where('account_id', $this->selectedAccount?->id)
                ->with('tradeAsset') // Carga impaciente para optimizar
                ->orderBy('exit_time', 'desc') // Orden por fecha de salida
                ->paginate(10); // Paginación de 15 elementos
        } catch (Exception $e) {
            $this->logError($e, 'getHistoryTrades', 'AccountPage', "Error al obtener trades de cuenta {$this->selectedAccountId}");

            // Fallback seguro
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_loading_trades'),
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
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_loading_currency'),
            ]);
        }
    }

    // * Para modificar el timeframe del grafico
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
                'message' => __('labels.error_loading_timeframe'),
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

            // Leer last_sync DIRECTAMENTE de la BD (no del modelo serializado,
            // que entre polls no refleja los cambios escritos por el job de sync).
            // Se usa el id del computed, no `selectedAccountId` a pelo: el computed sale
            // de `accounts`, que ya filtra por Auth::id(). Con la propiedad pública se
            // podía sondear el `last_sync` de una cuenta ajena.
            $currentSync = Account::whereKey($this->selectedAccount->id)->value('last_sync');

            // Si no hay sincronización registrada, salir
            if (!$currentSync) {
                return;
            }

            // Verificar si last_sync cambió desde la última verificación
            if ($currentSync != $this->lastKnownSync) {

                // Solo actualizar si la sincronización fue reciente (últimos 5 minutos)
                if ($currentSync->greaterThan(now()->subMinutes(5))) {

                    Log::info("🔄 Sincronización detectada para cuenta {$this->selectedAccount->id}");

                    // ✅ INVALIDAR COMPUTED → re-consulta la cuenta fresca desde BD
                    $this->loadAccounts();

                    // ✅ RECALCULAR TODO (forzando recálculo: hay trades nuevos)
                    $this->updateData(force: true);

                    // ✅ GUARDAR TIMESTAMP PARA NO REPETIR
                    $this->lastKnownSync = $currentSync;

                    // ✅ NOTIFICAR AL USUARIO
                    $this->dispatch('show-alert', [
                        'type' => 'success',
                        'message' => __('labels.new_operations_detected'),
                    ]);

                    Log::info("✅ Datos actualizados para cuenta {$this->selectedAccount->id}");
                }
            }
        } catch (Exception $e) {
            Log::error('checkSyncStatus ERROR:', [
                'message' => $e->getMessage(),
                'account_id' => $this->selectedAccount?->id,
            ]);
            // No mostramos error al usuario para no interrumpir la UX
        }
    }

    public function changeAccount($accountId)
    {
        try {
            $this->selectedAccountId = $accountId;
            unset($this->selectedAccount); // refresca el computed con el nuevo id
            // ✅ RESETEAR TIMESTAMP AL CAMBIAR CUENTA
            $this->lastKnownSync = $this->selectedAccount?->last_sync;
            $this->changeCurrency();
            $this->updateData();
            $this->resetPage();
            $this->dispatch('timeframe-updated', timeframe: 'all');
        } catch (Exception $e) {
            $this->logError($e, 'changeAccount', 'AccountPage', "Error al cambiar a cuenta {$accountId}");

            // Fallback seguro
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_loading_account'),
            ]);
        }
    }

    /**
     * Actualiza todos los datos de la cuenta seleccionada
     */
    private function updateData(bool $force = false)
    {
        try {
            if (!$this->selectedAccount) {
                return;
            }

            // ========================================
            // 1. CALCULAR ESTADÍSTICAS (Con Caché)
            //    Deja grossProfit / grossLoss en propiedades.
            // ========================================
            $this->calculateStatistics($force);

            // ========================================
            // 2. BALANCE TEÓRICO
            //    totalPnl = PnL realizado (cerrados) = grossProfit - grossLoss,
            //    derivado de las stats para no repetir un SUM(pnl) en BD.
            // ========================================
            $this->initialBalance = $this->selectedAccount->initial_balance;
            $this->totalPnl = $this->grossProfit - $this->grossLoss;
            $theoreticalBalance = $this->initialBalance + $this->totalPnl;

            // Solo persistir si no hay sincronización activa (el broker manda el balance real)
            if (is_null($this->selectedAccount->last_sync)
                && $this->selectedAccount->current_balance != $theoreticalBalance) {
                $this->selectedAccount->update([
                    'current_balance' => $theoreticalBalance,
                ]);
            }

            // ========================================
            // 3. CARGAR GRÁFICO (Con Caché)
            // ========================================
            $this->loadBalanceChart($force);

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
                'message' => __('labels.error_loading_data_account'),
            ]);
        }
    }

    private function calculateStatistics(bool $force = false)
    {

        try {
            $action = new CalculateAccountStatistics;
            $stats = $action->execute($this->selectedAccount, $force);

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
    private function loadBalanceChart(bool $force = false)
    {
        try {
            $action = new GenerateBalanceChartData;
            $this->balanceChartData = $action->execute($this->selectedAccount, $this->selectedTimeframe, $force);
        } catch (Exception $e) {
            $this->logError($e, 'loadBalanceChart', 'AccountPage', 'Error generando gráfico de balance');

            // Gráfico vacío seguro
            $this->balanceChartData = [
                'categories' => ['Inicio'],
                'series' => [
                    ['name' => 'Balance', 'data' => [0]],
                ],
            ];
        }
    }

    public function showAlert($type, $message)
    {
        $this->dispatch('show-alert', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    public function insertAccount()
    {

        try {

            // Límite de cuentas por plan (config/billing.php). Antes eran 3 a
            // fuego para todo el mundo, suscriptores incluidos, mientras la página
            // de precios anunciaba «Cuentas Ilimitadas»: quien pagaba chocaba con
            // el tope el primer día. El tope es ahora el momento de venta.
            $user = Auth::user();

            if (!$user->canCreateAccount()) {
                $this->dispatch('show-alert', [
                    'type' => 'error',
                    'message' => __('labels.max_accounts_reached', ['limit' => $user->accountLimit()]),
                ]);

                return;
            }

            // ✅ Validar mt5_login único (solo si viene informado)
            //
            // `mt5_login` es único en toda la tabla y el índice no entiende de
            // archivadas: sin `withTrashed()` la comprobación pasaría y el
            // INSERT reventaría contra la BD con un error que no dice nada.
            if ($this->form->loginPlatform) {
                $owner = Account::withTrashed()
                    ->where('mt5_login', $this->form->loginPlatform)
                    ->first();

                if ($owner) {
                    $this->dispatch('show-alert', [
                        'type' => 'error',
                        'message' => $owner->trashed()
                            ? __('labels.mt5_login_belongs_to_archived')
                            : __('labels.mt5_login_already_exists'),
                    ]);

                    return;
                }
            }

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
                'mt5_login' => $this->form->loginPlatform ?: null,
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

            $this->loadAccounts();
            $this->selectedAccountId = $account->id;
            $this->changeCurrency();
            $this->updateData();
            $this->dispatch('account-created');
            $this->dispatch('timeframe-updated', timeframe: 'all');
        } catch (Exception $e) {
            $this->logError($e, 'insertAccount', 'AccountPage', 'Error al insertar cuenta');

            // Fallback seguro
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_create_account'),
            ]);
        }
    }

    public function editAccount($id)
    {

        try {
            // 1. Buscamos la cuenta y sus relaciones
            $account = Account::with('programLevel.program.propFirm')->findOrFail($id);
            $this->authorize('view', $account);

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
                    'server' => $this->form->server,
                ],
            ]);
        } catch (AuthorizationException $e) {
            // Un fallo de permisos no es un error de la aplicacion: que suba y
            // responda 403 en vez de acabar en el log como si algo se hubiera roto.
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'editAccount', 'AccountPage', "Error al editar cuenta {$id}");

            // Fallback seguro
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_edit_account'),
            ]);
        }
    }

    /**
     * Abre el detalle de un trade con contexto de paginación
     * Los IDs de contexto son los trades de la página actual visible
     */
    public function openTradeDetail(int $tradeId): void
    {
        try {
            // IDs del contexto = página actual de la tabla visible.
            // Solo necesitamos los IDs: seleccionamos 'id' y omitimos el eager loading.
            // El id sale del computed (ya filtrado por Auth::id()), no de la propiedad
            // pública, que permitía listar los trades de una cuenta ajena.
            $account = $this->selectedAccount;
            if (!$account) {
                return;
            }

            $contextIds = Trade::query()
                ->where('account_id', $account->id)
                ->orderBy('exit_time', 'desc')
                ->paginate(10, ['id'], 'page', $this->getPage())
                ->getCollection()
                ->pluck('id')
                ->toArray();

            $this->dispatch(
                'open-trade-detail',
                tradeId: $tradeId,
                tradeIds: $contextIds
            );
        } catch (\Exception $e) {
            $this->logError($e, 'openTradeDetail', 'AccountPage', "Error abriendo trade: {$tradeId}");
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_opening_trade')]);
        }
    }

    public function updateAccount($id)
    {

        try {
            // Lógica de validación y update...
            $account = $this->findOwned(Account::class, $id, 'update');

            // ✅ Validar mt5_login único excluyendo la propia cuenta
            if ($this->form->loginPlatform) {
                $owner = Account::withTrashed()
                    ->where('mt5_login', $this->form->loginPlatform)
                    ->where('id', '!=', $id)
                    ->first();

                if ($owner) {
                    $this->dispatch('show-alert', [
                        'type' => 'error',
                        'message' => $owner->trashed()
                            ? __('labels.mt5_login_belongs_to_archived')
                            : __('labels.mt5_login_already_exists'),
                    ]);

                    return;
                }
            }

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
                'mt5_login' => $this->form->loginPlatform ?: null,
                'mt5_server' => $level->program->propFirm->server, // Viene del JS automático
                'broker_name' => $level->program->propFirm->name, // Opcional, o sacarlo por relación

                'currency' => $level->currency,
                'initial_balance' => $level->size,
                'current_balance' => $newCurrentBalance, // ✅ Ajustado, no reseteado
                'sync' => $this->form->sync,

                // ... resto de campos ...
            ]);
            $account->save();

            // El balance inicial pudo cambiar → invalidar cachés de stats y gráfico
            CalculateAccountStatistics::clearCache($account->id);
            GenerateBalanceChartData::clearCache($account->id);

            $this->loadAccounts();
            $this->selectedAccountId = $account->id;

            $this->updateData(force: true);
            $this->dispatch('account-updated', timeframe: 'all');
            $this->dispatch('timeframe-updated', timeframe: 'all');
        } catch (AuthorizationException $e) {
            // Un fallo de permisos no es un error de la aplicacion: que suba y
            // responda 403 en vez de acabar en el log como si algo se hubiera roto.
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'updateAccount', 'AccountPage', "Error al actualizar cuenta {$id}");

            // Fallback seguro
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_update_account'),
            ]);
        }
    }

    /**
     * Archiva la cuenta. No la destruye.
     *
     * `trades.account_id` es `cascade`: un `delete()` de verdad se lleva por
     * delante meses de histórico, y eso es justo lo que el usuario no quiere
     * perder el día que quema una cuenta. El borrado real existe aparte
     * (`deleteAccountPermanently`) y hay que pedirlo a propósito.
     */
    public function deleteAccount($id)
    {
        try {
            $account = $this->findOwned(Account::class, $id, 'delete');

            $account->delete();

            $this->afterAccountListChanged();
            $this->dispatch('show-alert', ['type' => 'success', 'message' => __('labels.account_archived')]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'deleteAccount', 'AccountPage', "Error al borrar cuenta {$id}");

            // Fallback seguro
            $this->selectedAccountId = $this->accounts->first()?->id;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('labels.error_delete_account'),
            ]);
        }
    }

    /**
     * Cuentas archivadas del usuario, con cuántas operaciones guarda cada una.
     * Es el dato que decide si restaurarla merece la pena, así que va en la
     * propia lista y no detrás de un clic.
     */
    #[Computed]
    public function archivedAccounts()
    {
        return Account::onlyTrashed()
            ->where('user_id', Auth::id())
            ->withCount('trades')
            ->orderByDesc('deleted_at')
            ->get();
    }

    /**
     * Devuelve una cuenta archivada a la circulación, con su histórico intacto.
     */
    public function restoreAccount($id)
    {
        try {
            $account = $this->findOwned(Account::class, $id, 'delete', withTrashed: true);

            $account->restore();

            $this->afterAccountListChanged();
            $this->selectedAccountId = $account->id;
            $this->dispatch('show-alert', ['type' => 'success', 'message' => __('labels.account_restored')]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'restoreAccount', 'AccountPage', "Error al restaurar cuenta {$id}");
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_restore_account')]);
        }
    }

    /**
     * El borrado de verdad, con el cascade de `trades` detrás.
     *
     * Solo se puede pedir sobre una cuenta ya archivada: así nadie destruye un
     * histórico de un clic desde la pantalla principal, hacen falta dos pasos
     * separados en el tiempo y la confirmación dice cuántas operaciones se van.
     */
    public function deleteAccountPermanently($id)
    {
        try {
            $account = $this->findOwned(Account::class, $id, 'delete', withTrashed: true);

            if (!$account->trashed()) {
                $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.archive_before_deleting')]);

                return;
            }

            $account->forceDelete();

            $this->afterAccountListChanged();
            $this->dispatch('show-alert', ['type' => 'success', 'message' => __('labels.account_deleted')]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logError($e, 'deleteAccountPermanently', 'AccountPage', "Error al borrar cuenta {$id}");
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('labels.error_delete_account')]);
        }
    }

    /**
     * Lo que hay que rehacer cuando la lista de cuentas cambia: los computed
     * caducan, la selección puede haberse quedado apuntando a una cuenta que ya
     * no está y las gráficas hablan de otra cuenta.
     */
    private function afterAccountListChanged(): void
    {
        $this->loadAccounts();
        unset($this->archivedAccounts);

        $this->selectedAccountId = $this->accounts->firstWhere('id', $this->selectedAccountId)?->id
            ?? $this->accounts->first()?->id;

        $this->changeCurrency();
        $this->updateData();
        $this->dispatch('account-updated', timeframe: 'all');
    }

    public function render()
    {
        return view('livewire.account-page');
    }
}
