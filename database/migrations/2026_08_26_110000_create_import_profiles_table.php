<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emparejamientos de columnas guardados por el usuario.
 *
 * Quien importa una vez importa muchas: cada mes, el mismo fichero del mismo
 * broker. Guardar el mapeo convierte la segunda importación en dos clics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('preset');                 // clave de App\Services\Import\ImportPreset
            $table->json('mapping');                  // campo canónico => cabecera del fichero
            $table->boolean('pnl_includes_fees')->default(true);
            $table->string('decimal_mode', 10)->default('auto');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_profiles');
    }
};
