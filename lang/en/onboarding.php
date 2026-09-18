<?php

declare(strict_types=1);

return [
    'title' => 'Get TradeForge running',
    'subtitle' => 'Three steps and your dashboard is working on your own data.',
    'progress' => ':done of :total',
    // Retirado de la interfaz el 2026-09-18; se conserva como canario del test.
    'dismiss' => 'Hide this guide',

    'steps' => [
        'account' => [
            'title' => 'Create your first account',
            'text' => 'Pick the prop firm, the size and the phase. Drawdown and target are watched for you from then on.',
            'cta' => 'Go to Accounts',
        ],
        'trades' => [
            'title' => 'Bring your trades in',
            'text' => 'Upload your platform history, connect MetaTrader, or log them by hand.',
            'cta' => 'Import history',
            'cta_alt' => 'Add them by hand',
        ],
        'goals' => [
            'title' => 'Set your rules',
            'text' => 'Maximum daily risk, trade cap and the rules you will not break. That is what your discipline score is measured against.',
            'cta' => 'Define my plan',
        ],
    ],
];
