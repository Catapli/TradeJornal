<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Manifiesto y pantalla sin conexión (R4, base de PWA).
 *
 * El manifiesto se sirve desde una ruta y no como fichero estático para que el
 * nombre y la descripción salgan traducidos: es el texto que se queda pegado al
 * icono en el escritorio del usuario.
 */
class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $manifest = [
            'name' => __('pwa.name'),
            'short_name' => __('pwa.short_name'),
            'description' => __('pwa.description'),
            'lang' => app()->getLocale(),
            'dir' => 'ltr',
            'start_url' => route('dashboard', absolute: false),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0B1120',
            'theme_color' => '#0B1120',
            'icons' => [
                [
                    'src' => asset('img/pwa/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('img/pwa/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    // Android recorta el icono a su antojo; este lleva margen.
                    'src' => asset('img/pwa/icon-maskable-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => __('menu.dashboard'),
                    'url' => route('dashboard', absolute: false),
                ],
                [
                    'name' => __('menu.journal'),
                    'url' => route('journal', absolute: false),
                ],
                [
                    'name' => __('import.menu'),
                    'url' => route('trades.import', absolute: false),
                ],
            ],
        ];

        return response()
            ->json($manifest, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }

    /** Pantalla que sirve el service worker cuando no hay red. */
    public function offline(): View
    {
        return view('pwa.offline');
    }
}
