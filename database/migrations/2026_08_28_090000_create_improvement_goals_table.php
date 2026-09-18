<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El objetivo de mejora del mes (Fase 6 · P5).
 *
 * Hasta ahora la aplicación producía auditorías sueltas: cada operación recibía
 * su veredicto y ninguna recordaba la anterior. Un mentor de verdad no hace eso;
 * se fija en lo que se repite y pide **una sola cosa al mes**.
 *
 * Por eso se guarda el objetivo entero y congelado, no una referencia que haya
 * que recalcular: la frase que se le enseñó al usuario, cuántas veces cometió el
 * error el mes anterior (`baseline`), a cuántas se comprometió (`target`) y sobre
 * cuántas operaciones salió ese número (`sample`). Dentro de seis meses, mirando
 * la lista, se tiene que poder saber de dónde venía cada objetivo.
 *
 * Uno por usuario y mes: el índice único es la regla de producto, no un detalle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('improvement_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // El error al que apunta el objetivo. Nulo si el catálogo cambia:
            // el objetivo cumplido sigue contando aunque el error ya no exista.
            $table->foreignId('mistake_id')->nullable()->constrained()->nullOnDelete();

            // Primer día del mes al que se refiere.
            $table->date('month');

            // La frase tal cual se le enseñó al usuario, congelada.
            $table->string('statement', 300);

            // De cuántas veces se venía y a cuántas se apunta.
            $table->unsignedSmallInteger('baseline');
            $table->unsignedSmallInteger('target');

            // Operaciones del periodo de referencia: la muestra viaja con el objetivo.
            $table->unsignedInteger('sample')->default(0);

            // active | achieved | missed
            $table->string('status', 12)->default('active');

            // Veces que se cometió el error dentro del mes, al cerrarlo.
            $table->unsignedSmallInteger('result')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'month']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('improvement_goals');
    }
};
