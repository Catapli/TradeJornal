<x-app-layout>
    {{-- pt-16 en vez de mt-[55px]: el navbar fijo mide h-16 (64px), así que con 55
         la tarjeta de datos de ejemplo quedaba 9px por debajo de la barra. --}}
    <div class="pt-16">
        <div class="relative z-50 mx-auto max-w-[1600px] px-4 pt-2 sm:px-6 lg:px-8">
            @livewire('burned-account-card')
            @livewire('sample-data-card')
        </div>

        <div class="py-2">
            @livewire('account-page')
        </div>
    </div>
</x-app-layout>
