@props(['route', 'icon', 'name', 'color' => '', 'locked' => false, 'badge' => 0])

@php
    $isActive = request()->routeIs($route);
    // Bloqueado: se ve, se puede entrar y se encuentra el muro de PRO con la
    // pantalla real detrás. Antes estos ítems simplemente no se pintaban, así que
    // el usuario gratuito no sabía qué existía al otro lado.
    $iconColor = $isActive ? 'text-white' : ($locked ? 'text-gray-600 group-hover:text-gray-400' : 'text-gray-400 group-hover:text-white');
    $textColor = $isActive ? 'text-white' : ($locked ? 'text-gray-500' : 'text-gray-400 group-hover:text-white');
    $bgClass = $isActive ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-gray-800/80';
@endphp

{{--
    Ítem del carril lateral.

    El icono va dentro de una caja de 64px que, con el `px-2` del <nav>, lo deja
    centrado exactamente en los 80px del carril cerrado. Así el icono **no se
    mueve** al desplegarse: solo aparece la etiqueta a su derecha.

    El tooltip flotante que había antes ya no hace falta: al acercar el ratón se
    despliega el carril entero con todos los nombres, que además deja comparar
    unos con otros en vez de leerlos de uno en uno.
--}}
<li class="group relative w-full">
    <a class="{{ $bgClass }} flex h-11 w-full items-center rounded-xl transition-all duration-200 ease-out"
       href="{{ route($route) }}"
       wire:navigate>

        <span class="relative flex w-16 shrink-0 items-center justify-center">
            <i class="{{ $icon }} {{ $iconColor }} {{ $isActive ? '' : $color }} text-lg transition-transform duration-200 group-hover:scale-110"></i>

            @if ($locked)
                <span class="absolute -right-0.5 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-800 ring-2 ring-[#0B1120]"
                      aria-hidden="true">
                    <i class="fa-solid fa-lock text-[8px] text-amber-400"></i>
                </span>
            @elseif ($badge > 0)
                {{-- El contador es el recordatorio: con el carril cerrado sigue
                     viéndose, que es cuando hace falta. --}}
                <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-black text-white ring-2 ring-[#0B1120]">
                    {{ $badge > 99 ? '99+' : $badge }}
                </span>
            @endif
        </span>

        <span class="{{ $textColor }} flex min-w-0 items-center gap-2 whitespace-nowrap pr-4 text-sm font-semibold opacity-0 transition-opacity duration-200 group-hover/sidebar:opacity-100">
            <span class="truncate">{{ $name }}</span>

            @if ($locked)
                <span class="shrink-0 text-[10px] font-bold uppercase text-amber-400">{{ __('landing.gate.tag') }}</span>
            @endif
        </span>

        @if ($isActive)
            <span class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span>
        @endif
    </a>
</li>
