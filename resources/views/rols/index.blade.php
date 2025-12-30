<x-app-layout>
    <x-slot name="header">
        <i class="fa-solid fa-users-gear text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('menu.rols') }}
        </h2>
    </x-slot>

    <div class="py-4">
        @livewire('rols-page')
    </div>
</x-app-layout>
