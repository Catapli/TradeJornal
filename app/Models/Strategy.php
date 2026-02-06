<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Strategy extends Model
{
    //
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rules' => 'array', // Convierte JSON a Array automáticamente
    ];

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    // Añadir esta relación en ambos modelos
    public function tradingPlan()
    {
        return $this->morphOne(TradingPlan::class, 'plannable');
    }
}
