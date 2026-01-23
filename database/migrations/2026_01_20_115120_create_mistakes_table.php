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
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained(); // Null = Error global del sistema (ej: FOMO)
            $table->string('name'); // Ej: "FOMO", "Moví el Stop"
            $table->string('color')->default('red'); // Para visualización
            $table->integer('weight')->default(1); // Gravedad (1 = Leve, 3 = Fatal)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};
