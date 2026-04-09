<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacktestStrategy extends Model
{
    //
    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'rules'           => 'array',
        'initial_capital' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(BacktestTrade::class);
    }
}
