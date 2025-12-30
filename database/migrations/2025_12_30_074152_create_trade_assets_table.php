<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trade_assets', function (Blueprint $table) {
            $table->id();
            $table->string('symbol'); // "EURUSD", "BTCUSD", "NAS100"
            $table->string('name'); // "Euro Dólar", "Bitcoin"
            $table->string('category'); // "Forex", "Crypto", "Indices"
            $table->string('broker_symbol')->nullable(); // Variación broker
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_assets');
    }
};
