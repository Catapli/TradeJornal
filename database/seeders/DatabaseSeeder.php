<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Camera;
use App\Models\CollaborativeList;
use App\Models\File;
use App\Models\InterestList;
use App\Models\Lists;
use App\Models\Log;
use App\Models\Reason;
use App\Models\Town;
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
        //? DATOS FALSOS


        //* Usuarios
        $users = User::factory(5)->create();




        //! DATOS PRODUCCION
        //* USUARIO ADMIN
        User::factory()->create([
            'name' => 'Jordi',
            'email' => 'jordi@gmail.com',
            'google_id' => '',
        ]);
    }
}
