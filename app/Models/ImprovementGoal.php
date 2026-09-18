<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * El objetivo de mejora del mes (Fase 6 · P5).
 *
 * Uno por usuario y mes. Mientras el mes está en curso el objetivo está `active`;
 * al pasar se cierra como `achieved` o `missed` comparando `result` con `target`.
 * El cierre lo hace `CloseFinishedGoals`, y es irreversible a propósito: un
 * objetivo que se puede reabrir para que salga bien no es un objetivo.
 */
class ImprovementGoal extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ACHIEVED = 'achieved';

    public const STATUS_MISSED = 'missed';

    protected $fillable = [
        'user_id',
        'mistake_id',
        'month',
        'statement',
        'baseline',
        'target',
        'sample',
        'status',
        'result',
        'closed_at',
    ];

    /**
     * El valor por defecto de la columna vive en la base, y un modelo recién
     * creado no lo lee: `$goal->status` salía null y `isOpen()` daba por cerrado
     * un objetivo que acababa de nacer. Se declara aquí para que la instancia en
     * memoria y la fila digan lo mismo desde el primer momento.
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected $casts = [
        'month' => 'immutable_date',
        'baseline' => 'integer',
        'target' => 'integer',
        'sample' => 'integer',
        'result' => 'integer',
        'closed_at' => 'immutable_datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Mistake, $this> */
    public function mistake(): BelongsTo
    {
        return $this->belongsTo(Mistake::class);
    }

    /** ¿Sigue vivo, es decir, su mes todavía no ha terminado? */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Último instante que cuenta para este objetivo. */
    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->month)->endOfMonth();
    }
}
