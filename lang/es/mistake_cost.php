<?php

return [

    // ── Titular (portada y Laboratorio) ──────────────────────────────────────
    'title' => 'Lo que te han costado tus errores',
    'headline_cost' => 'Tus errores te han costado :amount',
    'headline_gain' => 'Tus operaciones marcadas te han dado :amount',
    'headline_neutral' => 'Tus errores marcados no te han costado nada todavía',
    'without_them' => 'Sin ellas llevarías :amount en vez de :real.',
    'no_marked' => 'Todavía no has marcado ningún error en este periodo.',
    'no_trades' => 'No hay operaciones en este periodo.',

    // ── Cobertura ────────────────────────────────────────────────────────────
    'coverage' => 'Calculado sobre :reviewed de :total operaciones repasadas (:percent %).',
    'coverage_short' => ':reviewed de :total repasadas',
    'coverage_warning' => 'Cuantas más repases, más se acerca este número a la verdad.',

    // ── Desglose ─────────────────────────────────────────────────────────────
    'by_mistake' => 'Por error',
    'by_slot' => 'Por franja horaria',
    'cost_column' => 'Coste',
    'count_column' => 'Operaciones',
    'trades_count' => '{1} :count operación|[2,*] :count operaciones',
    'avg_column' => 'Media',
    'overlap_note' => 'Una operación puede llevar varios errores, así que estas cifras no suman el total.',
    'gain_label' => 'a favor',

    'slots' => [
        'early' => 'Madrugada (00-07)',
        'morning' => 'Mañana (08-12)',
        'afternoon' => 'Tarde (13-17)',
        'evening' => 'Noche (18-23)',
    ],

    // ── Escenario del Laboratorio ────────────────────────────────────────────
    'scenario_title' => 'Quitar operaciones con estos errores',
    'scenario_hint' => 'Marca uno o varios para ver tu curva sin esas operaciones.',
    'scenario_none' => 'Marca errores en tus operaciones para poder usar este escenario.',

    // ── Cola de repaso ───────────────────────────────────────────────────────
    'review' => [
        'title' => 'Perdedoras sin repasar',
        'lead' => 'Marca lo que pasó en cada una. Lo que no repasas no cuenta en el número de arriba.',
        'empty' => 'No te queda ninguna perdedora sin repasar. Bien hecho.',
        'pending' => '{1} Te queda :count perdedora sin repasar|[2,*] Te quedan :count perdedoras sin repasar',
        'open' => 'Repasar ahora',
        'open_trade' => 'Ver detalle',
        'clean' => 'Sin errores',
        'clean_hint' => 'La saca de la cola sin inventarse nada: la miraste y estaba limpia.',
        'save' => 'Guardar y siguiente',
        'saved' => 'Operación repasada.',
        'marked_clean' => 'Marcada como repasada, sin errores.',
        'no_mistakes_yet' => 'Todavía no tienes errores definidos. Créalos desde el detalle de una operación.',
        'showing' => 'Mostrando :count de :total.',
        'load_more' => 'Ver más',
    ],
];
