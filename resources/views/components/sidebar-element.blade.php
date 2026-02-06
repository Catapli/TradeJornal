@props(['route', 'icon', 'name', 'color' => ''])

@php
    $isActive = request()->routeIs($route);
    // Color base del icono: si está activo es blanco, si no es gris
    $iconColor = $isActive ? 'text-white' : 'text-gray-400 group-hover:text-white';
    // Fondo del botón: si está activo es indigo, si no transparente (hover gris oscuro)
    $bgClass = $isActive ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-gray-800/80';
@endphp

<li class="group relative flex w-full justify-center">
    <a class="{{ $bgClass }} relative flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 ease-out"
       href="{{ route($route) }}">

        {{-- Icono --}}
        <i class="{{ $icon }} {{ $iconColor }} {{ $isActive ? '' : $color }} text-lg transition-transform duration-200 group-hover:scale-110"></i>

        {{-- Indicador Activo (Barra lateral pequeña) --}}
        @if ($isActive)
            <div class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></div>
        @endif
    </a>

    {{-- TOOLTIP (Fuera del enlace, dentro del LI group) --}}
    <div class="pointer-events-none absolute left-[calc(100%+10px)] top-1/2 z-[100] -translate-y-1/2 opacity-0 transition-opacity delay-75 duration-200 group-hover:opacity-100">
        <div class="relative whitespace-nowrap rounded-md border border-gray-700 bg-[#0F172A] px-3 py-2 text-xs font-bold text-white shadow-xl">
            {{ $name }}
            {{-- Flechita izquierda del tooltip --}}
            <div class="absolute -left-1 top-1/2 -mt-1 h-2 w-2 rotate-45 transform border-b border-l border-gray-700 bg-[#0F172A]"></div>
        </div>
    </div>
</li>
