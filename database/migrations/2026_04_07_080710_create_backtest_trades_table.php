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
        Schema::create('backtest_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backtest_strategy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Temporalidad
            $table->date('trade_date');
            $table->time('entry_time')->nullable();
            $table->time('exit_time')->nullable();
            $table->enum('session', ['london', 'new_york', 'asia', 'other'])->nullable();

            // Datos de precio
            $table->enum('direction', ['long', 'short']);
            $table->decimal('entry_price', 15, 5);
            $table->decimal('exit_price', 15, 5);
            $table->decimal('stop_loss', 15, 5)->nullable();

            // Resultado
            $table->decimal('pnl_r', 8, 4)->nullable();         // resultado en R (lo más importante)
            $table->decimal('risk_pct', 5, 2)->nullable(); // null = usa el de la estrategia

            // Calidad / contexto
            $table->tinyInteger('setup_rating')->nullable();     // 1-5
            $table->boolean('followed_rules')->default(true);
            $table->json('confluences')->nullable();             // ["EMA200", "FVG", "POI"]
            $table->text('notes')->nullable();
            $table->string('screenshot')->nullable();

            $table->timestamps();

            // Índices para los cálculos de métricas (evitan full-scans)
            $table->index(['backtest_strategy_id', 'trade_date']);
            $table->index(['backtest_strategy_id', 'session']);
            $table->index(['backtest_strategy_id', 'followed_rules']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtest_trades');
    }
};
