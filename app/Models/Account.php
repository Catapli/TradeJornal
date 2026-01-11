<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
