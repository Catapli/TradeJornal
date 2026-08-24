<?php

namespace Database\Factories;

use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramObjective> */
class ProgramObjectiveFactory extends Factory
{
    protected $model = ProgramObjective::class;

    public function definition(): array
    {
        return [
            'program_level_id' => ProgramLevel::factory(),
            'name' => 'Fase 1',
            'phase_number' => 1,
            'profit_target_percent' => 8,
            'max_daily_loss_percent' => 5,
            'max_total_loss_percent' => 10,
            'min_trading_days' => 4,
            'loss_type' => 'balance_based',
        ];
    }
}
