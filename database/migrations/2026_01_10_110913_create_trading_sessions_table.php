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
            // Un solo índice cubre: listing, dateFrom, dateTo, y el ORDER BY
            $table->index(['user_id', 'start_time'], 'idx_sessions_user_time');

            // Filtro por cuenta (filterAccount)
            // El FK index simple de account_id no basta — no incluye user_id
            $table->index(
                ['user_id', 'account_id'],
                'idx_sessions_user_account'
            );

            // Filtro por estado psicológico (filterMood)
            $table->index(
                ['user_id', 'end_mood'],
                'idx_sessions_user_mood'
            );

            // Forward-looking: filtro por estrategia (Mejora 17)
            // Creamos ahora para evitar una segunda migration más adelante
            $table->index(
                ['user_id', 'strategy_id'],
                'idx_sessions_user_strategy'
            );

            // Covering index para la query de stats agregados (Mejora 8)
            // Permite Index-Only Scan en: COUNT(*), SUM(session_pnl), AVG(CASE WHEN session_pnl...)
            // PostgreSQL no necesita tocar el heap — lee todo desde el índice
            $table->index(
                ['user_id', 'session_pnl'],
                'idx_sessions_user_pnl'
            );
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
