@props([
    'type' => 'new',
    'icon' => 'fa-solid fa-eraser',
    'tooltip' => 'Nuevo',
])

@php
    // Definimos las posibles opciones de color que quieres manejar:
    // Si agregas más (info, secondary, etc.), añádelos aquí
    $typeClases = [
        'new' => 'hover:text-green-600',
        'edit' => 'hover:text-yellow-500',
        'delete' => 'hover:text-red-600',
    ];

    // Si no se encuentra la clave, usa una clase por defecto
    $bgClass = $typeClases[$type] ?? 'hover:text-green-600';
@endphp

<div {!! $attributes->merge(['class' => 'relative justify-items-center']) !!}>
    <button class="peer flex h-14 w-16 items-center justify-center gap-x-2 rounded-full border border-transparent px-2 py-1 text-sm font-medium text-white transition-all duration-500 hover:scale-110 focus:outline-none disabled:pointer-events-none disabled:opacity-50 dark:bg-white dark:text-neutral-800"
            type="button">
        <i class="{{ $icon }} {{ $bgClass }} text-3xl text-black"></i>
    </button>

    <div class="absolute -bottom-5 left-1/2 z-10 -translate-x-1/2 translate-y-2 scale-95 whitespace-nowrap rounded bg-neutral-950 px-2 py-1 text-center text-sm text-white opacity-0 transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 peer-focus:translate-y-0 peer-focus:scale-100 peer-focus:opacity-100 dark:bg-white dark:text-neutral-900"
         role="tooltip">
        {{ $tooltip }}
    </div>
</div>
