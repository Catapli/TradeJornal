<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingPlan extends Model
{
    //

    protected $guarded = [];

    public function plannable()
    {
        return $this->morphTo();
    }
}
