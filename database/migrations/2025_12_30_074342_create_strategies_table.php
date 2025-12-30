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
        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Breakout H1", "Scalping 5m"
            $table->string('description')->nullable();
            $table->string('timeframe'); // "M5", "H1", "D1"
            $table->decimal('winrate_pct', 5, 2)->default(0); // Winrate
            $table->decimal('avg_rr', 4, 2)->default(0); // AVG R:R (Ratio)
            $table->integer('trades_count')->default(0); // Cuenta de Trades
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategies');
    }
};
