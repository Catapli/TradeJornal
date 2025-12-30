<x-app-layout>
    <x-slot name="header">
        <i class="fa-solid fa-user-group text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('menu.users') }}
        </h2>
    </x-slot>

    <div class="py-4">
        @livewire('users-page')
    </div>
</x-app-layout>
