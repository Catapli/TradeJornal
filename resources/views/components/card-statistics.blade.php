@props(['label' => '', 'icono' => '', 'key' => '', 'tooltip' => '', 'align' => 'center'])

@php
    // Definimos las clases según la posición para que no se corte en los bordes
    $alignClasses =
        [
            'center' => 'left-1/2 -translate-x-1/2',
            'right' => 'right-0', // Se pega al borde derecho del icono y crece a la izquierda
            'left' => 'left-0', // Se pega al borde izquierdo del icono y crece a la derecha
        ][$align] ?? 'left-1/2 -translate-x-1/2';

    // Ajustamos la flechita para que coincida con el alineado
    $arrowClasses =
        [
            'center' => 'left-1/2 -translate-x-1/2',
            'right' => 'right-2',
            'left' => 'left-2',
        ][$align] ?? 'left-1/2 -translate-x-1/2';
@endphp

<div {!! $attributes->merge(['class' => 'grid grid-cols-12 rounded-lg border border-gray-500 py-2 m-2']) !!}>
    <div class="col-span-2 flex items-center justify-center text-xl">
        <span class="h-11 w-11 rounded-xl border border-green-700 p-2 text-center text-green-700">{!! $icono !!}</span>
    </div>

    <div class="col-span-7 flex flex-col">
        <span class="text-sm text-gray-500">{{ $label }}</span>
        <span class="font-bold">{{ $key }}</span>
    </div>

    <div class="col-span-3 flex items-center justify-end px-3">
        <div class="group relative flex flex-col items-center">
            <!-- Icono con peer para disparar el hover -->
            <i class="fa-solid fa-circle-info peer cursor-help text-gray-400 hover:text-gray-600"></i>

            <!-- Tooltip -->
            <div class="{{ $alignClasses }} pointer-events-none absolute top-full z-30 mt-2 w-64 max-w-[80vw] translate-y-2 scale-95 rounded-lg bg-neutral-900 p-3 text-center text-sm text-white opacity-0 shadow-xl transition-all duration-300 ease-out peer-hover:translate-y-0 peer-hover:scale-100 peer-hover:opacity-100 dark:bg-white dark:text-neutral-900"
                 role="tooltip">

                {{ $tooltip }}

                <!-- Flechita -->
                <div class="{{ $arrowClasses }} absolute -top-1 h-2 w-2 rotate-45 bg-neutral-900 dark:bg-white"></div>
            </div>
        </div>
    </div>
</div>
