<?php

namespace App\Console\Commands;

use App\Models\Strategy;
use App\Actions\Strategy\RecalculateStrategyStats;
use Illuminate\Console\Command;

class RecalculateAllStrategyStats extends Command
{
    protected $signature = 'strategies:recalculate-stats {--user_id=}';
    protected $description = 'Recalcula las stats de todas las estrategias (o de un usuario específico)';

    public function handle()
    {
        $query = Strategy::query();

        if ($userId = $this->option('user_id')) {
            $query->where('user_id', $userId);
        }

        $strategies = $query->get();
        $bar = $this->output->createProgressBar($strategies->count());

        $this->info('Recalculando stats de ' . $strategies->count() . ' estrategias...');

        foreach ($strategies as $strategy) {
            app(RecalculateStrategyStats::class)->execute($strategy);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Stats recalculadas correctamente.');
    }
}
