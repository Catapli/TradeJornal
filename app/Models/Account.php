<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'max_balance' => 'decimal:2',
    ];

    // ← ACCESSOR STATUS FORMATEADO
    public function getStatusFormattedAttribute(): string
    {
        return match ($this->status) {
            'phase_1' => 'Fase 1',
            'phase_2' => 'Fase 2',
            'active' => 'Activa',
            'burned' => 'Quemada',
            default => 'Desconocida'
        };
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
