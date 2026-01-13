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
        Schema::create('account_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');

            // La fecha del registro (sin hora)
            $table->date('date');

            // Los valores al cierre del día (que es el inicio del siguiente)
            $table->decimal('balance', 15, 2);
            $table->decimal('equity', 15, 2);

            // Opcional: Métricas extra si quieres calcularlas en el futuro
            // $table->integer('trades_count')->default(0);
            // $table->decimal('daily_return', 10, 2)->nullable(); // % ganado ese día

            // Evitar duplicados: Una métrica por cuenta y día
            $table->unique(['account_id', 'date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_daily_metrics');
    }
};
