<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDailyMetric extends Model
{
    protected $fillable = [
        'account_id',
        'date',
        'balance',
        'equity',
    ];

    // Castear la fecha para que Carbon funcione directo
    protected $casts = [
        'date' => 'date'
    ];
}
