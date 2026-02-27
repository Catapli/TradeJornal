@props(['route', 'icon', 'name', 'color' => ''])

@php
    $isActive = request()->routeIs($route);
    $iconColor = $isActive ? 'text-white' : 'text-gray-400 group-hover:text-white';
    $bgClass = $isActive ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-gray-800/80';
@endphp

{{-- ✅ FIX: x-data + @mouseenter para calcular posición Y del tooltip dinámicamente --}}
{{-- Esto resuelve el clipping del tooltip cuando el padre tiene overflow-y-auto --}}
<li class="group relative flex w-full justify-center"
    x-data="{ showTip: false, tipY: 0 }"
    @mouseenter="showTip = true; tipY = $el.getBoundingClientRect().top + $el.getBoundingClientRect().height / 2"
    @mouseleave="showTip = false">
    <a class="{{ $bgClass }} relative flex h-11 w-11 items-center justify-center rounded-xl transition-all duration-200 ease-out"
       href="{{ route($route) }}"
       wire:navigate>

        <i class="{{ $icon }} {{ $iconColor }} {{ $isActive ? '' : $color }} text-lg transition-transform duration-200 group-hover:scale-110"></i>

        @if ($isActive)
            <div class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></div>
        @endif
    </a>

    {{-- ✅ FIX: Tooltip con position fixed + Y calculado dinámicamente --}}
    {{-- Así nunca queda cortado por overflow-y-auto del contenedor padre --}}
    <div class="pointer-events-none fixed left-[88px] z-[200]"
         style="top: 0px;"
         :style="`top: ${tipY}px; transform: translateY(-50%)`"
         x-show="showTip"
         x-transition:enter="transition-opacity duration-150 delay-75"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="relative whitespace-nowrap rounded-md border border-gray-700 bg-[#0F172A] px-3 py-2 text-xs font-bold text-white shadow-xl">
            {{ $name }}
            <div class="absolute -left-1 top-1/2 -mt-1 h-2 w-2 rotate-45 transform border-b border-l border-gray-700 bg-[#0F172A]"></div>
        </div>
    </div>
</li>
