<?php

namespace App\Jobs;

use App\Models\Strategy;
use App\Actions\Strategy\RecalculateStrategyStats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateStrategyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Strategy $strategy) {}

    public function handle(): void
    {
        app(RecalculateStrategyStats::class)->execute($this->strategy);
    }
}
