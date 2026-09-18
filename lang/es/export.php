<?php

declare(strict_types=1);

return [

    // ── CSV ────────────────────────────────────────────────────────────────
    'csv' => [
        'slug' => 'operaciones',
        'button' => 'Exportar CSV',
        'hint' => 'Baja exactamente las operaciones que estás viendo, con los filtros puestos.',
        'empty' => 'No hay ninguna operación que exportar con estos filtros.',
    ],

    'demo_notice' => 'Datos de la demo de TradeForge: no son operaciones reales.',

    'columns' => [
        'ticket' => 'Ticket',
        'account' => 'Cuenta',
        'asset' => 'Activo',
        'direction' => 'Dirección',
        'entry_time' => 'Apertura',
        'exit_time' => 'Cierre',
        'entry_price' => 'Precio entrada',
        'exit_price' => 'Precio salida',
        'size' => 'Tamaño',
        'pnl' => 'P&L',
        'pnl_percentage' => 'P&L %',
        'duration' => 'Duración (min)',
        'strategy' => 'Estrategia',
        'mistakes' => 'Errores',
        'mood' => 'Emoción',
        'notes' => 'Notas',
    ],

    'direction' => [
        'long' => 'Larga',
        'short' => 'Corta',
    ],

    // ── Informe mensual en PDF ─────────────────────────────────────────────
    'pdf' => [
        'slug' => 'informe',
        'button' => 'Descargar informe (PDF)',
        'card_title' => 'Informe mensual',
        'card_lead' => 'Un PDF con marca del mes elegido: métricas, curva, calendario, coste de los errores y cumplimiento de tus reglas.',
        'account_label' => 'Cuenta',
        'month_label' => 'Mes',
        'all_accounts' => 'todas las cuentas',
        'all_accounts_option' => 'Todas las cuentas',
        'empty' => 'No hay operaciones cerradas en :month. Elige otro mes.',
        'generating' => 'Generando informe…',

        'title' => 'Informe mensual',
        'generated_at' => 'Generado el :date',
        'for' => 'Preparado para :name',
        'demo_stamp' => 'DEMO',
        'demo_footer' => 'Informe generado desde la demo de TradeForge con datos de ejemplo.',
        'footer' => 'TradeForge · diario de trading para prop firms',

        'account' => [
            'phase' => 'Fase',
            'status' => 'Estado',
            'balance' => 'Balance',
        ],

        'metrics' => [
            'title' => 'El mes en cifras',
            'pnl' => 'P&L del mes',
            'trades' => 'Operaciones',
            'win_rate' => 'Acierto',
            'profit_factor' => 'Factor de beneficio',
            'expectancy' => 'Esperanza por operación',
            'max_drawdown' => 'Máxima caída',
            'avg_win' => 'Ganancia media',
            'avg_loss' => 'Pérdida media',
            'best_trade' => 'Mejor operación',
            'worst_trade' => 'Peor operación',
            'win_days' => 'Días en verde',
            'loss_days' => 'Días en rojo',
            'infinite' => 'sin pérdidas',
        ],

        'previous' => [
            'title' => 'Contra :month',
            'none' => 'Sin operaciones el mes anterior: no hay con qué comparar.',
            'pnl' => 'P&L',
            'win_rate' => 'Acierto',
            'trades' => 'Operaciones',
        ],

        'equity' => [
            'title' => 'Curva de capital del mes',
            'lead' => 'Acumulado día a día, por fecha de cierre.',
            'empty' => 'Sin operaciones cerradas: no hay curva que dibujar.',
        ],

        'calendar' => [
            'title' => 'Calendario del mes',
            'lead' => 'P&L por día de cierre. En blanco, los días sin operar.',
            'weekdays' => ['L', 'M', 'X', 'J', 'V', 'S', 'D'],
        ],

        'mistakes' => [
            'title' => 'Lo que costaron los errores',
            'cost' => 'Coste del mes',
            'without' => 'Sin esas operaciones llevarías',
            'real' => 'Lo que llevas de verdad',
            'coverage' => 'Calculado sobre :reviewed de :total operaciones repasadas (:coverage %).',
            'pending' => 'Quedan :count perdedoras sin repasar.',
            'column_mistake' => 'Error',
            'column_count' => 'Operaciones',
            'column_cost' => 'Coste',
            'column_avg' => 'Coste medio',
            'overlap' => 'Una operación con dos errores cuenta entera en los dos, así que el desglose no suma el total.',
            'empty' => 'Ninguna operación marcada este mes.',
        ],

        'rules' => [
            'title' => 'Tus reglas',
            'lead' => 'Reglas activas adoptadas desde el Laboratorio.',
            'column_rule' => 'Regla',
            'column_breaches' => 'Veces que la rompiste',
            'column_rate' => 'Cumplimiento',
            'unit_days' => ':breaches de :scope días',
            'unit_trades' => ':breaches de :scope operaciones',
            'manual' => 'La compruebas tú',
            'empty' => 'Todavía no has adoptado ninguna regla.',
            'global' => 'todas las cuentas',
        ],
    ],
];
