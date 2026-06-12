<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Strategy extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'description',
        'image_path',
        'rules',
        'timeframe',
        'is_main',
        'stats_total_trades',
        'stats_winning_trades',
        'stats_losing_trades',
        'stats_total_pnl',
        'stats_gross_profit',
        'stats_gross_loss',
        'stats_profit_factor',
        'stats_avg_win',
        'stats_avg_loss',
        'stats_expectancy',
        'stats_avg_rr',
        'stats_max_drawdown_pct',
        'stats_sharpe_ratio',
        'stats_avg_mae_pct',
        'stats_avg_mfe_pct',
        'stats_by_day_of_week',
        'stats_by_hour',
        'stats_best_win_streak',
        'stats_worst_loss_streak',
        'stats_last_calculated_at',
    ];

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
