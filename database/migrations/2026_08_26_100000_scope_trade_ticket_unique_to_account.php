<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `trades.ticket` era único a nivel global.
 *
 * Mientras la única entrada de datos era el `.exe` de MT5 no molestaba, porque los
 * números de ticket de MetaTrader casi nunca chocan entre servidores. En cuanto se
 * abre el importador de ficheros, sí: dos usuarios que suban el historial de dos
 * brokers distintos pueden traer perfectamente el mismo número, y el segundo
 * import reventaría con una violación de unicidad que no es culpa suya.
 *
 * La unicidad correcta es por cuenta, igual que ya lo era `(account_id, position_id)`.
 *
 * Verificado antes de escribir esta migración: 0 tickets duplicados a nivel global
 * y 0 duplicados por (cuenta, ticket) en la base de datos actual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropUnique('trades_ticket_unique');
            $table->unique(['account_id', 'ticket'], 'trades_account_ticket_unique');
        });
    }

    public function down(): void
    {
        // Ojo al revertir: si para entonces hay tickets repetidos entre cuentas,
        // restaurar el índice global fallará. Habría que desduplicar primero.
        Schema::table('trades', function (Blueprint $table) {
            $table->dropUnique('trades_account_ticket_unique');
            $table->unique('ticket', 'trades_ticket_unique');
        });
    }
};
