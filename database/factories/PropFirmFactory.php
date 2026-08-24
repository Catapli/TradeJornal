<?php

namespace Database\Factories;

use App\Models\PropFirm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropFirm> */
class PropFirmFactory extends Factory
{
    protected $model = PropFirm::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'website' => 'https://' . fake()->domainName(),
            'server' => fake()->word() . '-Server',
        ];
    }
}
