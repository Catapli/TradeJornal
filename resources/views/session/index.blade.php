<x-app-layout>
    {{-- mt-16 = alto real del navbar fijo (h-16); el módulo ocupa el resto exacto del viewport --}}
    <div class="mt-16">
        <x-pro-gate :module="__('menu.session')">
            @livewire('session-page')
        </x-pro-gate>
    </div>
</x-app-layout>
