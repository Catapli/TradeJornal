<x-app-layout>
    <x-slot name="header">
        <i class="fa-solid fa-city text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Entidades') }}
        </h2>
    </x-slot>

    <div class="py-4">
        @livewire('town')
    </div>
</x-app-layout>
