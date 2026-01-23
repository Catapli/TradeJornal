<x-app-layout>
    <x-slot name="header">
        <i class="fa-solid fa-chart-simple text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('menu.trades') }}
        </h2>
    </x-slot>

    <div class="py-2">
        @livewire('trades-page')
    </div>
</x-app-layout>
