<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

trait WithAiLimits
{
    /**
     * Comprueba si el usuario puede hacer peticiones.
     * Devuelve true si puede, false si ha llegado al límite.
     */
    public function checkAiLimit()
    {
        $key = 'ai_limit:' . Auth::id();
        $maxAttempts = 10; // Límite diario

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            // Formatear mensaje de error
            $hours = ceil($seconds / 3600);

            $this->dispatch('notify', "⚠️ Has alcanzado tu límite diario de $maxAttempts análisis. Se reinicia en $hours horas.");
            return false;
        }

        return true;
    }

    /**
     * Consume un crédito. Llamar a esto SOLO si la petición a Gemini fue exitosa.
     */
    public function consumeAiCredit()
    {
        $key = 'ai_limit:' . Auth::id();

        // Calculamos segundos hasta la medianoche para que se resetee al acabar el día
        $secondsUntilMidnight = now()->diffInSeconds(now()->endOfDay());

        RateLimiter::hit($key, $secondsUntilMidnight);
    }

    /**
     * (Opcional) Obtener créditos restantes para mostrar en la vista
     */
    public function getAiCreditsLeft()
    {
        $key = 'ai_limit:' . Auth::id();
        $maxAttempts = 10;
        $attempts = RateLimiter::attempts($key);

        return max(0, $maxAttempts - $attempts);
    }
}
