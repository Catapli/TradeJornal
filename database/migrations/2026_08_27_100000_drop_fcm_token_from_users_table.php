<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firebase se retira del proyecto.
 *
 * La columna se creó para las notificaciones push de Firebase Cloud Messaging,
 * pero nunca llegó a escribirse ni leerse desde ningún sitio: la integración
 * quedó a medias y el paquete `kreait/laravel-firebase` se ha eliminado. Guardar
 * un identificador de dispositivo que nadie usa es dato personal sin motivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 250)->nullable();
            }
        });
    }
};
