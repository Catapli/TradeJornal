<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\PropFirm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program> */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'prop_firm_id' => PropFirm::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'step_count' => 2,
        ];
    }
}
