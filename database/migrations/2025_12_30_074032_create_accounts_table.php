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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Fondeo PropFirm 1", "Personal MT5"
            $table->string('broker'); // "FTMO", "Bitget", "MT5"
            $table->decimal('initial_balance', 12, 2); // Balance Incial
            $table->decimal('current_balance', 12, 2); // Balance Actual
            $table->decimal('max_balance', 12, 2)->nullable(); // Límite fondeo
            $table->enum('status', ['phase_1', 'phase_2', 'active', 'burned']); // Status Actual (Fase 1, Fase 2, Activa / Fondeo, Perdida)
            $table->integer('max_daily_loss')->default(0); // Maxima Perdida Diaria
            $table->integer('max_total_loss')->default(0); // Máxima Perdida Total
            $table->timestamps();
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
