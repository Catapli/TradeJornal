<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo cerró el usuario el resumen de la cuenta quemada.
 *
 * Quemar una cuenta es el momento de máxima motivación para cambiar de hábitos,
 * y hasta ahora la aplicación lo dejaba pasar en silencio. Se le enseña qué
 * salió mal, pero una sola vez: sin esta columna la tarjeta se volvería un
 * reproche permanente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('recovery_dismissed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('recovery_dismissed_at');
        });
    }
};
