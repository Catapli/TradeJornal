<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de «ya la he repasado» (Fase 5 · P2).
 *
 * Sin esto no hay forma de distinguir una operación que todavía no se ha mirado
 * de una que se miró y estaba limpia, y la cola de repaso nunca se vaciaría: el
 * usuario tendría que inventarse un error para quitarse la fila de encima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->timestamp('mistakes_reviewed_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn('mistakes_reviewed_at');
        });
    }
};
