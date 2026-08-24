<?php

namespace Database\Factories;

use App\Models\TradeAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TradeAsset> */
class TradeAssetFactory extends Factory
{
    protected $model = TradeAsset::class;

    public function definition(): array
    {
        return [
            'symbol' => fake()->unique()->randomElement(['EURUSD', 'GBPUSD', 'USDJPY', 'XAUUSD', 'NAS100', 'BTCUSD']),
            'name' => fake()->words(2, true),
            'category' => 'Forex',
        ];
    }
}
