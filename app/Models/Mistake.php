<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

/**
 * @property-read string $display_name
 * @property-read string $display_description
 * @property-read string $color_hex
 */
class Mistake extends Model
{
    /** Paleta permitida: nombre Tailwind => hex (para gráficos y puntos de color). */
    public const COLORS = [
        'red' => '#EF4444',
        'orange' => '#F97316',
        'amber' => '#F59E0B',
        'yellow' => '#EAB308',
        'lime' => '#84CC16',
        'green' => '#22C55E',
        'teal' => '#14B8A6',
        'cyan' => '#06B6D4',
        'blue' => '#3B82F6',
        'indigo' => '#6366F1',
        'violet' => '#8B5CF6',
        'purple' => '#A855F7',
        'fuchsia' => '#D946EF',
        'pink' => '#EC4899',
        'rose' => '#F43F5E',
        'slate' => '#64748B',
    ];

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'color',
        'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    /** Errores del usuario + catálogo global (user_id null). */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhereNull('user_id');
        });
    }

    /** Sólo los errores creados por el usuario (los únicos editables). */
    public function scopeCustom($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isCustom(): bool
    {
        return $this->user_id !== null;
    }

    /** Nombre traducido si es del catálogo global; literal si lo creó el usuario. */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->translated('name') ?? $this->name);
    }

    protected function displayDescription(): Attribute
    {
        return Attribute::get(fn () => $this->translated('description') ?? $this->description);
    }

    protected function colorHex(): Attribute
    {
        return Attribute::get(function () {
            $color = (string) $this->color;

            return str_starts_with($color, '#')
                ? $color
                : (self::COLORS[$color] ?? self::COLORS['red']);
        });
    }

    /** Traducción del catálogo global; null para errores del usuario o slugs sin traducir. */
    private function translated(string $field): ?string
    {
        if (!$this->slug) {
            return null;
        }

        $key = "mistakes.{$this->slug}.{$field}";

        return Lang::has($key) ? __($key) : null;
    }
}
