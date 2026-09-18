<?php

declare(strict_types=1);

namespace App;

use App\Models\AiUsage;
use App\Support\Demo;
use Illuminate\Support\Facades\Auth;

/**
 * Cupo diario de análisis con IA para los componentes Livewire.
 *
 * El cálculo vive en el modelo `User` (`aiDailyLimit()` y `aiCreditsLeft()`),
 * porque el contador de la barra de navegación también lo necesita y tener dos
 * copias de la misma cuenta acabaría dando cifras distintas en cada sitio.
 * Aquí queda lo propio del componente: avisar y consumir.
 */
trait WithAiLimits
{
    /**
     * ¿Puede hacer una petición más hoy? Avisa por su cuenta si no.
     */
    public function checkAiLimit(): bool
    {
        // La demo no gasta llamadas de pago: los análisis que se ven en ella
        // vienen precocinados en DemoSeeder, guardados en trades.ai_analysis.
        if (Demo::active()) {
            $this->dispatch('notify', __('landing.demo.blocked'));

            return false;
        }

        if ($this->getAiCreditsLeft() > 0) {
            return true;
        }

        $this->dispatch('notify', __('labels.limit_ai_reached_detail', [
            'limit' => $this->aiDailyLimit(),
            'hours' => (int) ceil(now()->diffInMinutes(now()->endOfDay()) / 60),
        ]));

        return false;
    }

    /**
     * Consume un crédito. Llamar SOLO si la petición a la IA salió bien.
     *
     * Persistido en BD: sobrevive a limpiezas de caché y permite métricas de uso.
     */
    public function consumeAiCredit(): void
    {
        $usage = AiUsage::firstOrCreate(
            ['user_id' => Auth::id(), 'date' => today()->toDateString()],
            ['count' => 0]
        );

        $usage->increment('count');
    }

    /** Créditos que le quedan hoy (público: las vistas lo pintan). */
    public function getAiCreditsLeft(): int
    {
        return Auth::user()?->aiCreditsLeft() ?? 0;
    }

    /** Total diario según su plan (público: las vistas muestran «x de y»). */
    public function aiDailyLimit(): int
    {
        return Auth::user()?->aiDailyLimit() ?? 0;
    }
}
