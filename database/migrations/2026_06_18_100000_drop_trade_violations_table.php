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
        Schema::dropIfExists('trade_violations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('trade_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key');
            $table->text('message');
            $table->timestamps();
        });
    }
};
