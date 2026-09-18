<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory;

    /**
     * Archivada, no borrada: `trades.account_id` es `cascade`, así que un
     * `delete()` de verdad se llevaba por delante todo el histórico de la
     * cuenta. Con esto, «Eliminar» la aparta de todas las pantallas —el scope
     * global también filtra el `whereHas('account')` de `Trade`— y el dato
     * sigue ahí para restaurarla.
     */
    use SoftDeletes;

    /**
     * Fracción del balance inicial que un día tiene que ganar para contar como
     * «día rentable» en `min_trading_days`.
     *
     * Vive aquí porque la comparte la simulación de la fase (`SimulateChallenge`):
     * si las dos usaran umbrales distintos, el centro de mando prometería un
     * plazo que el semáforo de la cuenta nunca daría por cumplido.
     */
    public const PROFITABLE_DAY_THRESHOLD = 0.003;

    protected $guarded = ['id'];

    protected $casts = [
        'last_sync' => 'datetime',
        // `CalculateAccountStatistics` las trata como Carbon (`->diffInDays()`), pero sin
        // cast Eloquent las devuelve como string en cuanto la cuenta viene de la BD.
        // No reventaba solo porque hoy nada rellena `funded_date` fuera de la factory.
        'funded_date' => 'datetime',
        'end_date' => 'datetime',
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    /** @return BelongsTo<ProgramObjective, $this> */
    public function currentObjective(): BelongsTo
    {
        return $this->belongsTo(ProgramObjective::class, 'program_objective_id');
    }

    public function programLevel(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    // Añadir esta relación en ambos modelos
    public function tradingPlan()
    {
        return $this->hasOne(TradingPlan::class);
    }

    // ← ACCESSOR STATUS FORMATEADO
    public function getStatusFormattedAttribute(): string
    {
        // El enum de `status` es active|passed|burned|abandoned. Antes esto cubría
        // 'phase_1'/'phase_2' (imposibles) y no cubría 'passed' ni 'abandoned', así
        // que una cuenta que superaba el challenge se mostraba como "Desconocido".
        return match ($this->status) {
            'active' => __('labels.activa'),
            'passed' => __('labels.passed'),
            'burned' => __('labels.burned'),
            'abandoned' => __('labels.abandoned'),
            default => __('labels.unknown')
        };
    }

    public function getPhaseLabelAttribute(): string
    {
        // CASO 1: Cuenta Personal
        if ($this->type === 'personal') {
            // Podrías mirar si el broker es "Demo" o "Real" si tienes ese campo,
            // pero por defecto devolvemos "Personal".
            return __('labels.personal_account');
        }

        // CASO 2: Prop Firm (Depende del objetivo vinculado)
        if ($this->currentObjective) {

            // Opción A: Usar el nombre que guardaste en BD (Ej: "Phase 1: Challenge")
            // return $this->currentObjective->name;

            // Opción B: Lógica personalizada basada en el número de fase (Más flexible para traducir)
            //
            // El (int) no es decorativo: `phase_number` es un `enum` de Postgres,
            // así que Eloquent lo devuelve como **string** ('1', '0'…) y el match
            // usa comparación estricta. Sin el cast no acertaba ni un brazo y todo
            // caía al default: una cuenta fondeada (fase 0) salía como «Fase 0» en
            // vez de «Cuenta Fondeada (VIVO)».
            return match ((int) $this->currentObjective->phase_number) {
                1 => __('labels.phase_1_evaluation'),
                2 => __('labels.phase_2_verification'),
                3 => __('labels.phase_3'),
                0 => __('labels.account_funded'), // El 0 suele usarse para la cuenta real
                default => __('labels.phase') . $this->currentObjective->phase_number,
            };
        }

        // CASO 3: Error de configuración (Prop firm sin objetivo)
        return __('labels.without_objective');
    }

    /**
     * Progreso de la cuenta contra las reglas de su fase actual.
     *
     * Devuelve siempre una colección (vacía si la cuenta no tiene fase), con una
     * entrada por regla configurada. `type` es una clave estable, no un literal
     * traducido: `card-objectives.blade.php` la usa para elegir icono y color.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getObjectivesProgressAttribute(): \Illuminate\Support\Collection
    {
        $objective = $this->currentObjective;
        if (!$objective) {
            return collect();
        }

        $results = [];
        $initial = (float) $this->initial_balance;
        // Usamos el equity actual para medir el drawdown vivo, o el balance si no hay equity
        $currentEquity = (float) ($this->current_equity ?? $this->current_balance);
        $currentBalance = (float) $this->current_balance;

        // ---------------------------------------------------
        // 1. PROFIT TARGET (Objetivo de Ganancia)
        // ---------------------------------------------------
        if ($objective->profit_target_percent > 0) {
            $target = $initial * ($objective->profit_target_percent / 100);
            $currentProfit = $currentBalance - $initial;

            $results[] = [
                'type' => 'profit_target',
                'label' => __('labels.profit_target') . ' (' . $objective->profit_target_percent . '%)',
                'target_value' => $target,
                // Si estás en negativo, el progreso hacia el target es 0, no negativo
                'current_value' => max(0, $currentProfit),
                'status' => $currentProfit >= $target ? 'passed' : 'ongoing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => false,
            ];
        }

        // ---------------------------------------------------
        // 2. MAX DAILY LOSS (Pérdida Diaria Máxima)
        // ---------------------------------------------------
        if ($objective->max_daily_loss_percent > 0) {
            $limit = $initial * ($objective->max_daily_loss_percent / 100);

            // Lógica de recuperación del Balance Inicial del Día
            if ($this->today_starting_equity) {
                // Caso ideal: Tenemos el dato guardado de anoche (snapshot)
                $startDayEquity = (float) $this->today_starting_equity;
            } else {
                // Caso fallback: No hay dato guardado. Lo calculamos matemáticamente.
                // Fórmula: Balance Actual - (PnL de lo cerrado hoy)

                // Sumamos el PnL de los trades donde exit_time es HOY
                $todaysRealizedProfit = $this->trades()
                    ->whereRaw('DATE(exit_time) = CURRENT_DATE')
                    ->sum('pnl'); // Nota: Asegúrate que 'pnl' incluye comisiones/swap si aplica

                $startDayEquity = $currentBalance - (float) $todaysRealizedProfit;
            }

            // Cálculo: Cuánto ha bajado mi Equity actual respecto a como empecé el día
            // Ejemplo: Empecé con 10k, tengo 9.5k. Drawdown = 500.
            $currentDailyDrawdown = max(0, $startDayEquity - $currentEquity);

            $results[] = [
                'type' => 'max_daily_loss',
                'label' => __('labels.loss_daily') . $objective->max_daily_loss_percent . '%)',
                'target_value' => $limit,
                'current_value' => $currentDailyDrawdown,
                'status' => $currentDailyDrawdown >= $limit ? 'failed' : 'passing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => true,
            ];
        }

        // ---------------------------------------------------
        // 3. MAX TOTAL LOSS (Pérdida Total Máxima)
        // ---------------------------------------------------
        if ($objective->max_total_loss_percent > 0) {
            $limit = $initial * ($objective->max_total_loss_percent / 100);

            // Cálculo: Cuánto ha bajado mi Equity actual respecto al Balance Inicial de la cuenta
            $currentTotalDrawdown = max(0, $initial - $currentEquity);

            $results[] = [
                'type' => 'max_total_loss',
                'label' => __('labels.loss_total') . $objective->max_total_loss_percent . '%)',
                'target_value' => $limit,
                'current_value' => $currentTotalDrawdown,
                'status' => $currentTotalDrawdown >= $limit ? 'failed' : 'passing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => true,
            ];
        }

        // ---------------------------------------------------
        // 4. MIN TRADING DAYS (Días Mínimos Operados)
        // ---------------------------------------------------
        if ($objective->min_trading_days > 0) {
            // Umbral: Un día cuenta si se ganó al menos el 0.3% del balance inicial
            $dailyProfitThreshold = $initial * self::PROFITABLE_DAY_THRESHOLD;

            // Se agrupa por `exit_time`, no por `entry_time`: el PnL se realiza al cerrar,
            // así que un trade abierto el viernes y cerrado el lunes cuenta como lunes.
            // Es el mismo criterio que usa el drawdown diario de arriba.
            $profitableDays = $this->trades()
                ->selectRaw('DATE(exit_time) as trade_date')
                ->groupByRaw('DATE(exit_time)')
                ->havingRaw('SUM(pnl) >= ?', [$dailyProfitThreshold])
                ->get()
                ->count();

            $results[] = [
                'type' => 'min_trading_days',
                'label' => __('labels.profitable_days'),
                'target_value' => $objective->min_trading_days,
                'current_value' => $profitableDays,
                'status' => $profitableDays >= $objective->min_trading_days ? 'passed' : 'ongoing',
                'unit' => 'days',
                'is_hard_rule' => false,
            ];
        }

        return collect($results);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
