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
            @livewire('burned-account-card')

            {{-- Ritual pre-mercado: convive con el panel, no lo sustituye. --}}
            @livewire('pre-market-ritual')

            @livewire('onboarding-checklist')
            @livewire('sample-data-card')
        </div>

        @livewire('dashboard-page')
    </div>
</x-app-layout>
