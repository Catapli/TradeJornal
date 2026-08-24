<?php

namespace Database\Factories;

use App\Models\Strategy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Strategy> */
class StrategyFactory extends Factory
{
    protected $model = Strategy::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Estrategia ' . fake()->unique()->word(),
            'timeframe' => fake()->randomElement(['M5', 'M15', 'H1', 'H4']),
            'color' => '#4F46E5',
            'is_main' => false,
        ];
    }
}
