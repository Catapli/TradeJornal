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
        Schema::create('program_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_level_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('phase_number', [0, 1, 2, 3])->default(1);
            $table->float('profit_target_percent')->nullable();
            $table->float('max_daily_loss_percent')->nullable();
            $table->float('max_total_loss_percent')->nullable();
            $table->integer('min_trading_days')->nullable();
            $table->enum('loss_type', ['balance_based', 'equity_based', 'relative'])->default('balance_based');
            $table->json('rules_metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_objectives');
    }
};
