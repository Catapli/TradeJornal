<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{

    /** @use HasFactory<\Database\Factories\TradeFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'trade_asset_id',
        'strategy_id',
        'ticket',
        'direction',
        'entry_price',
        'exit_price',
        'size',
        'pnl',
        'pnl_pct',
        'rr_ratio',
        'risk_amount',
        'reward_amount',
        'duration_minutes',
        'status',
        'entry_time',
        'exit_time',
        'notes',
        'screenshot'
    ];

    protected $casts = [
        'entry_price' => 'decimal:5',
        'exit_price' => 'decimal:5',
        'size' => 'decimal:2',
        'pnl' => 'decimal:2',
        'pnl_pct' => 'decimal:2',
        'rr_ratio' => 'decimal:2',
        'risk_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tradeAsset(): BelongsTo
    {
        return $this->belongsTo(TradeAsset::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }
}
