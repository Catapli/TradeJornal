<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Resumen semanal por correo
    |--------------------------------------------------------------------------
    |
    | El correo sale el domingo por la tarde en la hora del usuario, no la del
    | servidor: un resumen que llega de madrugada no se lee. Para poder cubrir
    | todos los husos, el comando `resumen:semanal` se ejecuta cada hora y en
    | cada pasada escribe solo a quien en ese momento tiene la hora acordada.
    |
    | `day` sigue la convención de Carbon (0 = domingo).
    |
    */

    'weekly_summary' => [

        'enabled' => (bool) env('WEEKLY_SUMMARY_ENABLED', true),

        'day' => (int) env('WEEKLY_SUMMARY_DAY', 0),

        'hour' => (int) env('WEEKLY_SUMMARY_HOUR', 18),

        /**
         * Cuántos usuarios se procesan por tanda. El envío va a la cola, así que
         * esto solo limita la memoria del comando, no el ritmo de salida.
         */
        'chunk' => (int) env('WEEKLY_SUMMARY_CHUNK', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ritual pre-mercado
    |--------------------------------------------------------------------------
    |
    | La tarjeta del panel deja de ofrecerse pasada esta hora local: a las once
    | de la noche ya no hay ninguna sesión que preparar, y proponerlo entonces
    | solo enseña a ignorarla.
    |
    */

    'pre_market' => [

        'until_hour' => (int) env('PRE_MARKET_UNTIL_HOUR', 22),
    ],

];
