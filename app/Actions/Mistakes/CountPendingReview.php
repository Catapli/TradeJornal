<?php

declare(strict_types=1);

namespace App\Actions\Mistakes;

use App\Models\Trade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Cuántas perdedoras quedan por repasar (Fase 6 · usabilidad del repaso).
 *
 * El número lo piden tres sitios a la vez —el carril lateral, el Laboratorio y la
 * propia pantalla de repaso—, y el del carril se pide en **cada carga de página**.
 * Por eso vive aquí con caché corta en vez de repetir la consulta en cada uno.
 *
 * La definición de «pendiente» también es única a propósito: una perdedora sin
 * errores marcados y sin haber pasado por el repaso. Si cada pantalla se la
 * inventara, el carril diría 9 y la lista enseñaría 7.
 */
class CountPendingReview
{
    /** Lo justo para que el número no vaya un paso por detrás dentro de una sesión. */
    private const TTL_MINUTES = 5;

    public function execute(int $userId): int
    {
        return Cache::remember(
            self::key($userId),
            now()->addMinutes(self::TTL_MINUTES),
            fn (): int => self::query($userId)->count(),
        );
    }

    /** Se llama al repasar una operación: el número acaba de cambiar. */
    public static function forget(int $userId): void
    {
        Cache::forget(self::key($userId));
    }

    /**
     * Perdedoras sin repasar, de la más reciente a la más antigua.
     *
     * Solo perdedoras: es donde está el dinero y donde el recuerdo todavía escuece.
     * Repasar trescientas ganadoras no lo hace nadie.
     */
    public static function query(int $userId): Builder
    {
        // Las perdedoras de una cuenta quemada son las que más hay que repasar:
        // son las que explican por qué se quemó. Las archivadas quedan fuera.
        return Trade::forUser($userId)
            ->where('pnl', '<', 0)
            ->whereNull('mistakes_reviewed_at')
            ->whereDoesntHave('mistakes')
            ->orderByDesc('exit_time');
    }

    private static function key(int $userId): string
    {
        return "pending_review_{$userId}";
    }
}
