<x-app-layout>
    <x-slot name="header">
        <i class="fa-solid fa-file-lines text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Logs') }}
        </h2>
    </x-slot>

    <div class="py-4">
        @livewire('logs')
    </div>
</x-app-layout>
