<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDailyMetric extends Model
{
    protected $guarded = [];

    // Castear la fecha para que Carbon funcione directo
    protected $casts = [
        'date' => 'date'
    ];
}
