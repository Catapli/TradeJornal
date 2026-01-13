<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Symfony\Component\String\s;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // ---------------------------------------------------------------
            // 1. VINCULACIÓN CON TU SISTEMA DE OBJETIVOS
            // ---------------------------------------------------------------
            // FK al "Producto" (Ej: FTMO 100k USD). Nullable si es cuenta personal.
            $table->foreignId('program_level_id')->constrained()->nullOnDelete();

            // FK a la "Fase Actual" (Ej: Fase 1). Nullable si es personal o ya fondeada sin reglas.
            $table->foreignId('program_objective_id')->constrained()->nullOnDelete();

            // ---------------------------------------------------------------
            // 2. IDENTIFICACIÓN Y TIPO
            // ---------------------------------------------------------------
            $table->string('name'); // "Mi cuenta FTMO Enero"
            $table->enum('type', ['prop_firm', 'personal', 'demo'])->default('prop_firm'); // Distinción rápida

            // Estado de Salud (No mezclar con la Fase)
            // active: Operando
            // passed: Objetivo cumplido (esperando credenciales de sig. fase)
            // failed: Regla rota (Burned)
            // abandoned: El usuario la dejó
            $table->enum('status', ['active', 'passed', 'burned', 'abandoned'])->default('active');

            // ---------------------------------------------------------------
            // 3. CONEXIÓN TÉCNICA (MT4/5/cTrader)
            // ---------------------------------------------------------------
            $table->boolean('sync')->default(false);
            $table->string('platform')->default('mt5'); // 'mt4', 'mt5', 'ctrader', 'dxtrade'
            $table->string('mt5_login')->nullable(); // El numero de cuenta real
            $table->string('mt5_password')->encrypted()->nullable();
            $table->string('mt5_server')->nullable();
            $table->string('broker_name')->nullable(); // Texto libre para mostrar (Ej: "FTMO-Server")

            $table->timestamp('last_sync')->nullable();
            $table->boolean('sync_error')->default(false);
            $table->text('sync_error_message')->nullable(); // Text es mejor que String para logs de error largos

            // ---------------------------------------------------------------
            // 4. MÉTRICAS (SNAPSHOTS)
            // ---------------------------------------------------------------
            // Guardamos esto para no recalcular todo el historial cada vez que cargas el dashboard
            $table->string('currency', 3)->default('USD'); // Vital para mostrar el símbolo correcto
            $table->decimal('initial_balance', 15, 2);
            $table->decimal('current_balance', 15, 2);
            $table->decimal('current_equity', 15, 2)->nullable(); // Importante para reglas de DD basado en Equity
            $table->decimal('today_starting_equity', 15, 2)->nullable(); // Comprobacion de Equity diaria
            $table->decimal('today_starting_balance', 15, 2)->nullable(); // Comprobacion de Balance diaria

            // Fechas clave
            $table->timestamp('funded_date')->nullable(); // Cuándo empezó esta fase
            $table->timestamp('end_date')->nullable();     // Cuándo terminó (pasó o falló)

            $table->timestamps();
            $table->softDeletes(); // Recomendado: nunca borres cuentas de trading, solo ocúltalas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
