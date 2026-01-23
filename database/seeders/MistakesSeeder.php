<?php

namespace Database\Seeders;

use App\Models\Mistake;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MistakesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // GRAVES (Restan mucho a la disciplina)
            [
                'name' => 'Revenge Trading',
                'color' => 'red',
                'weight' => 3
            ],
            [
                'name' => 'No Stop Loss',
                'color' => 'red',
                'weight' => 3
            ],
            [
                'name' => 'Mover Stop Loss',
                'color' => 'orange',
                'weight' => 3
            ],

            // MEDIOS
            [
                'name' => 'FOMO',
                'color' => 'rose',
                'weight' => 2
            ],
            [
                'name' => 'Overtrading',
                'color' => 'amber',
                'weight' => 2
            ],
            [
                'name' => 'Contra-Tendencia',
                'color' => 'yellow',
                'weight' => 2
            ],
            [
                'name' => 'Round Trip', // Ganador que acaba en perdedor
                'color' => 'orange',
                'weight' => 2 // Es un error medio/grave de gestión
            ],

            // LEVES / TÉCNICOS
            [
                'name' => 'Salida Prematura',
                'color' => 'blue',
                'weight' => 1
            ],
            [
                'name' => 'Entrada Tarde',
                'color' => 'cyan',
                'weight' => 1
            ],
            [
                'name' => 'Lotaje Incorrecto',
                'color' => 'purple',
                'weight' => 1
            ],
        ];

        foreach ($defaults as $error) {
            Mistake::firstOrCreate(
                ['name' => $error['name']], // Evita duplicados si lo corres 2 veces
                [
                    'user_id' => null, // NULL significa "Global para todos"
                    'color' => $error['color'],
                    'weight' => $error['weight']
                ]
            );
        }
    }
}
