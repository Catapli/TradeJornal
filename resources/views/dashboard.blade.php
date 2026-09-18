<x-app-layout>

    {{-- pt-16 = alto real del navbar fijo (h-16, z-[40]).

         El hueco se reserva aquí, una sola vez y para toda la página. Antes lo
         ponía el <header> de dashboard-page con mt-[55px], así que cualquier cosa
         colocada por encima de él —como las tarjetas de puesta en marcha— nacía
         debajo de la barra y quedaba recortada. --}}
    <div class="pb-4 pt-16">

        {{-- Puesta en marcha y datos de ejemplo: solo se pintan mientras hacen falta.
             z-50 los deja por encima del navbar aunque crezcan. --}}
        <div class="relative z-50 mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
            {{-- La autopsia de la cuenta quemada va fuera del bloque plegable: no
                 es puesta en marcha, aparece una sola vez y se cierra sola. --}}
            @livewire('burned-account-card')

            {{-- BLOQUE DE PUESTA EN MARCHA
                 Las tres tarjetas se pliegan juntas y dejan una sola línea. Una a
                 una no servía de nada: tres tarjetas plegadas siguen ocupando tres
                 barras encima de los números, que es lo que molestaba. --}}
            <div x-data="tfStartupBlock()">

                <div class="mb-3 flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-2 dark:border-gray-700 dark:bg-gray-800"
                     x-cloak
                     x-show="cards > 0">
                    <span class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200">
                        <i class="fa-solid fa-rocket text-xs text-indigo-500"></i>
                        {{ __('labels.startup_block') }}
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-black text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                              x-text="cards"></span>
                    </span>

                    <button class="flex items-center gap-2 text-xs font-bold text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                            type="button"
                            @click="toggle()"
                            :aria-expanded="(!min).toString()"
                            x-text="min ? @js(__('labels.card_expand')) : @js(__('labels.card_minimize'))">
                    </button>
                </div>

                <div x-ref="cards"
                     x-show="!min"
                     x-collapse>
                    {{-- Ritual pre-mercado: convive con el panel, no lo sustituye. --}}
                    @livewire('pre-market-ritual')

                    @livewire('onboarding-checklist')
                    @livewire('sample-data-card')
                </div>
            </div>
        </div>

        @livewire('dashboard-page')
    </div>
</x-app-layout>
