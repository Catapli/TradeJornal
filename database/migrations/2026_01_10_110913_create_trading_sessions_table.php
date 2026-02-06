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
        Schema::create('trading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_id')->nullable()->constrained()->nullOnDelete(); // Estrategia principal de la sesión

            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();

            // Snapshot del estado inicial para calcular delta de la sesión
            $table->decimal('start_balance', 15, 2);
            $table->decimal('end_balance', 15, 2)->nullable();

            // Estado psicológico y notas
            $table->string('start_mood')->nullable(); // Cómo empiezas
            $table->string('end_mood')->nullable();   // Cómo terminas
            $table->text('pre_session_notes')->nullable();
            $table->text('post_session_notes')->nullable();

            // Estadísticas rápidas de la sesión (se rellenan al cerrar)
            $table->integer('total_trades')->default(0);
            $table->decimal('session_pnl', 15, 2)->default(0);
            $table->decimal('session_pnl_percent', 8, 2)->default(0);
            $table->json('checklist_state')->nullable()->after('strategy_id');

            $table->string('status')->default('active'); // active, closed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_sessions');
    }
};
