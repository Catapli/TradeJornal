<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Punto único de verdad del modo demo.
 *
 * `active()` es lo que consultan el guardia de escritura, el banner del layout
 * y los sitios que gastan dinero de verdad (IA y Stripe).
 */
final class Demo
{
    /** Clave de sesión que marca una visita como demo. */
    public const SESSION_KEY = 'tf_demo_mode';

    /** ¿Está habilitada la demo en este entorno? */
    public static function enabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    /** ¿Estamos sirviendo ahora mismo una sesión de demo? */
    public static function active(): bool
    {
        return self::enabled()
            && Session::get(self::SESSION_KEY) === true
            && Auth::check();
    }

    /** El usuario sembrado por DemoSeeder, o null si no existe todavía. */
    public static function user(): ?User
    {
        return User::where('email', config('demo.email'))->first();
    }
}
