<?php

declare(strict_types=1);

return [
    'title' => 'Pon TradeForge en marcha',
    'subtitle' => 'Tres pasos y tienes el panel funcionando con tus datos.',
    'progress' => ':done de :total',
    // El botón se retiró el 2026-09-18 (el bloque entero se pliega). La clave
    // se conserva porque es el canario del test de regresión: si alguien vuelve
    // a pintar este texto en la guía, el test lo canta.
    'dismiss' => 'Ocultar la guía',

    'steps' => [
        'account' => [
            'title' => 'Crea tu primera cuenta',
            'text' => 'Elige la prop firm, el tamaño y la fase. Con eso ya se vigilan solos el drawdown y el objetivo.',
            'cta' => 'Ir a Cuentas',
        ],
        'trades' => [
            'title' => 'Mete tus operaciones',
            'text' => 'Sube el historial de tu plataforma, conecta MetaTrader o regístralas a mano.',
            'cta' => 'Importar historial',
            'cta_alt' => 'Añadirlas a mano',
        ],
        'goals' => [
            'title' => 'Fija tus reglas',
            'text' => 'Riesgo máximo diario, tope de operaciones y las reglas que no te vas a saltar. Es lo que después mide tu disciplina.',
            'cta' => 'Definir mi plan',
        ],
    ],
];
