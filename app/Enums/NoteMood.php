<?php

namespace App\Enums;

enum NoteMood: string
{
    case Neutral    = 'neutral';
    case Anxious  = 'anxious';
    case Confident = 'confident';
    case Calm      = 'calm';
    case Fear      = 'fear';

    public function label(): string
    {
        return __('labels.mood_' . $this->value);
    }

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn(self $c) => ['key' => $c->value, 'label' => $c->label()])
            ->toArray();
    }
}
