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
        Schema::create('trading_plans', function (Blueprint $table) {
            $table->id();
            // Polimorfismo: Se conecta a Account o Strategy
            $table->foreignId('account_id')->unique()->constrained()->onDelete('cascade');

            // Reglas (Null = No aplica esa regla)
            $table->integer('max_daily_trades')->nullable(); // Ej: 5 trades
            $table->decimal('max_daily_loss_percent', 5, 2)->nullable(); // Ej: -500.00 (Drawdown límite)
            $table->decimal('daily_profit_target_percent', 5, 2)->nullable(); // Ej: 100.00

            // Horario Operativo (Formato HH:MM)
            $table->time('start_time')->nullable(); // Ej: 09:00
            $table->time('end_time')->nullable();   // Ej: 17:00

            // Configuración
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices para velocidad
            $table->index(['account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_plans');
    }
};
