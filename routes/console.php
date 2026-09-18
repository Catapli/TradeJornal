<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Nada de esto se ejecuta solo: el servidor necesita una entrada de cron que
| llame cada minuto a `php artisan schedule:run`, y un proceso permanente de
| `php artisan queue:work` que vacíe la tabla `jobs`. Ambas líneas están en el
| README, en «Puesta en marcha del scheduler».
|
*/

// Resumen semanal (R1). Cada hora, no una vez a la semana: la cita es a la hora
// local de cada usuario, así que en cada pasada solo escribe a quien en ese
// momento tiene su domingo a las 18:00. `weekly_summary_sent_at` evita repetir.
Schedule::command('resumen:semanal')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// La demo pública se regenera de madrugada. Quedó pendiente de la Fase 1 por no
// haber scheduler: hasta ahora, cualquier estado raro duraba hasta que alguien
// se acordaba de lanzarlo a mano.
Schedule::command('demo:refresh --force')
    ->dailyAt('04:00')
    ->when(fn (): bool => (bool) config('demo.enabled'));
