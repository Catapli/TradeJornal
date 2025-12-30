<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSectionPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,  string $section, string $action): Response
    {
        // Verificar que el usuario esté autenticado

        if (!Auth::check()) {
            abort(403, 'Acceso denegado. Debes iniciar sesión.');
        }

        // Verificar permiso usando el método que definimos en el modelo User
        if (!$request->user()->canDo($section, $action)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        // if($section == "towns")

        return $next($request);
    }
}
