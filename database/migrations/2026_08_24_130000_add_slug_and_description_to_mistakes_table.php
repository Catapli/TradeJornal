<?php

use Database\Seeders\MistakesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slug canónico de los errores del catálogo global, indexado por el nombre
     * con el que se sembraron originalmente (mezcla de ES/EN).
     */
    private const LEGACY_SLUGS = [
        'Revenge Trading' => 'revenge_trading',
        'No Stop Loss' => 'no_stop_loss',
        'Mover Stop Loss' => 'moved_stop_loss',
        'FOMO' => 'fomo',
        'Overtrading' => 'overtrading',
        'Contra-Tendencia' => 'counter_trend',
        'Round Trip' => 'round_trip',
        'Salida Prematura' => 'early_exit',
        'Entrada Tarde' => 'late_entry',
        'Lotaje Incorrecto' => 'wrong_size',
    ];

    public function up(): void
    {
        Schema::table('mistakes', function (Blueprint $table) {
            // Sólo lo tienen los errores globales: es la clave de traducción.
            $table->string('slug')->nullable()->after('user_id')->index();
            $table->string('description', 500)->nullable()->after('name');
        });

        foreach (self::LEGACY_SLUGS as $name => $slug) {
            DB::table('mistakes')
                ->whereNull('user_id')
                ->where('name', $name)
                ->update(['slug' => $slug]);
        }

        // El catálogo global es datos, no esquema: lo sembramos aquí para que en
        // producción baste con `php artisan migrate`. El seeder es idempotente
        // (updateOrCreate por slug), así que no duplica los que ya existen.
        (new MistakesSeeder)->run();
    }

    public function down(): void
    {
        // Los errores globales que introdujo esta migración se van con ella,
        // para que un rollback + migrate no los duplique.
        DB::table('mistakes')
            ->whereNull('user_id')
            ->whereNotNull('slug')
            ->whereNotIn('slug', array_values(self::LEGACY_SLUGS))
            ->delete();

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn(['slug', 'description']);
        });
    }
};
