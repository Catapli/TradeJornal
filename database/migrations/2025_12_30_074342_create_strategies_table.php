<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Breakout H1", "Scalping 5m"
            $table->string('description')->nullable();
            $table->string('timeframe'); // "M5", "H1", "D1"
            $table->boolean('is_main')->default(false); // Estrategia principal
            // Imagen del setup ideal
            $table->string('image_path')->nullable()->after('description');
            // Reglas en formato JSON (ej: ["Esperar cierre vela", "RSI en sobreventa"])
            $table->json('rules')->nullable()->after('image_path');
            // Color para identificarla en los gráficos (hex)
            $table->string('color')->default('#4F46E5')->after('name');

            // ========== STATS BÁSICAS (actualizadas vía Observer) ==========
            $table->integer('stats_total_trades')->default(0)->after('color');
            $table->integer('stats_winning_trades')->default(0)->after('stats_total_trades');
            $table->integer('stats_losing_trades')->default(0)->after('stats_winning_trades');
            $table->decimal('stats_total_pnl', 15, 2)->default(0)->after('stats_losing_trades');
            $table->decimal('stats_gross_profit', 15, 2)->default(0)->after('stats_total_pnl');
            $table->decimal('stats_gross_loss', 15, 2)->default(0)->after('stats_gross_profit');

            // ========== KPIs CALCULADOS ==========
            $table->decimal('stats_profit_factor', 8, 2)->nullable()->after('stats_gross_loss');
            $table->decimal('stats_avg_win', 10, 2)->nullable()->after('stats_profit_factor');
            $table->decimal('stats_avg_loss', 10, 2)->nullable()->after('stats_avg_win');
            $table->decimal('stats_expectancy', 10, 2)->nullable()->after('stats_avg_loss');
            $table->decimal('stats_avg_rr', 8, 2)->nullable()->after('stats_expectancy'); // R:R promedio real

            // ========== RISK METRICS ==========
            $table->decimal('stats_max_drawdown_pct', 8, 2)->nullable()->after('stats_avg_rr');
            $table->decimal('stats_sharpe_ratio', 8, 2)->nullable()->after('stats_max_drawdown_pct');

            // ========== EFICIENCIA (MAE/MFE) ==========
            $table->decimal('stats_avg_mae_pct', 8, 2)->nullable()->after('stats_sharpe_ratio'); // Drawdown intra-trade promedio
            $table->decimal('stats_avg_mfe_pct', 8, 2)->nullable()->after('stats_avg_mae_pct'); // Ganancia máxima promedio

            // ========== TEMPORAL (para heatmaps/cronotipos) ==========
            $table->json('stats_by_day_of_week')->nullable()->after('stats_avg_mfe_pct');
            $table->json('stats_by_hour')->nullable()->after('stats_by_day_of_week');

            // ========== STREAKS ==========
            $table->integer('stats_best_win_streak')->default(0)->after('stats_by_hour');
            $table->integer('stats_worst_loss_streak')->default(0)->after('stats_best_win_streak');

            // ========== METADATA ==========
            $table->timestamp('stats_last_calculated_at')->nullable()->after('stats_worst_loss_streak');
            $table->timestamps();

            // Índice compuesto para queries del dashboard
            $table->index(['user_id', 'stats_total_trades']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategies');
    }
};
