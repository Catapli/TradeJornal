<?php

return [

    // ── Hallazgos del Laboratorio ────────────────────────────────────────────
    'findings' => [
        'title' => 'Lo que tus datos te están diciendo',
        'lead' => 'Cada frase sale de tus operaciones y trae la cifra y la muestra. Conviértela en regla y la verás antes de operar.',
        'empty' => 'Todavía no hay nada lo bastante claro como para convertirlo en regla. Hacen falta al menos :min operaciones en el periodo que estés mirando.',
        'sample' => 'sobre :count operaciones',
        'adopt' => 'Convertir en regla',
        'adopted' => 'Ya es regla tuya',
        'cost_label' => 'Te cuesta :amount',
    ],

    // Cada hallazgo, dicho en una frase que se pueda cumplir.
    'finding' => [
        'mistake_cost' => ':name te ha costado :amount en :count operaciones.',
        'mistake_cost_rule' => 'Antes de entrar, comprobar que no estoy cometiendo: :name',

        'time_of_day' => 'Operando en la franja de :slot has perdido :amount en :count operaciones.',
        'time_of_day_rule' => 'Operar solo entre las :from y las :to',
        'time_of_day_rule_soft' => 'No operar en la franja de :slot',

        'trades_per_day' => 'A partir de tu operación número :position, el día se te tuerce: :amount perdidos en :count operaciones.',
        'trades_per_day_rule' => 'Máximo :limit operaciones al día',

        'weekday' => 'Los :weekday has perdido :amount en :count operaciones.',
        'weekday_rule' => 'No operar los :weekday',
    ],

    'weekdays' => [
        0 => 'domingos',
        1 => 'lunes',
        2 => 'martes',
        3 => 'miércoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sábados',
    ],

    'slots' => [
        'early' => 'la madrugada (00-07)',
        'morning' => 'la mañana (08-12)',
        'afternoon' => 'la tarde (13-17)',
        'evening' => 'la noche (18-23)',
    ],

    // ── Al adoptar ───────────────────────────────────────────────────────────
    'adopt' => [
        'title' => 'Convertir en regla',
        'scope' => '¿A qué se aplica?',
        'scope_account' => 'Solo a :name',
        'scope_one' => 'Una cuenta concreta',
        'scope_all' => 'A todas mis cuentas',
        'scope_hint' => 'Las reglas de una cuenta bajan además a su plan, para que el semáforo de la sesión las vigile sola.',
        'text_label' => 'La regla, con tus palabras',
        'confirm' => 'Adoptar',
        'cancel' => 'Cancelar',
        'saved' => 'Regla adoptada. La verás en el checklist de tu próxima sesión.',
        'enforced' => 'Regla adoptada y añadida al plan de la cuenta: el semáforo la vigilará.',
        'no_account' => 'Elige una cuenta o marca que valga para todas.',
    ],

    // ── Mis reglas ───────────────────────────────────────────────────────────
    'mine' => [
        'title' => 'Mis reglas',
        'lead' => 'Salieron de un hallazgo. Si los datos cambian, se pueden desactivar sin borrarlas.',
        'empty' => 'Todavía no has convertido ningún hallazgo en regla.',
        'global' => 'Todas las cuentas',
        'origin' => 'Salió de: :summary (:sample).',
        'origin_sample' => '{1} :count operación|[2,*] :count operaciones',
        'enforced' => 'La vigila el semáforo',
        'checklist_only' => 'Solo en el checklist',
        'activate' => 'Activar',
        'deactivate' => 'Desactivar',
        'delete' => 'Borrar',
        'deleted' => 'Regla borrada.',
        'toggled_on' => 'Regla activada.',
        'toggled_off' => 'Regla desactivada.',
    ],

    // ── Sesión en vivo ───────────────────────────────────────────────────────
    'session' => [
        'my_rules' => 'Mis reglas',
        'broken' => 'Estás rompiendo una regla tuya',
        'broken_max_trades' => 'Llevas :current operaciones y tu tope es :limit.',
        'broken_time_window' => 'Son las :now y tu ventana es de :from a :to.',
        'broken_weekday' => 'Hoy es :weekday, y decidiste no operar ese día.',
    ],
];
