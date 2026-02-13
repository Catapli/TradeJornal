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
        Schema::create('session_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_session_id')->constrained()->onDelete('cascade');
            $table->text('note'); // "Me siento FOMO..."
            $table->string('mood')->nullable(); // 'angry', 'happy', etc.
            $table->index(
                ['trading_session_id', 'created_at'],
                'idx_session_notes_fetch'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_notes');
    }
};
