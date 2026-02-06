<x-app-layout>
    {{--    {{~~ <x-slot name="header">
        {{~~ <i class="fa-solid fa-calendar-days text-2xl"></i>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('menu.calendar') }}
        </h2> ~~}}
    </x-slot> --}}

    <div class="py-2">
        @livewire('economic-calendar-page')
    </div>
</x-app-layout>
