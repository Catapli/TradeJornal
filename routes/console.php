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
// Schedule::command('calendar:sync --range=week --source=merged')
//     ->weeklyOn(1, '06:00')
//     ->timezone('Europe/Madrid')
//     ->withoutOverlapping()
//     ->runInBackground();

// // Lunes-Viernes, cada 30min entre 7am y 22pm:
// // Refresca el "actual" de los eventos que han ido saliendo durante el día
// Schedule::command('calendar:sync --range=today --source=merged')
//     ->everyThirtyMinutes()
//     ->timezone('Europe/Madrid')
//     ->weekdays()
//     ->between('07:00', '22:00')
//     ->withoutOverlapping()
//     ->runInBackground();

// // Cierre diario 23:30 — sync final del día para cerrar actuals rezagados
// Schedule::command('calendar:sync --range=today --source=merged')
//     ->dailyAt('23:30')
//     ->timezone('Europe/Madrid')
//     ->withoutOverlapping()
//     ->runInBackground();
