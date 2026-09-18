<?php

/**
 * Catálogo global de errores (mistakes.slug). Los errores creados por el
 * usuario no pasan por aquí: se muestran con su nombre literal.
 */

return [

    // ── GRAVES ──────────────────────────────────────────────────────────────
    'revenge_trading' => [
        'name' => 'Trading de venganza',
        'description' => 'Volviste a entrar poco después de una pérdida para recuperarla, no porque apareciera tu setup.',
    ],
    'no_stop_loss' => [
        'name' => 'Sin stop loss',
        'description' => 'Abriste la operación sin un stop definido, dejando la pérdida máxima sin acotar.',
    ],
    'moved_stop_loss' => [
        'name' => 'Mover el stop loss',
        'description' => 'Alejaste el stop de su sitio para evitar que saltara, ampliando el riesgo aceptado al entrar.',
    ],
    'averaging_down' => [
        'name' => 'Promediar pérdidas',
        'description' => 'Añadiste volumen a una posición en pérdidas para bajar el precio medio en lugar de asumir el error.',
    ],
    'excessive_risk' => [
        'name' => 'Riesgo excesivo',
        'description' => 'Arriesgaste más porcentaje de cuenta del que fija tu plan de gestión de riesgo.',
    ],

    // ── MEDIOS ──────────────────────────────────────────────────────────────
    'fomo' => [
        'name' => 'FOMO',
        'description' => 'Entraste tarde persiguiendo un movimiento ya iniciado por miedo a quedarte fuera.',
    ],
    'overtrading' => [
        'name' => 'Sobreoperar',
        'description' => 'Acumulaste más operaciones de las que permite tu plan diario, bajando la calidad de cada entrada.',
    ],
    'counter_trend' => [
        'name' => 'Contra tendencia',
        'description' => 'Operaste en contra de la dirección dominante del marco temporal que usas como referencia.',
    ],
    'round_trip' => [
        'name' => 'Ida y vuelta',
        'description' => 'La operación llegó a estar claramente en beneficio y la dejaste volver hasta cerrar en pérdida.',
    ],
    'held_loser' => [
        'name' => 'Aguantar el perdedor',
        'description' => 'Mantuviste una posición muy en contra esperando que volviera, en vez de cerrar según el plan.',
    ],
    'no_setup' => [
        'name' => 'Entrada sin setup',
        'description' => 'No se cumplían todas las condiciones de tu estrategia: la entrada fue discrecional.',
    ],
    'news_trading' => [
        'name' => 'Operar en noticias',
        'description' => 'Mantuviste o abriste posición durante una noticia de alto impacto sin que fuera parte del plan.',
    ],

    // ── LEVES / TÉCNICOS ────────────────────────────────────────────────────
    'early_exit' => [
        'name' => 'Salida prematura',
        'description' => 'Cerraste antes de tu objetivo y capturaste sólo una parte pequeña del movimiento a favor.',
    ],
    'late_entry' => [
        'name' => 'Entrada tarde',
        'description' => 'Ejecutaste con retraso respecto a la señal y empeoraste el precio medio y el ratio riesgo/beneficio.',
    ],
    'wrong_size' => [
        'name' => 'Lotaje incorrecto',
        'description' => 'El tamaño de la posición no correspondía a la distancia del stop ni al riesgo previsto.',
    ],

];
