<?php

declare(strict_types=1);

return [
    'title' => 'MetaTrader connection',
    'subtitle' => 'Automatic sync status for your accounts.',
    'menu' => 'Connection',

    'token' => [
        'title' => 'Your sync token',
        'help' => 'The executable running next to your terminal uses it to identify you. Treat it like a password: anyone holding it can write trades into your accounts.',
        'show' => 'Show',
        'hide' => 'Hide',
        'copy' => 'Copy',
        'copied' => 'Token copied to the clipboard.',
        'regenerate' => 'Generate a new one',
        'regenerate_warning' => 'Once you generate it, the executable stops syncing until you give it the new token.',
    ],
    'token_regenerated' => 'Token regenerated. Update it in the executable.',

    'accounts' => [
        'title' => 'Status per account',
        'account' => 'Account',
        'login' => 'Login',
        'server' => 'Server',
        'status' => 'Status',
        'last_sync' => 'Last sync',
        'never' => 'Never',
        'active' => 'Syncing',
        'inactive' => 'Not syncing',
        'error' => 'Error',
        'empty' => 'You have no accounts set up yet.',
        'create' => 'Create an account',
        'stale' => 'No new data for :days days. Check that the terminal is open and the executable is running.',
    ],

    'help' => [
        'title' => 'If nothing arrives',
        'items' => [
            'The MetaTrader terminal must be open and logged into the account.',
            'The executable has to run on the same machine as the terminal.',
            'The account login in TradeForge must match the terminal exactly.',
            'Automatic sync requires the PRO plan.',
        ],
        'alternative' => 'Different platform, or would rather not install anything? Upload your history as a file.',
        'alternative_cta' => 'Import history',
    ],
];
