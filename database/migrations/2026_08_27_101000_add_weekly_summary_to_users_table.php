<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferencias del resumen semanal.
 *
 * El correo sale el domingo a las 18:00 **hora del usuario**, así que hace falta
 * saber dónde vive. Y el idioma hasta ahora solo existía en la sesión: fuera de
 * una petición HTTP —que es justo donde se envía el correo— no había forma de
 * saber en qué lengua escribirle.
 *
 * `weekly_summary_sent_at` es lo que impide duplicar el envío: el comando corre
 * cada hora para cubrir todos los husos y necesita recordar a quién ya escribió.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable();
            $table->string('locale', 5)->nullable();
            $table->boolean('weekly_summary')->default(true);
            $table->timestamp('weekly_summary_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'locale', 'weekly_summary', 'weekly_summary_sent_at']);
        });
    }
};
