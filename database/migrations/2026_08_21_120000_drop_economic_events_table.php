<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se retira el Calendario Económico: nada poblaba la tabla desde que
     * desapareció el comando `calendar:sync`, así que estaba siempre vacía.
     */
    public function up(): void
    {
        Schema::dropIfExists('economic_events');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('economic_events', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->time('time');
            $table->string('currency', 3);
            $table->string('event');
            $table->enum('impact', ['high', 'medium', 'low']);
            $table->string('actual')->nullable();
            $table->string('previous')->nullable();
            $table->string('forecast')->nullable();
            $table->unique(['date', 'time', 'currency', 'event'], 'unique_event_idx');
            $table->index(['date', 'time', 'impact'], 'idx_economic_events_upcoming');
            $table->timestamps();
        });
    }
};
