<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingPlan extends Model
{
    //

    protected $fillable = [
        'account_id',
        'max_daily_trades',
        'max_daily_loss_percent',
        'daily_profit_target_percent',
        'start_time',
        'end_time',
        'is_active',
    ];

    public function plannable()
    {
        return $this->morphTo();
    }
}
