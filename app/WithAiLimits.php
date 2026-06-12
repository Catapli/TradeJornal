<?php

namespace App;

use App\Models\AiUsage;
use Illuminate\Support\Facades\Auth;

trait WithAiLimits
{
    /**
     * Comprueba si el usuario puede hacer peticiones.
     * Devuelve true si puede, false si ha llegado al límite.
     */
    public function checkAiLimit()
    {
        if ($this->getAiCreditsLeft() > 0) {
            return true;
        }

        $hours = (int) ceil(now()->diffInMinutes(now()->endOfDay()) / 60);
        $limit = $this->aiDailyLimit();

        $this->dispatch('notify', "⚠️ Has alcanzado tu límite diario de $limit análisis. Se reinicia en $hours horas.");

        return false;
    }

    /**
     * Consume un crédito. Llamar a esto SOLO si la petición a la IA fue exitosa.
     * Persistido en BD: sobrevive a limpiezas de caché y permite métricas de uso.
     */
    public function consumeAiCredit()
    {
        $usage = AiUsage::firstOrCreate(
            ['user_id' => Auth::id(), 'date' => today()->toDateString()],
            ['count' => 0]
        );

        $usage->increment('count');
    }

    /**
     * Créditos restantes del día (para mostrar en la vista).
     */
    public function getAiCreditsLeft()
    {
        $used = AiUsage::where('user_id', Auth::id())
            ->where('date', today()->toDateString())
            ->value('count') ?? 0;

        return max(0, $this->aiDailyLimit() - $used);
    }

    /**
     * Límite diario según el plan del usuario (free vs suscripción activa).
     */
    private function aiDailyLimit(): int
    {
        $user = Auth::user();
        $isPro = $user && $user->subscribed('default');

        return (int) config($isPro ? 'services.groq.daily_limit_pro' : 'services.groq.daily_limit_free');
    }
}
