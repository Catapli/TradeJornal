<?php

declare(strict_types=1);

return [

    'title' => 'Mentor',
    'lead' => 'Lo que se repite en tus últimos :months meses, y una sola cosa que cambiar este mes.',

    // ── Perfil acumulado ─────────────────────────────────────────────────────
    'profile_title' => 'Tu perfil',
    'coverage' => ':reviewed de :trades operaciones repasadas (:coverage %)',
    'accounts_mixed' => 'sobre :accounts cuentas, con importes sin normalizar',
    'coverage_hint' => 'El perfil solo puede ver lo que has marcado. Cuanto menos repases, menos sabe.',

    'not_enough_title' => 'Todavía no hay suficiente para hablar de patrones',
    'not_enough_text' => 'Llevas :marks marcas de error y hacen falta :min. Te faltan :missing.',
    'not_enough_why' => 'Con cuatro marcas, «tu error recurrente» es una casualidad con nombre propio. Y de aquí sale el objetivo al que te vas a comprometer un mes entero.',
    'not_enough_cta' => 'Ir a la cola de repaso',

    'mistake_count' => ':count veces',
    'mistake_cost' => 'te ha costado :amount',
    'this_month' => 'Este mes: :count',
    'trend_down' => 'A mejor',
    'trend_up' => 'A peor',
    'trend_flat' => 'Igual',
    'trend_hint' => 'Comparado por operaciones, no por número suelto: un mes con la mitad de operaciones tiene menos marcas sin que hayas mejorado.',

    // ── Objetivo del mes ─────────────────────────────────────────────────────
    'goal_title' => 'El objetivo de este mes',
    'goal_lead' => 'Una sola cosa. Cinco cambios a la vez no son ninguno.',

    'goal' => [
        'statement' => 'Bajar de :baseline a :target operaciones con «:name».',
        'propose_intro' => 'El mes pasado cometiste este error :baseline veces en :sample operaciones.',
        'set' => 'Fijar este objetivo',
        'confirm_title' => '¿Te comprometes?',
        'confirm_text' => 'Una vez fijado no se puede cambiar ni borrar hasta que acabe el mes. Es lo que hace que valga algo.',
        'confirm_yes' => 'Sí, este es mi objetivo',
        'confirm_no' => 'Ahora no',
        'saved' => 'Objetivo del mes fijado. Nos vemos el día 1.',
        'none_title' => 'Este mes no hay objetivo que proponer',
        'none_text' => 'Hace falta un mes anterior con al menos :min operaciones y algún error repetido en él. Sin eso, cualquier cifra sería inventada.',
    ],

    'progress_so_far' => 'Llevas :count de :target',
    'progress_days' => 'Quedan :days días',
    'progress_last_day' => 'Último día del mes',
    'progress_ok' => 'Vas dentro',
    'progress_blown' => 'Objetivo roto: llevas :count y el límite era :target',
    'progress_blown_hint' => 'Se dice ahora y no el día 30. Enterarse a fin de mes de algo que se rompió el día 4 no corrige nada.',

    // ── Histórico ────────────────────────────────────────────────────────────
    'history_title' => 'Meses anteriores',
    'history_empty' => 'Todavía no has cerrado ningún mes.',
    'history_achieved' => 'Cumplido',
    'history_missed' => 'No cumplido',
    'history_result' => ':result de :target',
    'history_sample' => 'sobre :sample operaciones',
];
