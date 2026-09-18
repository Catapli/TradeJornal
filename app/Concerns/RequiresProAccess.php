<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * Cierra en servidor los módulos de pago.
 *
 * Hasta ahora el muro era solo visual: los módulos PRO se ocultaban en el
 * sidebar y, desde la Fase 1, se sirven difuminados e inertes. Pero sus rutas
 * nunca estuvieron protegidas, así que un usuario gratuito que fabricara la
 * petición de Livewire a mano podía usarlos enteros.
 *
 * El corte no puede ir en la ruta: la vista previa difuminada necesita que la
 * pantalla se renderice. Va en el `boot()` del componente, distinguiendo el
 * render inicial —permitido, es el escaparate— de cualquier acción posterior,
 * que es donde de verdad se trabaja.
 *
 * Uso: `use RequiresProAccess;` en el componente Livewire del módulo.
 */
trait RequiresProAccess
{
    public function bootRequiresProAccess(): void
    {
        // Render inicial: entra dentro de una petición HTTP normal, no de una
        // petición de Livewire. Es la vista previa y se deja pasar.
        if (!Livewire::isLivewireRequest()) {
            return;
        }

        if (Auth::user()?->hasProAccess()) {
            return;
        }

        abort(403, __('landing.gate.locked'));
    }
}
