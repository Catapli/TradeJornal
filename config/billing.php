<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Prueba de PRO
    |--------------------------------------------------------------------------
    |
    | Prueba sin tarjeta: se apoya en `users.trial_ends_at`, la columna que ya
    | trae Cashier, y no crea nada en Stripe. Un diario solo demuestra su valor
    | cuando tiene datos dentro, así que cerrar el grifo el primer día garantiza
    | que nadie llegue a sentirlo.
    |
    | Poner 0 desactiva la prueba para las altas nuevas.
    |
    */

    'trial_days' => (int) env('TRIAL_DAYS', 14),

    /**
     * Días antes del final en los que se empieza a avisar.
     */
    'trial_warning_days' => (int) env('TRIAL_WARNING_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Límites por plan
    |--------------------------------------------------------------------------
    |
    | `null` significa sin límite. El tope de cuentas estaba escrito a fuego en
    | AccountPage y se aplicaba a todo el mundo, suscriptores incluidos, mientras
    | la página de precios anunciaba «Cuentas Ilimitadas».
    |
    | Las cuentas quemadas no cuentan: son historia, no cuentas en uso.
    |
    */

    'accounts' => [
        'free' => (int) env('ACCOUNT_LIMIT_FREE', 3),
        'pro' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recuperación tras quemar una cuenta
    |--------------------------------------------------------------------------
    |
    | Código promocional de Stripe que se ofrece junto al informe de qué salió
    | mal. Si se deja vacío, la tarjeta se muestra igual pero sin prometer ningún
    | descuento: nunca se anuncia una rebaja que el checkout no vaya a aplicar.
    |
    */

    'recovery_coupon' => env('RECOVERY_COUPON'),

];
