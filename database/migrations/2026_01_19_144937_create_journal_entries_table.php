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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index(); // La fecha del journal

            // 1. Psicología y Contexto
            $table->string('mood')->nullable(); // Ej: 'happy', 'stressed', 'neutral'

            // 2. Contenido
            $table->longText('content')->nullable(); // Reflexión principal
            $table->longText('plan_for_tomorrow')->nullable(); // Objetivos

            // 3. Categorización
            $table->json('tags')->nullable(); // Ej: ["FOMO", "News", "Perfect Execution"]

            $table->string('pre_market_mood')->nullable();
            $table->text('pre_market_notes')->nullable();
            $table->json('daily_objectives')->nullable(); // Guardará: [{"done": false, "text": "No operar noticias"}]
            $table->decimal('discipline_score', 5, 2)->nullable();

            $table->timestamps();

            // Regla de oro: Un usuario solo puede tener 1 entrada de journal por día
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
