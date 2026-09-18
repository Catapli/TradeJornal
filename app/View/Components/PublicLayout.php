<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout de las páginas públicas (landing y precios sin sesión).
 *
 * Se declara como componente de clase por coherencia con AppLayout y GuestLayout,
 * que es como el proyecto resuelve <x-app-layout> y <x-guest-layout>.
 */
class PublicLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
    ) {}

    public function render(): View
    {
        return view('layouts.public');
    }
}
