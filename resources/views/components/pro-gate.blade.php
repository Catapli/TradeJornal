@props(['module'])

{{--
    Muro PRO visible.

    Hasta la Fase 1 del ROADMAP, los módulos de pago simplemente no aparecían en el
    sidebar: el usuario gratuito no sabía siquiera qué estaría comprando. Ahora la
    pantalla real se renderiza detrás, difuminada e inerte, con la tarjeta de venta
    encima y un enlace a la demo, donde ese mismo módulo se ve lleno de datos.

    Uso:
        <x-pro-gate :module="__('menu.laboratory')">
            @livewire('reports-page')
        </x-pro-gate>

    Si el usuario ya es PRO, el componente se aparta y deja pasar el contenido.
--}}

@if (auth()->user()?->hasProAccess())
    {{ $slot }}
@else
    <div class="relative">

        {{-- Pantalla real, difuminada e inaccesible al teclado y al ratón --}}
        <div class="pointer-events-none max-h-[75vh] select-none overflow-hidden blur-[6px] saturate-50"
             aria-hidden="true"
             inert>
            {{ $slot }}
        </div>

        {{-- Velo para que el texto de la tarjeta se lea sobre cualquier contenido --}}
        <div class="absolute inset-0 bg-gradient-to-b from-white/70 via-white/85 to-white dark:from-gray-900/70 dark:via-gray-900/90 dark:to-gray-900"></div>

        {{-- Tarjeta de venta --}}
        <div class="absolute inset-0 flex items-start justify-center px-4 pt-24">
            <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-2xl dark:border-gray-700 dark:bg-gray-800">

                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <i class="fa-solid fa-lock text-[10px]"></i>
                    {{ __('landing.gate.badge') }}
                </span>

                <h2 class="mt-4 text-2xl font-black text-gray-900 dark:text-gray-100">
                    {{ __('landing.gate.title', ['module' => $module]) }}
                </h2>

                <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ __('landing.gate.lead', ['module' => $module]) }}
                </p>

                <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800 sm:w-auto"
                       href="{{ route('pricing') }}">
                        {{ __('landing.gate.cta') }}
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>

                    {{-- La demo es donde este módulo se ve con datos reales de ejemplo.
                         En una sesión de demo el enlace sobra: ya está dentro. --}}
                    @unless (\App\Support\Demo::active())
                        <a class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 px-6 py-3 text-sm font-bold text-gray-900 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700 sm:w-auto"
                           href="{{ route('demo.enter') }}">
                            <i class="fa-solid fa-play text-xs"></i>
                            {{ __('landing.gate.demo_cta') }}
                        </a>
                    @endunless
                </div>
            </div>
        </div>
    </div>
@endif
