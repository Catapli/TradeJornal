<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una regla que salió de un hallazgo del Laboratorio (Fase 5 · P7).
 */
class TradingRule extends Model
{
    /** Tope de operaciones al día. config: {"limit": 3} */
    public const KIND_MAX_TRADES = 'max_trades';

    /** Ventana horaria en la que sí se opera. config: {"from":"09:00","to":"13:00"} */
    public const KIND_TIME_WINDOW = 'time_window';

    /** Día de la semana que se evita. config: {"weekday": 4} — 0 domingo, 6 sábado */
    public const KIND_WEEKDAY = 'weekday';

    /** Recordatorio sobre un error concreto. config: {"mistake_id": 7} */
    public const KIND_MISTAKE = 'mistake';

    /**
     * Las que el semáforo de la sesión puede comprobar solo.
     *
     * Un recordatorio sobre un error no está aquí a propósito: la máquina no sabe
     * si has entrado por FOMO. Eso lo dice el checklist y lo decides tú.
     */
    public const ENFORCEABLE = [self::KIND_MAX_TRADES, self::KIND_TIME_WINDOW, self::KIND_WEEKDAY];

    protected $fillable = [
        'user_id',
        'account_id',
        'kind',
        'text',
        'config',
        'source_key',
        'source_summary',
        'source_sample',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'source_sample' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Reglas que aplican a una cuenta: las suyas y las globales del usuario. */
    public function scopeForAccount($query, int $userId, ?int $accountId)
    {
        return $query->where('user_id', $userId)
            ->where(function ($q) use ($accountId) {
                $q->whereNull('account_id');

                if ($accountId !== null) {
                    $q->orWhere('account_id', $accountId);
                }
            });
    }

    public function isEnforceable(): bool
    {
        return in_array($this->kind, self::ENFORCEABLE, true);
    }

    public function isGlobal(): bool
    {
        return $this->account_id === null;
    }
}
