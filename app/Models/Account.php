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
        $currentEquity = (float) ($this->current_equity ?? $this->current_balance);
        $currentBalance = (float) $this->current_balance;

        // 1. PROFIT TARGET
        if ($objective->profit_target_percent > 0) {
            $target = $initial * ($objective->profit_target_percent / 100);
            $currentProfit = $currentBalance - $initial;

            $results[] = [
                'type' => 'profit_target',
                'label' => 'Profit Target (' . $objective->profit_target_percent . '%)',
                'target_value' => $target,
                'current_value' => max(0, $currentProfit), // Si pierdes, llevas 0 de target
                'status' => $currentProfit >= $target ? 'passed' : 'ongoing',
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => false
            ];
        }

        // 2. MAX DAILY LOSS (Pérdida Diaria)
        // Regla: Equity Actual vs Equity Inicio del Día
        if ($objective->max_daily_loss_percent > 0) {
            $limit = $initial * ($objective->max_daily_loss_percent / 100);

            // Obtenemos el punto de referencia de las 00:00 (o inicial si es cuenta nueva)
            $startDayEquity = (float) ($this->today_starting_equity ?? $initial);

            // CÁLCULO DRAWDOWN: ¿Cuánto ha bajado desde el inicio del día?
            // Si empecé con 10,000 y tengo 10,020 -> (10000 - 10020) = -20. Max(0, -20) = 0. (Correcto, no hay pérdida)
            // Si empecé con 10,000 y tengo 9,500  -> (10000 - 9500)  = 500.  (Pérdida positiva)
            $currentDailyDrawdown = max(0, $startDayEquity - $currentEquity);

            $results[] = [
                'type' => 'max_daily_loss',
                'label' => 'Pérdida Diaria (' . $objective->max_daily_loss_percent . '%)',
                'target_value' => $limit,
                'current_value' => $currentDailyDrawdown, // Aquí enviamos lo PERDIDO, no el balance
                'status' => $currentDailyDrawdown >= $limit ? 'failed' : 'passing', // passing = en regla
                'unit' => 'money',
                'currency' => $this->currency,
                'is_hard_rule' => true
            ];
        }

        // 3. MAX TOTAL LOSS (Pérdida Total)
        // Regla: Equity Actual vs Balance Inicial de la cuenta
        if ($objective->max_total_loss_percent > 0) {
            $limit = $initial * ($objective->max_total_loss_percent / 100);

            // CÁLCULO DRAWDOWN TOTAL
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
        // 4. DÍAS MÍNIMOS RENTABLES (Min Trading Days)
        // Regla: Días con Profit >= 0.3% del Balance Inicial
        if ($objective->min_trading_days > 0) {

            $dailyProfitThreshold = $initial * 0.003;

            // CORRECCIÓN POSTGRESQL:
            // 1. Agrupamos por DATE(entry_time).
            // 2. En el HAVING usamos SUM(pnl) directamente, no el alias.
            $profitableDays = $this->trades()
                ->selectRaw('DATE(entry_time) as trade_date') // Seleccionamos la fecha
                ->groupByRaw('DATE(entry_time)')              // Agrupamos por la fecha calculada
                ->havingRaw('SUM(pnl) >= ?', [$dailyProfitThreshold]) // Filtramos usando la suma real
                ->get()
                ->count(); // Contamos cuántas filas (días) devolvió la consulta

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
