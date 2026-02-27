<?php

namespace App\Enums;

enum TradeMood: string
{
    case Neutral    = 'neutral';
    case Happy       = 'happy';
    case Angry    = 'angry';
    case Fearful  = 'fearful';
    case Confident = 'confident';

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
