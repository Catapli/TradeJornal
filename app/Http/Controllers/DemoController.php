<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Demo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Entrada y salida de la demo pública.
 *
 * La demo autentica un usuario sembrado y marca la sesión; a partir de ahí
 * DemoGuard se encarga de que no se escriba nada.
 */
class DemoController extends Controller
{
    public function enter(Request $request): RedirectResponse
    {
        // Un usuario real que llega a /demo por curiosidad no debe perder su sesión.
        if (Auth::check() && !Demo::active()) {
            return redirect()->route('dashboard');
        }

        if (!Demo::enabled() || !($demoUser = Demo::user())) {
            return redirect()
                ->route('register')
                ->with('error', __('landing.demo.blocked'));
        }

        Auth::login($demoUser);

        $request->session()->regenerate();
        $request->session()->put(Demo::SESSION_KEY, true);

        return redirect()->route('dashboard');
    }

    public function exit(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
