<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\ProgramLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramLevel> */
class ProgramLevelFactory extends Factory
{
    protected $model = ProgramLevel::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'name' => '100K',
            'currency' => 'USD',
            'size' => 100000,
            'fee' => 500,
        ];
    }
}
