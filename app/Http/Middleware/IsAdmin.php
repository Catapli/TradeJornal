<?php

namespace App\Http\Middleware;

use App\AuthActions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    use AuthActions; // <--- Usamos el Trait aquí

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verificamos si hay usuario logueado
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // 2. Usamos tu función del Trait para verificar
        if (! $this->isSuperAdmin($request->user())) {
            abort(403, 'ACCESO DENEGADO: Se requieren permisos de Super Administrador.');
        }

        return $next($request);
    }
}
