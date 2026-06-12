<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EconomicEvent extends Model
{
    protected $fillable = [
        'date',
        'time',
        'currency',
        'event',
        'impact',
        'actual',
        'previous',
        'forecast',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
    ];

    // ==========================================
    // 🎯 SCOPES OPTIMIZADOS
    // ==========================================

    /**
     * ✅ NUEVO: Scope para eventos próximos de alto impacto
     * 
     * @param int $minutesBefore Minutos hacia atrás (eventos recientes)
     * @param int $minutesAhead Minutos hacia adelante (eventos futuros)
     */
    public function scopeUpcoming($query, int $minutesBefore = 10, int $minutesAhead = 60)
    {
        $now = now();
        $pastLimit = now()->subMinutes($minutesBefore);
        $futureLimit = now()->addMinutes($minutesAhead);

        return $query
            ->where('date', $now->toDateString())
            ->whereBetween('time', [
                $pastLimit->format('H:i:s'),
                $futureLimit->format('H:i:s')
            ])
            ->where('impact', 'high')
            ->orderBy('time', 'asc');
    }

    /**
     * ✅ NUEVO: Scope para filtrar por divisas específicas
     */
    public function scopeForCurrencies($query, array $currencies)
    {
        return $query->whereIn('currency', $currencies);
    }

    /**
     * ✅ NUEVO: Scope para eventos por impacto
     */
    public function scopeByImpact($query, string $impact)
    {
        return $query->where('impact', $impact);
    }

    // ==========================================
    // 🧮 MÉTODOS AUXILIARES
    // ==========================================

    /**
     * ✅ NUEVO: Calcula minutos restantes hasta el evento
     */
    public function getMinutesUntilAttribute(): int
    {
        $eventTime = \Carbon\Carbon::parse($this->date . ' ' . $this->time);
        return now()->diffInMinutes($eventTime, false);
    }

    /**
     * ✅ NUEVO: Formato simplificado para API
     */
    public function toSimpleArray(): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'event' => $this->event,
            'time' => $this->time instanceof \Carbon\Carbon
                ? $this->time->format('H:i')
                : substr($this->time, 0, 5),
            'minutes_diff' => $this->minutes_until
        ];
    }
}
