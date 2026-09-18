<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas nacidas de un hallazgo del Laboratorio (Fase 5 · P7).
 *
 * No basta con guardar el texto de la regla: dentro de un mes, «no operar después
 * de las 13:00» sin nada más es una manía. Con la procedencia al lado —de qué
 * hallazgo salió, con qué cifra y sobre cuántas operaciones— es una decisión que
 * se puede revisar, y revocar si los datos cambian.
 *
 * `account_id` nulo = la regla vale para toda la operativa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->cascadeOnDelete();

            // Qué clase de regla es: decide si el semáforo puede vigilarla sola.
            $table->string('kind', 24);

            // La regla en palabras, tal y como sale en el checklist.
            $table->string('text', 255);

            // Parámetros: {"limit":3} · {"from":"09:00","to":"13:00"} · {"weekday":4}
            $table->json('config')->nullable();

            // Procedencia
            $table->string('source_key', 40);
            $table->string('source_summary', 255);
            $table->unsignedInteger('source_sample')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_rules');
    }
};
