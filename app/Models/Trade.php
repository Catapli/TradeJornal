<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Trade extends Model
{
    /** @use HasFactory<\Database\Factories\TradeFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'entry_price' => 'decimal:5',
        'exit_price' => 'decimal:5',
        'size' => 'decimal:2',
        'pnl' => 'decimal:2',
        'pnl_pct' => 'decimal:2',
        'rr_ratio' => 'decimal:2',
        'risk_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'executions_data' => 'array',
        'pips_traveled' => 'decimal:2',
    ];

    /**
     * Trades cuyas cuentas pertenecen al usuario (por defecto, el autenticado).
     *
     * Incluye las **quemadas** —el historial sigue siendo suyo y es el más
     * instructivo que tiene— y deja fuera las **archivadas**, que las aparta el
     * scope global de SoftDeletes de `Account`. Es el filtro de todo lo que
     * habla del trader: Mentor, hallazgos y cola de repaso.
     */
    public function scopeForUser($query, $userId = null)
    {
        return $query->whereHas('account', function ($q) use ($userId) {
            $q->where('user_id', $userId ?? Auth::id());
        });
    }

    /**
     * Igual que forUser, pero excluyendo también las cuentas quemadas.
     *
     * Es el filtro de lo que habla de **dinero vivo**: el valor por defecto del
     * panel y el consejo del día. Una cuenta muerta no opera hoy. Para verla en
     * el panel se elige a mano en el selector, y entonces `DashboardTradeQuery`
     * no aplica este scope.
     */
    public function scopeForUserActiveAccounts($query, $userId = null)
    {
        return $query->whereHas('account', function ($q) use ($userId) {
            $q->where('user_id', $userId ?? Auth::id())
                ->where('status', '!=', 'burned');
        });
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tradeAsset(): BelongsTo
    {
        return $this->belongsTo(TradeAsset::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    /** @return BelongsToMany<Mistake, $this> */
    public function mistakes(): BelongsToMany
    {
        return $this->belongsToMany(Mistake::class, 'trade_mistake');
    }
}
