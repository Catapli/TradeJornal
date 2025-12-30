<x-app-layout>
    <x-slot name="header">
        <i class="fas fa-chart-bar text-xl text-black"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard') }}
        </h2>

    </x-slot>


    <div class="py-4">
        @livewire('dashboard-page')
    </div>
</x-app-layout>
