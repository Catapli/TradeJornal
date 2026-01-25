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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('trade_asset_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ticket')->unique(); // ID único broker
            $table->enum('direction', ['long', 'short']);
            $table->decimal('entry_price', 12, 5);
            $table->decimal('exit_price', 12, 5);
            $table->decimal('size', 12, 2);
            $table->decimal('pnl', 10, 2);
            $table->unsignedBigInteger('duration_minutes');
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('screenshot')->nullable();
            $table->text('ai_analysis')->nullable();
            $table->string('chart_data_path')->nullable();
            // MAE: Maximum Adverse Excursion (El peor precio/pérdida máxima latente)
            $table->decimal('mae_price', 16, 8)->nullable()->after('pnl');

            // MFE: Maximum Favorable Excursion (El mejor precio/ganancia máxima latente)
            $table->decimal('mfe_price', 16, 8)->nullable()->after('mae_price');


            $table->index(['account_id']);
            $table->index('entry_time');
            $table->index(['exit_time', 'id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
