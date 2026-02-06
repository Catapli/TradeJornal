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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type')->default('info'); // 'info', 'error', 'warning', 'success'
            $table->string('form')->nullable();
            $table->string('action');
            $table->string('description')->nullable();

            // Campos específicos para errores
            $table->text('exception_message')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('exception_trace')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();

            // Contexto adicional
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // GET, POST, etc

            // Estado del log
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['type', 'created_at']);
            $table->index('resolved');
        });
    }

    /**
     * Reverse the migrations.
    //  */
    public function down(): void
    {
        // Schema::dropIfExists('logs');
    }
};
