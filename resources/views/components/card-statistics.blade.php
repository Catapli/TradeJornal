@props(['label' => '', 'icono' => '', 'key' => '', 'tooltip' => '', 'align' => 'center'])

@php
    // Clases de alineación para el tooltip
    $alignClasses =
        [
            'center' => 'left-1/2 -translate-x-1/2',
            'right' => 'right-0',
            'left' => 'left-0',
        ][$align] ?? 'left-1/2 -translate-x-1/2';

    // Clases de la flechita del tooltip
    $arrowClasses =
        [
            'center' => 'left-1/2 -translate-x-1/2',
            'right' => 'right-2',
            'left' => 'left-2',
        ][$align] ?? 'left-1/2 -translate-x-1/2';
@endphp

<div {!! $attributes->merge(['class' => 'group relative rounded-xl border border-gray-200 bg-white p-4 transition-all duration-200 hover:border-gray-300 hover:shadow-md']) !!}>

    <div class="flex items-center gap-3">

        {{-- Icono --}}
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg border-2 border-emerald-200 bg-emerald-50 text-emerald-600 transition-all duration-200 group-hover:border-emerald-300 group-hover:bg-emerald-100">
            <span class="text-lg">{!! $icono !!}</span>
        </div>

        {{-- Contenido --}}
        <div class="min-w-0 flex-1">
            <p class="truncate text-xs font-medium text-gray-500">{{ $label }}</p>
            <p class="truncate text-lg font-bold text-gray-900">{{ $key }}</p>
        </div>

        {{-- Tooltip Icon --}}
        <div class="relative flex flex-shrink-0 flex-col items-center">
            <i class="fa-solid fa-circle-info peer cursor-help text-gray-400 transition hover:text-gray-600"></i>

            {{-- Tooltip --}}
            <div class="{{ $alignClasses }} pointer-events-none absolute top-full z-30 mt-2 w-64 max-w-[80vw] translate-y-2 scale-95 rounded-lg bg-gray-900 p-3 text-center text-sm text-white opacity-0 shadow-xl transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100"
                 role="tooltip">
                {{ $tooltip }}

                {{-- Arrow --}}
                <div class="{{ $arrowClasses }} absolute -top-1 h-2 w-2 rotate-45 bg-gray-900"></div>
            </div>
        </div>
    </div>
</div>
