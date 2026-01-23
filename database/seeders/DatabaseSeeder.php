<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Alert;
use App\Models\Camera;
use App\Models\CollaborativeList;
use App\Models\File;
use App\Models\InterestList;
use App\Models\Lists;
use App\Models\Log;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use App\Models\Reason;
use App\Models\Strategy;
use App\Models\Town;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\Traffic;
use App\Models\User;
use App\Models\Zone;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([PropFirmsSeeder::class, MistakesSeeder::class]);


        //! DATOS PRODUCCION
        //* USUARIO ADMIN
        User::factory()->create([
            'name' => 'Jordi',
            'email' => 'jordi@gmail.com',
            'google_id' => '',
            'sync_token' => Str::random(32)
        ]);

        //? DATOS FALSOS


        //* Usuarios
        $users = User::factory(5)->create();

        // * Cuentas

        $fivekaccountPrime = ProgramLevel::where('program_id', 5)->where('size', 5000)->where('currency', 'USD')->first();
        $fiveAccountObjective = ProgramObjective::where('program_level_id', $fivekaccountPrime->id)->where('phase_number', "1")->first();

        $tenkaccountPrime = ProgramLevel::where('program_id', 5)->where('size', 10000)->where('currency', 'USD')->first();
        $tenAccountObjective = ProgramObjective::where('program_level_id', $tenkaccountPrime->id)->where('phase_number', "1")->first();



        // Account::create([
        //     'user_id' => 1,
        //     'name' => 'Prueba Neomma 2',
        //     'broker_name' => 'Neommaa',
        //     'initial_balance' => 5000,
        //     'current_balance' => 5000,
        //     'status' => 'active',
        //     'mt5_login' => '7730373',
        //     'mt5_password' => encrypt('x83Vgb#mUEsF'),
        //     'mt5_server' => 'Neomaaa-Live',
        //     'program_level_id' => $fivekaccountPrime->id,
        //     'program_objective_id' => $fiveAccountObjective->id,
        //     'sync' => true
        // ]);


        Account::create([
            'user_id' => 1,
            'name' => 'Prueba Neomma 3',
            'broker_name' => 'Neommaa',
            'initial_balance' => 10000,
            'current_balance' => 10000,
            'status' => 'active',
            'mt5_login' => '7733662',
            'mt5_password' => encrypt('j0CMHxCmj#@H'),
            'mt5_server' => 'Neomaaa-Live',
            'program_level_id' => $tenkaccountPrime->id,
            'program_objective_id' => $tenAccountObjective->id,
            "sync" => true,
        ]);


        TradeAsset::create([
            'symbol' => 'EURUSD',
            'name' => 'Euro Dolar',
            'category' => 'Forex',
        ]);

        TradeAsset::create([
            'symbol' => 'BTCUSDT',
            'name' => 'Bitcoin',
            'category' => 'Crypto',
        ]);

        Strategy::create([
            'user_id' => 1,
            'name' => 'POIs/Traps',
            'description' => 'Tomar como referencia los OB y diferenciarlos entre POI y trap',
            'timeframe' => 'M5',
        ]);

        // Trade::factory()->count(50)->create(); // 150 por cuenta x4
    }
}
