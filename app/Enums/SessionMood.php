<?php

namespace App\Enums;

enum SessionMood: string
{
    case Calm       = 'calm';
    case Neutral    = 'neutral';
    case Anxious    = 'anxious';
    case Confident  = 'confident';
    case Satisfied  = 'satisfied';
    case Frustrated = 'frustrated';
    case Tired      = 'tired';

    public function label(): string
    {
        return __('labels.mood_' . $this->value);
    }

    /** Devuelve [{key: 'calm', label: 'Calmado'}, ...] para Alpine */
    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn(self $c) => ['key' => $c->value, 'label' => $c->label()])
            ->toArray();
    }
}
