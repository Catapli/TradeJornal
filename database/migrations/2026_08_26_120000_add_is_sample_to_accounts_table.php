<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca la cuenta de ejemplo que un usuario puede crearse para trastear.
 *
 * Va en columna propia y no reutilizando `type = 'demo'`: ese valor significa
 * «cuenta demo del broker», que es una cuenta real con dinero ficticio, no datos
 * inventados por nosotros. Mezclarlos dejaría al usuario sin forma de saber qué
 * operaciones son suyas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_sample')->default(false)->after('type');
            $table->index(['user_id', 'is_sample'], 'idx_accounts_user_sample');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('idx_accounts_user_sample');
            $table->dropColumn('is_sample');
        });
    }
};
