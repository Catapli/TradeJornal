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
        Schema::create('economic_events', function (Blueprint $table) {
            $table->id();
            // No vinculamos a user_id obligatoriamente porque las noticias son globales,
            // pero lo vinculamos al usuario para que cada uno guarde "sus" noticias relevantes.
            $table->date('date')->index();
            $table->time('time');
            $table->string('currency', 3); // USD, EUR
            $table->string('event'); // "CPI YoY", "Non-Farm Payrolls"
            $table->enum('impact', ['high', 'medium', 'low']); // Rojo, Naranja, Amarillo
            $table->string('actual')->nullable(); // Dato real (opcional)
            $table->string('previous')->nullable(); // Dato previo (opcional)
            $table->string('forecast')->nullable(); // Previsión (opcional)
            $table->unique(['date', 'time', 'currency', 'event'], 'unique_event_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('economic_events');
    }
};
