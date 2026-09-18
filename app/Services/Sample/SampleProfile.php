<?php

declare(strict_types=1);

namespace App\Services\Sample;

/**
 * Perfil de comportamiento de una cuenta de ejemplo.
 *
 * `target` es el P&L final al que se normaliza la serie. Sin él, cualquier
 * combinación razonable de acierto y R:R produce curvas imposibles: un challenge
 * de 50k con un +40 % que ninguna firma habría dejado llegar tan lejos.
 */
final class SampleProfile
{
    public function __construct(
        /** Días hacia atrás desde hoy en los que empieza el histórico. */
        public readonly int $days,
        /** Día en el que deja de operar (0 = hasta hoy). */
        public readonly int $until,
        /** Porcentaje de operaciones ganadoras. */
        public readonly int $winrate,
        /** Riesgo medio por operación, en la divisa de la cuenta. */
        public readonly float $risk,
        /** Ratio beneficio/riesgo medio de las ganadoras. */
        public readonly float $rr,
        /** Porcentaje de días laborables en los que hay operaciones. */
        public readonly int $density,
        /** P&L acumulado al que se normaliza la serie. */
        public readonly float $target,
    ) {}

    public static function challenge(int $days, float $target): self
    {
        return new self($days, 0, 52, 250, 1.15, 55, $target);
    }

    public static function funded(int $days, float $target): self
    {
        return new self($days, 0, 50, 120, 1.40, 40, $target);
    }

    public static function burned(int $days, int $until, float $target): self
    {
        return new self($days, $until, 40, 90, 1.10, 70, $target);
    }
}
