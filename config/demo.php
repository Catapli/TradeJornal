<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Demo pública
    |--------------------------------------------------------------------------
    |
    | La ruta /demo autentica un usuario sembrado de solo lectura para que
    | cualquiera pueda recorrer la aplicación sin registrarse. Mientras la
    | sesión esté marcada como demo, DemoGuard cancela toda escritura de
    | Eloquent, así que el estado que ve el visitante siempre es el que dejó
    | `php artisan demo:refresh`.
    |
    */

    'enabled' => (bool) env('DEMO_ENABLED', true),

    /**
     * Correo del usuario de demostración. Lo crea database/seeders/DemoSeeder.php.
     */
    'email' => env('DEMO_EMAIL', 'demo@tradeforge.app'),

    /**
     * Nombre visible del usuario de demostración.
     */
    'name' => env('DEMO_NAME', 'Trader Demo'),

];
