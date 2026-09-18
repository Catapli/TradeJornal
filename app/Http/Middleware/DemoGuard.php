<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Convierte la sesión de demo en solo lectura.
 *
 * No se puede filtrar por método HTTP: en una aplicación Livewire *toda*
 * interacción viaja como POST a /livewire/update, así que bloquear por verbo
 * dejaría la demo inservible. Se bloquea donde de verdad importa:
 *
 *  1. Escrituras de Eloquent — un listener comodín cancela `saving`, `deleting`
 *     y `restoring` devolviendo false, que es como Eloquent aborta la operación
 *     sin lanzar excepción. Todo el dominio pasa por modelos, así que esto cubre
 *     trades, cuentas, journal, sesiones, errores y logs.
 *  2. Subida de ficheros — el endpoint temporal de Livewire y el del journal
 *     escribirían en R2 aunque el modelo no se guardara.
 *
 * Las sesiones de Laravel (driver `database`) no pasan por Eloquent, así que
 * el login de la demo y el CSRF siguen funcionando.
 */
class DemoGuard
{
    /** Rutas de subida que no deben ejecutarse en modo demo. */
    private const BLOCKED_ROUTES = [
        'livewire.upload-file',
        'journal.upload',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!Demo::active()) {
            return $next($request);
        }

        if ($request->routeIs(self::BLOCKED_ROUTES)) {
            abort(403, __('landing.demo.blocked'));
        }

        Event::listen([
            'eloquent.saving: *',
            'eloquent.deleting: *',
            'eloquent.restoring: *',
        ], fn (): bool => false);

        return $next($request);
    }
}
