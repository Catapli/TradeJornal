<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una revisión semanal guiada (una por usuario y semana).
 *
 * `answers` guarda las respuestas indexadas por operación —
 * `['trade_42' => ['plan' => 'yes', 'trigger' => '…', 'change' => '…']]` — y
 * `stats` congela las métricas de la semana tal y como se vieron al cerrarla.
 */
class WeeklyReview extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'week_start' => 'date',
        'answers' => 'array',
        'stats' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
