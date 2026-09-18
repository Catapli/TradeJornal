@props([
    'icon' => 'fa-inbox',
    'title',
    'text' => null,
    'compact' => false,
])

{{--
    Estado vacío con salida.

    Una tabla vacía con una línea de texto gris no le dice al usuario qué hacer a
    continuación, y el primer día del producto es justo eso: tablas vacías. Aquí
    van icono, explicación y, en el slot, los botones que llenan esa tabla.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center ' . ($compact ? 'py-10 px-4' : 'py-16 px-6')]) }}>

    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
        <i class="fa-solid {{ $icon }} text-xl text-gray-400 dark:text-gray-500"></i>
    </span>

    <p class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $title }}</p>

    @if ($text)
        <p class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">{{ $text }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
