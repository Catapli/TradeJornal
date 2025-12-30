<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\TrustProxies;           // <-- añade esto
use Illuminate\Support\Facades\URL;              // <-- si vas a usar forceScheme
use Illuminate\Support\Str;                      // <-- opcional para comprobar cabeceras
use Illuminate\Http\Request;                     // <-- si quisieras usar constantes de headers

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*'); // Confía en el LB/Ingress
    $middleware->trustHosts(['^app\.cloud\.detrafic\.es$']);

    $middleware->alias([
        'check.permission' => \App\Http\Middleware\CheckSectionPermission::class,
    ]);
})

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
