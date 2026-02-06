<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 2. Aquí programamos tu comando de Snapshots para las 00:00
// Schedule::command('accounts:update-snapshots')
//     ->dailyAt('00:00')
//     ->timezone('Europe/Madrid'); // <--- 3. OJO CON ESTO (Leer nota abajo)

// Ejecutar cada 6 horas para actualizar datos "Actual" y nuevas previsiones
Schedule::command('calendar:sync')->everySixHours();
