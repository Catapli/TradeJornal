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

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        //! DATOS PRODUCCION
        //* USUARIO ADMIN
        User::factory()->create([
            'name' => 'Jordi',
            'email' => 'jordi@gmail.com',
            'google_id' => '',
        ]);

        //? DATOS FALSOS


        //* Usuarios
        $users = User::factory(5)->create();

        // * Cuentas

        Account::create([
            'user_id' => 1,
            'name' => 'Prueba Neomma',
            'broker' => 'Neommaa',
            'initial_balance' => 5000,
            'current_balance' => 4985,
            'max_balance' => 800,
            'status' => 'phase_1',
            'max_daily_loss' => 8,
            'max_total_loss' => 6,
        ]);

        Account::create([
            'user_id' => 1,
            'name' => 'Prueba Neomma 2',
            'broker' => 'Neommaa',
            'initial_balance' => 5000,
            'current_balance' => 4985,
            'max_balance' => 800,
            'status' => 'phase_2',
            'max_daily_loss' => 8,
            'max_total_loss' => 6,
        ]);


        Account::create([
            'user_id' => 1,
            'name' => 'Prueba Neomma 3',
            'broker' => 'Neommaa',
            'initial_balance' => 10000,
            'current_balance' => 12000,
            'max_balance' => 800,
            'status' => 'active',
            'max_daily_loss' => 8,
            'max_total_loss' => 6,
        ]);

        Account::create([
            'user_id' => 1,
            'name' => 'Prueba Neomma 4',
            'broker' => 'Neommaa',
            'initial_balance' => 10000,
            'current_balance' => 12000,
            'max_balance' => 800,
            'status' => 'burned',
            'max_daily_loss' => 8,
            'max_total_loss' => 6,
        ]);

        TradeAsset::create([
            'symbol' => 'EURUSD',
            'name' => 'Euro Dólar',
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

        Trade::factory()->count(300)->create(); // 150 por cuenta x4
    }
}
