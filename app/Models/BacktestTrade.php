<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestTrade extends Model
{
    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'trade_date'     => 'date',
        'entry_time'     => 'datetime:H:i',
        'exit_time'      => 'datetime:H:i',
        'pnl_r'          => 'float',
        'entry_price'    => 'float',
        'exit_price'     => 'float',
        'stop_loss'      => 'float',
        'followed_rules' => 'boolean',
        'confluences'    => 'array',
    ];

    // ── Accessors útiles ──────────────────────────────────────────

    public function getOutcomeAttribute(): string
    {
        if ($this->pnl_r > 0)  return 'win';
        if ($this->pnl_r < 0)  return 'loss';
        return 'be';
    }

    public function getIsWinAttribute(): bool
    {
        return $this->pnl_r > 0;
    }
    public function getIsLossAttribute(): bool
    {
        return $this->pnl_r < 0;
    }
    public function getIsBeAttribute(): bool
    {
        return $this->pnl_r == 0;
    }

    // ── Relaciones ────────────────────────────────────────────────

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(BacktestStrategy::class, 'backtest_strategy_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeWinners($query)
    {
        return $query->where('pnl_r', '>', 0);
    }
    public function scopeLosers($query)
    {
        return $query->where('pnl_r', '<', 0);
    }
    public function scopeBySession($query, string $session)
    {
        return $query->where('session', $session);
    }
}
