<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeViolation extends Model
{

    protected $fillable = [
        'trade_id',
        'rule_key',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una violación pertenece a un trade
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
