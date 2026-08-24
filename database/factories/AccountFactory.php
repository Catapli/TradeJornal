<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $balance = 100000;

        // program_level_id y program_objective_id son NOT NULL en el esquema
        // (aunque el comentario de la migración diga lo contrario), así que
        // siempre hay que colgar la cuenta de un nivel y una fase.
        $level = ProgramLevel::factory();

        return [
            'user_id' => User::factory(),
            'program_level_id' => $level,
            'program_objective_id' => fn(array $attrs) => ProgramObjective::factory()->create([
                'program_level_id' => $attrs['program_level_id'],
            ])->id,
            'name' => 'Cuenta ' . fake()->word(),
            'type' => 'prop_firm',
            'status' => 'active',
            'currency' => 'USD',
            'initial_balance' => $balance,
            'current_balance' => $balance,
            'current_equity' => $balance,
            'funded_date' => now()->subDays(30),
        ];
    }

    /** Cuenta con un balance inicial concreto. */
    public function balance(float $amount): static
    {
        return $this->state(fn() => [
            'initial_balance' => $amount,
            'current_balance' => $amount,
            'current_equity' => $amount,
        ]);
    }
}
