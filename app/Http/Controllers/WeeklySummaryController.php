<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Demo;
use Illuminate\View\View;

class WeeklySummaryController extends Controller
{
    /**
     * Baja del resumen semanal desde el propio correo.
     *
     * La ruta va firmada y no exige sesión a propósito: el correo se lee en el
     * móvil y obligar a iniciar sesión para dejar de recibirlo es la forma
     * educada de no dejar que nadie se dé de baja.
     */
    public function unsubscribe(User $user): View
    {
        // En demo la escritura está cancelada de todos modos; devolver la
        // pantalla de confirmación mentiría sobre lo que ha pasado.
        if (!Demo::active()) {
            $user->forceFill(['weekly_summary' => false])->saveQuietly();
        }

        return view('weekly.unsubscribe', ['user' => $user]);
    }
}
