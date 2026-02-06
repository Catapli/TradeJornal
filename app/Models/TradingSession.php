<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingSession extends Model
{
    //

    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'start_balance' => 'decimal:2',
        'end_balance' => 'decimal:2',
        'session_pnl' => 'decimal:2',
        'checklist_state' => 'array',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    public function strategy()
    {
        return $this->belongsTo(Strategy::class);
    }
    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    // RELACIÓN QUE FALTABA
    public function notes()
    {
        // Asumiendo que tienes una tabla 'session_notes' o 'trading_session_notes'
        // con columna 'trading_session_id'
        return $this->hasMany(\App\Models\SessionNote::class);

        // Si tu modelo se llama diferente (ej: TradingNote), ajusta:
        // return $this->hasMany(\App\Models\TradingNote::class);
    }
}
