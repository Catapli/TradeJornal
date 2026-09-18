<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisión semanal guiada.
 *
 * Una fila por usuario y semana (`week_start` = lunes). Las respuestas se guardan
 * en JSON porque el cuestionario es fijo pero las operaciones revisadas no: cada
 * semana son otras seis, y no tiene sentido una tabla por respuesta.
 *
 * `stats` congela las métricas de esa semana en el momento de cerrarla. Si el
 * usuario reclasifica una operación seis meses después, la comparativa entre
 * semanas seguiría contando lo que se vio entonces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->json('answers')->nullable();
            $table->json('stats')->nullable();
            $table->text('takeaway')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reviews');
    }
};
