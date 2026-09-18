<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `Mt5SyncController::sync()` usa (account_id, position_id) como clave de
 * `updateOrCreate`, pero el índice que existía sobre ese par NO era único. Un reintento
 * del .exe o dos syncs solapados podían crear dos filas para la misma posición, y en un
 * diario de trading un trade duplicado corrompe todas las métricas: PnL, winrate,
 * profit factor y —lo importante— el drawdown contra el que se miden los objetivos.
 *
 * Hasta ahora lo tapaba de rebote el índice único de `ticket`, pero esa columna es
 * nullable y depende de que el broker no reutilice tickets.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Por si ya hubiera duplicados: se conserva la fila más reciente de cada
        // (account_id, position_id), que es la que el último sync dejó actualizada.
        DB::statement('
            DELETE FROM trades t
            USING trades mas_nueva
            WHERE t.account_id = mas_nueva.account_id
              AND t.position_id = mas_nueva.position_id
              AND t.position_id IS NOT NULL
              AND t.id < mas_nueva.id
        ');

        Schema::table('trades', function (Blueprint $table) {
            // El índice no único sobre el mismo par pasa a sobrar: el único ya sirve
            // para las búsquedas que lo usaban.
            $table->dropIndex(['account_id', 'position_id']);
            $table->unique(['account_id', 'position_id'], 'trades_account_position_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropUnique('trades_account_position_unique');
            $table->index(['account_id', 'position_id']);
        });
    }
};
