<x-app-layout>
    <div class="mt-[55px] py-2">
        <x-pro-gate :module="__('menu.journal')">
            @livewire('journal-page')
        </x-pro-gate>
    </div>
</x-app-layout>
