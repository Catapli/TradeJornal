<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramObjective extends Model
{
    use HasFactory;

    // Permitimos asignación masiva (útil para los Seeders)
    protected $guarded = [];

    // Transformación automática de datos
    protected $casts = [
        'rules_metadata' => 'array', // Ahora $objective->rules_metadata será un array, no un string JSON
        'profit_target_percent' => 'float',
        'max_daily_loss_percent' => 'float',
        'max_total_loss_percent' => 'float',
        'min_trading_days' => 'integer',
    ];

    /**
     * Relación con el Nivel del Programa (Padre)
     * Ej: Este objetivo pertenece al nivel "100k USD"
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    /**
     * Relación con las Cuentas (Hijos)
     * Ej: Este objetivo está siendo cursado por 50 cuentas activas.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'program_objective_id');
    }
}
