<?php

namespace Database\Seeders;

use App\Models\Mistake;
use Illuminate\Database\Seeder;

class MistakesSeeder extends Seeder
{
    /**
     * Catálogo global. El nombre/descripción visibles salen de lang/{locale}/mistakes.php;
     * la columna 'name' queda sólo como respaldo si falta la traducción.
     */
    public function run(): void
    {
        $defaults = [
            // GRAVES (Restan mucho a la disciplina)
            ['slug' => 'revenge_trading', 'color' => 'red',     'weight' => 3],
            ['slug' => 'no_stop_loss',    'color' => 'red',     'weight' => 3],
            ['slug' => 'moved_stop_loss', 'color' => 'orange',  'weight' => 3],
            ['slug' => 'averaging_down',  'color' => 'red',     'weight' => 3],
            ['slug' => 'excessive_risk',  'color' => 'rose',    'weight' => 3],

            // MEDIOS
            ['slug' => 'fomo',            'color' => 'rose',    'weight' => 2],
            ['slug' => 'overtrading',     'color' => 'amber',   'weight' => 2],
            ['slug' => 'counter_trend',   'color' => 'yellow',  'weight' => 2],
            ['slug' => 'round_trip',      'color' => 'orange',  'weight' => 2],
            ['slug' => 'held_loser',      'color' => 'orange',  'weight' => 2],
            ['slug' => 'no_setup',        'color' => 'amber',   'weight' => 2],
            ['slug' => 'news_trading',    'color' => 'yellow',  'weight' => 2],

            // LEVES / TÉCNICOS
            ['slug' => 'early_exit',      'color' => 'blue',    'weight' => 1],
            ['slug' => 'late_entry',      'color' => 'cyan',    'weight' => 1],
            ['slug' => 'wrong_size',      'color' => 'purple',  'weight' => 1],
        ];

        foreach ($defaults as $error) {
            $mistake = Mistake::firstOrNew(['slug' => $error['slug'], 'user_id' => null]);

            // 'name' sólo se rellena al crear: es el respaldo histórico y lo que
            // usa la migración para emparejar filas antiguas por nombre.
            $mistake->name ??= __("mistakes.{$error['slug']}.name", [], 'es');
            $mistake->color = $error['color'];
            $mistake->weight = $error['weight'];
            $mistake->save();
        }
    }
}
