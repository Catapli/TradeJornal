<?php

namespace Database\Seeders;

use App\Models\ProgramObjective;
use App\Models\PropFirm;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropFirmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //! =================================================================
        // * Neomaaa
        // ---------------------------------------------------------
        // 1. CREAR LA EMPRESA (Neomaaa)
        // ---------------------------------------------------------
        $neomaaa = PropFirm::create([
            'name'    => 'Neomaaa',
            'slug'    => 'neomaaa',
            'website' => 'https://neomaaa.com/es/',
            // 'logo' => 'path/to/logo.png'
        ]);

        // =================================================================
        //* ORIGIN - 1 STEP
        // =================================================================

        $origin1Step = $neomaaa->programs()->create([
            'name'       => 'Origin (1 Step)',
            'slug'       => 'neomaaa-origin-1-step',
            'step_count' => 1,
        ]);

        // Array con todos los balances disponibles en la imagen
        $balances = [5000, 10000, 25000, 50000, 100000, 150000];
        $balancesFunded = [1500, 2500, 5000, 10000, 25000, 50000, 100000];

        foreach ($balances as $size) {
            // Crear el Nivel (Producto)
            $level = $origin1Step->levels()->create([
                'name'     => number_format($size / 1000) . 'k USD',
                'currency' => 'USD',
                'size'     => $size,
                'fee'      => 0, // Aquí pondrías el precio real si lo quieres trackear
            ]);

            // Definir las reglas (Datos aproximados estándar, ajusta con las reglas reales de Neoma)

            // FASE 1 (Única Fase)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 1,
                'name'                   => 'Phase 1',
                'profit_target_percent'  => 10.00, // Generalmente 8%
                'max_daily_loss_percent' => 4.00,
                'max_total_loss_percent' => 7.00, // O 8%, depende de "Origin"
                'min_trading_days'       => 4,
                'loss_type'              => 'balance_based', // Importante verificar si es Balance o Equity
            ]);

            // LIVE (Funded)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 0, // 0 = Live
                'name'                   => 'Live Account',
                'profit_target_percent'  => null,
                'max_daily_loss_percent' => 4.00,
                'max_total_loss_percent' => 7.00,
                'min_trading_days'       => 4,
                'rules_metadata'         => json_encode([
                    'profit_split' => 80, // El default es 80%
                    'platforms' => ['mt5', 'ctrader', 'tradelocker'] // Guardamos info útil
                ]),
            ]);
        }

        // =================================================================
        //* ORIGIN - 2 STEP
        // =================================================================

        $origin2Step = $neomaaa->programs()->create([
            'name'       => 'Origin (2 Step)',
            'slug'       => 'neomaaa-origin-2-step',
            'step_count' => 2,
        ]);

        foreach ($balances as $size) {
            // Crear el Nivel (Producto)
            $level = $origin2Step->levels()->create([
                'name'     => number_format($size / 1000) . 'k USD',
                'currency' => 'USD',
                'size'     => $size,
                'fee'      => 0, // Aquí pondrías el precio real si lo quieres trackear
            ]);

            // Definir las reglas (Datos aproximados estándar, ajusta con las reglas reales de Neoma)

            // FASE 1 
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 1,
                'name'                   => 'Phase 1',
                'profit_target_percent'  => 6.00, // Generalmente 8%
                'max_daily_loss_percent' => 4.00,
                'max_total_loss_percent' => 8.00, // O 8%, depende de "Origin"
                'min_trading_days'       => 4,
                'loss_type'              => 'balance_based', // Importante verificar si es Balance o Equity
            ]);

            // FASE 2 
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 2,
                'name'                   => 'Phase 2',
                'profit_target_percent'  => 6.00, // Generalmente 8%
                'max_daily_loss_percent' => 4.00,
                'max_total_loss_percent' => 8.00, // O 8%, depende de "Origin"
                'min_trading_days'       => 4,
                'loss_type'              => 'balance_based', // Importante verificar si es Balance o Equity
            ]);

            // LIVE (Funded)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 0, // 0 = Live
                'name'                   => 'Live Account',
                'profit_target_percent'  => null,
                'max_daily_loss_percent' => 4.00,
                'max_total_loss_percent' => 8.00,
                'min_trading_days'       => 5,
                'rules_metadata'         => json_encode([
                    'profit_split' => 80, // El default es 80%
                    'platforms' => ['mt5', 'ctrader', 'tradelocker'] // Guardamos info útil
                ]),
            ]);
        }


        // =================================================================
        // * ORIGIN - Instant Funded
        // =================================================================

        $originFunded = $neomaaa->programs()->create([
            'name'       => 'Origin (Instant Funded)',
            'slug'       => 'neomaaa-origin-instant-funded',
            'step_count' => 0,
        ]);

        foreach ($balancesFunded as $size) {
            // Crear el Nivel (Producto)
            $level = $originFunded->levels()->create([
                'name'     => number_format($size / 1000) . 'k USD',
                'currency' => 'USD',
                'size'     => $size,
                'fee'      => 0, // Aquí pondrías el precio real si lo quieres trackear
            ]);

            // Definir las reglas (Datos aproximados estándar, ajusta con las reglas reales de Neoma)

            // LIVE (Funded)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 0, // 0 = Live
                'name'                   => 'Live Account',
                'profit_target_percent'  => null,
                'max_daily_loss_percent' => 3.00,
                'max_total_loss_percent' => 6.00,
                'min_trading_days'       => 5,
                'rules_metadata'         => json_encode([
                    'profit_split' => 70, // El default es 80%
                    'platforms' => ['mt5', 'ctrader', 'tradelocker'] // Guardamos info útil
                ]),
            ]);
        }


        // =================================================================
        //* PRIME - 1 STEP
        // =================================================================

        $prime1Step = $neomaaa->programs()->create([
            'name'       => 'Prime (1 Step)',
            'slug'       => 'neomaaa-prime-1-step',
            'step_count' => 1,
        ]);

        foreach ($balances as $size) {
            // Crear el Nivel (Producto)
            $level = $prime1Step->levels()->create([
                'name'     => number_format($size / 1000) . 'k USD',
                'currency' => 'USD',
                'size'     => $size,
                'fee'      => 0, // Aquí pondrías el precio real si lo quieres trackear
            ]);

            // Definir las reglas (Datos aproximados estándar, ajusta con las reglas reales de Neoma)

            // FASE 1 (Fase Única)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 1,
                'name'                   => 'Phase 1',
                'profit_target_percent'  => 10.00, // Generalmente 8%
                'max_daily_loss_percent' => 3.00,
                'max_total_loss_percent' => 5.00, // O 8%, depende de "Origin"
                'min_trading_days'       => 3,
                'loss_type'              => 'balance_based', // Importante verificar si es Balance o Equity
            ]);


            // LIVE (Funded)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 0, // 0 = Live
                'name'                   => 'Live Account',
                'profit_target_percent'  => null,
                'max_daily_loss_percent' => 3.00,
                'max_total_loss_percent' => 5.00,
                'min_trading_days'       => 5,
                'rules_metadata'         => json_encode([
                    'profit_split' => 80, // El default es 80%
                    'platforms' => ['mt5', 'ctrader', 'tradelocker'] // Guardamos info útil
                ]),
            ]);
        }

        // =================================================================
        //* PRIME - 2 STEP
        // =================================================================

        $prime2Step = $neomaaa->programs()->create([
            'name'       => 'Prime (2 Step)',
            'slug'       => 'neomaaa-prime-2-step',
            'step_count' => 2,
        ]);

        foreach ($balances as $size) {
            // Crear el Nivel (Producto)
            $level = $prime2Step->levels()->create([
                'name'     => number_format($size / 1000) . 'k USD',
                'currency' => 'USD',
                'size'     => $size,
                'fee'      => 0, // Aquí pondrías el precio real si lo quieres trackear
            ]);

            // Definir las reglas (Datos aproximados estándar, ajusta con las reglas reales de Neoma)

            // FASE 1
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 1,
                'name'                   => 'Phase 1',
                'profit_target_percent'  => 8.00, // Generalmente 8%
                'max_daily_loss_percent' => 5.00,
                'max_total_loss_percent' => 8.00, // O 8%, depende de "Origin"
                'min_trading_days'       => 4,
                'loss_type'              => 'balance_based', // Importante verificar si es Balance o Equity
            ]);

            // FASE 2
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 2,
                'name'                   => 'Phase 2',
                'profit_target_percent'  => 5.00, // Generalmente 8%
                'max_daily_loss_percent' => 5.00,
                'max_total_loss_percent' => 8.00, // O 8%, depende de "Origin"
                'min_trading_days'       => 4,
                'loss_type'              => 'balance_based', // Importante verificar si es Balance o Equity
            ]);


            // LIVE (Funded)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 0, // 0 = Live
                'name'                   => 'Live Account',
                'profit_target_percent'  => null,
                'max_daily_loss_percent' => 3.00,
                'max_total_loss_percent' => 5.00,
                'min_trading_days'       => 5,
                'rules_metadata'         => json_encode([
                    'profit_split' => 80, // El default es 80%
                    'platforms' => ['mt5', 'ctrader', 'tradelocker'] // Guardamos info útil
                ]),
            ]);
        }

        // =================================================================
        // * PRIME - Instant Funded
        // =================================================================

        $primeFunded = $neomaaa->programs()->create([
            'name'       => 'Prime (Instant Funded)',
            'slug'       => 'neomaaa-prime-instant-funded',
            'step_count' => 0,
        ]);

        foreach ($balancesFunded as $size) {
            // Crear el Nivel (Producto)
            $level = $primeFunded->levels()->create([
                'name'     => number_format($size / 1000) . 'k USD',
                'currency' => 'USD',
                'size'     => $size,
                'fee'      => 0, // Aquí pondrías el precio real si lo quieres trackear
            ]);

            // Definir las reglas (Datos aproximados estándar, ajusta con las reglas reales de Neoma)

            // LIVE (Funded)
            ProgramObjective::create([
                'program_level_id'       => $level->id,
                'phase_number'           => 0, // 0 = Live
                'name'                   => 'Live Account',
                'profit_target_percent'  => null,
                'max_daily_loss_percent' => 3.00,
                'max_total_loss_percent' => 4.00,
                'min_trading_days'       => 5,
                'rules_metadata'         => json_encode([
                    'profit_split' => 80, // El default es 80%
                    'platforms' => ['mt5', 'ctrader', 'tradelocker'] // Guardamos info útil
                ]),
            ]);
        }
    }
}
