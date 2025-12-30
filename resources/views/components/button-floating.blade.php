@props([
    'color' => 'primary',
    'icon' => 'fa-solid fa-eraser',
    'tooltip' => 'Reiniciar Filtros',
])

@php
    // Definimos las posibles opciones de color que quieres manejar:
    // Si agregas más (info, secondary, etc.), añádelos aquí
    $colorClasses = [
        'primary' => 'bg-primary hover:bg-secondary',
        'secondary' => 'bg-secondary hover:bg-primary',
        'danger' => 'bg-red-600 hover:bg-red-700',
        'success' => 'bg-green-600 hover:bg-green-700',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600',
        'gray' => 'bg-gray-800 hover:bg-primary', // o como quieras manejar "gray"
    ];

    // Si no se encuentra la clave, usa una clase por defecto
    $bgClass = $colorClasses[$color] ?? 'bg-gray-800 hover:bg-primary';
@endphp

<div {!! $attributes->merge(['class' => ' relative justify-items-center']) !!}>
    <button class="shadow_btn {{ $bgClass }} peer flex h-12 w-14 items-center justify-center gap-x-2 rounded-full border border-transparent px-4 py-3 text-sm font-medium text-white transition-all duration-500 hover:scale-110 focus:outline-none disabled:pointer-events-none disabled:opacity-50 dark:bg-white dark:text-neutral-800"
            type="button">
        <i class="{{ $icon }} text-xl"></i>
    </button>

    <div class="absolute -bottom-8 left-1/2 z-10 -translate-x-1/2 translate-y-2 scale-95 whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 dark:bg-white dark:text-neutral-900"
         role="tooltip">
        {{ $tooltip }}
    </div>
</div>
