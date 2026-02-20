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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('trade_asset_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('trading_session_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ticket')->nullable()->unique(); // ID único broker
            $table->enum('direction', ['long', 'short']);
            $table->decimal('entry_price', 12, 5);
            $table->decimal('exit_price', 12, 5);
            $table->decimal('size', 12, 2);
            $table->decimal('pnl', 10, 2);
            $table->unsignedBigInteger('duration_minutes');
            $table->timestamp('entry_time');
            $table->timestamp('exit_time');
            $table->text('notes')->nullable();
            $table->json('checklist_data')->nullable();
            // Emoción específica del trade
            $table->string('mood')->nullable();

            $table->string('screenshot')->nullable();
            $table->text('ai_analysis')->nullable();
            $table->string('chart_data_path')->nullable();
            $table->decimal("pnl_percentage", 8, 4)->nullable();
            $table->string('position_id')->nullable();
            $table->json('executions_data')->nullable();

            // MAE: Maximum Adverse Excursion (El peor precio/pérdida máxima latente)
            $table->decimal('mae_price', 16, 8)->nullable();

            // MFE: Maximum Favorable Excursion (El mejor precio/ganancia máxima latente)
            $table->decimal('mfe_price', 16, 8)->nullable();
            $table->decimal('pips_traveled', 10, 2)->nullable();


            $table->index(['account_id', 'position_id']);
            $table->index('entry_time');
            $table->index(['exit_time', 'id']);
            // 👇 ÍNDICE 1: Para queries de agregación por cuenta y fecha
            // Usado en: calculateStats(), generateCalendar(), heatmap
            $table->index(['account_id', 'exit_time', 'pnl'], 'idx_trades_account_exit_pnl');

            // 👇 ÍNDICE 2: Para filtrar por PnL positivo/negativo (winRate)
            $table->index(['account_id', 'pnl'], 'idx_trades_account_pnl');

            // 👇 ÍNDICE 3: Para el heatmap (día de la semana + hora)
            // PostgreSQL permite índices con expresiones
            $table->rawIndex(
                'account_id, (EXTRACT(ISODOW FROM exit_time)), (EXTRACT(HOUR FROM exit_time))',
                'idx_trades_heatmap'
            );
            $table->index('direction', 'idx_trades_direction');
            $table->index(['mae_price', 'mfe_price'], 'idx_trades_mae_mfe');
            $table->index(['account_id', 'entry_time'], 'idx_trades_account_entry');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
