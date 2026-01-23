<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EconomicEvent extends Model
{
    //
    protected $guarded = [];
    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i', // Para formatear fácil
    ];
}
