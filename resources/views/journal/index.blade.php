<x-app-layout>
    <x-slot name="header">
        <i class="fa-solid fa-book text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('menu.journal') }}
        </h2>
    </x-slot>

    <div class="py-2">
        @livewire('journal-page')
    </div>
</x-app-layout>
