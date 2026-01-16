<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'max_balance' => 'decimal:2',
    ];

    public function currentObjective()
    {
        return $this->belongsTo(ProgramObjective::class, 'program_objective_id');
    }

    public function programLevel(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }


    // ← ACCESSOR STATUS FORMATEADO
    public function getStatusFormattedAttribute(): string
    {
        return match ($this->status) {
            'phase_1' => 'Fase 1',
            'phase_2' => 'Fase 2',
            'active' => 'Activa',
            'burned' => 'Quemada',
            default => 'Desconocida'
        };
    }

    public function getPhaseLabelAttribute(): string
    {
        // CASO 1: Cuenta Personal
        if ($this->type === 'personal') {
            // Podrías mirar si el broker es "Demo" o "Real" si tienes ese campo,
            // pero por defecto devolvemos "Personal".
            return 'Cuenta Personal';
        }

        // CASO 2: Prop Firm (Depende del objetivo vinculado)
        if ($this->currentObjective) {

            // Opción A: Usar el nombre que guardaste en BD (Ej: "Phase 1: Challenge")
            // return $this->currentObjective->name; 

            // Opción B: Lógica personalizada basada en el número de fase (Más flexible para traducir)
            return match ($this->currentObjective->phase_number) {
                1 => 'Fase 1 (Evaluación)',
                2 => 'Fase 2 (Verificación)',
                3 => 'Fase 3',
                0 => 'Account Funded (Live)', // El 0 suele usarse para la cuenta real
                default => 'Fase ' . $this->currentObjective->phase_number,
            };
        }

        // CASO 3: Error de configuración (Prop firm sin objetivo)
        return 'Sin Objetivo Asignado';
    }

    public function getHardRulesAttribute()
    {
        $rules = $this->currentObjective;
        return [
            'target_amount' => $this->initial_balance * ($rules->profit_target_percent / 100),
            'daily_loss_limit' => $this->initial_balance * ($rules->max_daily_loss_percent / 100),
        ];
    }

    public function getObjectivesProgressAttribute()
    {
        $objective = $this->currentObjective;
        if (!$objective) return [];

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
                'label' => 'Profit Target (' . $objective->profit_target_percent . '%)',
                'target_value' => $target,
                // Si estás en negativo, el progreso hacia el target es 0, no negativo
                'current_value' => max(0, $currentProfit),
                'status' => $currentProfit >= $target ? 'passed' : 'ongoing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => false
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

                $startDayEquity = $currentBalance - (float)$todaysRealizedProfit;
            }

            // Cálculo: Cuánto ha bajado mi Equity actual respecto a como empecé el día
            // Ejemplo: Empecé con 10k, tengo 9.5k. Drawdown = 500.
            $currentDailyDrawdown = max(0, $startDayEquity - $currentEquity);

            $results[] = [
                'type' => 'max_daily_loss',
                'label' => 'Pérdida Diaria (' . $objective->max_daily_loss_percent . '%)',
                'target_value' => $limit,
                'current_value' => $currentDailyDrawdown,
                'status' => $currentDailyDrawdown >= $limit ? 'failed' : 'passing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => true
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
                'label' => 'Pérdida Total (' . $objective->max_total_loss_percent . '%)',
                'target_value' => $limit,
                'current_value' => $currentTotalDrawdown,
                'status' => $currentTotalDrawdown >= $limit ? 'failed' : 'passing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => true
            ];
        }

        // ---------------------------------------------------
        // 4. MIN TRADING DAYS (Días Mínimos Operados)
        // ---------------------------------------------------
        if ($objective->min_trading_days > 0) {
            // Umbral: Un día cuenta si se ganó al menos el 0.3% del balance inicial
            $dailyProfitThreshold = $initial * 0.003;

            // Consulta compatible con PostgreSQL para agrupar por fecha y sumar PnL
            $profitableDays = $this->trades()
                ->selectRaw('DATE(entry_time) as trade_date')
                ->groupByRaw('DATE(entry_time)')
                ->havingRaw('SUM(pnl) >= ?', [$dailyProfitThreshold])
                ->get()
                ->count();

            $results[] = [
                'type' => 'min_trading_days',
                'label' => 'Días Rentables (>0.3%)',
                'target_value' => $objective->min_trading_days,
                'current_value' => $profitableDays,
                'status' => $profitableDays >= $objective->min_trading_days ? 'passed' : 'ongoing',
                'unit' => 'days',
                'is_hard_rule' => false
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
