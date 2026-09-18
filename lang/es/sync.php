<?php

declare(strict_types=1);

return [
    'title' => 'Conexión con MetaTrader',
    'subtitle' => 'Estado de la sincronización automática de tus cuentas.',
    'menu' => 'Conexión',

    'token' => [
        'title' => 'Tu token de sincronización',
        'help' => 'El ejecutable que corre junto a tu terminal lo usa para identificarte. Trátalo como una contraseña: quien lo tenga puede escribir operaciones en tus cuentas.',
        'show' => 'Mostrar',
        'hide' => 'Ocultar',
        'copy' => 'Copiar',
        'copied' => 'Token copiado al portapapeles.',
        'regenerate' => 'Generar uno nuevo',
        'regenerate_warning' => 'Al generarlo, el ejecutable dejará de sincronizar hasta que le pongas el token nuevo.',
    ],
    'token_regenerated' => 'Token regenerado. Actualízalo en el ejecutable.',

    'accounts' => [
        'title' => 'Estado por cuenta',
        'account' => 'Cuenta',
        'login' => 'Login',
        'server' => 'Servidor',
        'status' => 'Estado',
        'last_sync' => 'Última sincronización',
        'never' => 'Nunca',
        'active' => 'Sincronizando',
        'inactive' => 'Sin sincronización',
        'error' => 'Con error',
        'empty' => 'Todavía no tienes cuentas configuradas.',
        'create' => 'Crear una cuenta',
        'stale' => 'Sin datos nuevos desde hace :days días. Comprueba que el terminal esté abierto y el ejecutable corriendo.',
    ],

    'help' => [
        'title' => 'Si no llega nada',
        'items' => [
            'El terminal de MetaTrader tiene que estar abierto y con la cuenta conectada.',
            'El ejecutable debe estar corriendo en el mismo equipo que el terminal.',
            'El login de la cuenta en TradeForge tiene que coincidir exactamente con el del terminal.',
            'La sincronización automática requiere plan PRO.',
        ],
        'alternative' => '¿Otra plataforma o prefieres no instalar nada? Sube el historial como fichero.',
        'alternative_cta' => 'Importar historial',
    ],
];
