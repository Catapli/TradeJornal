<x-app-layout>
    {{-- Módulo PRO: la pantalla se renderiza siempre y el muro decide si se ve
         nítida o difuminada. El corte real lo hace RequiresProAccess en servidor. --}}
    <x-pro-gate :module="__('menu.weekly_review')">
        @livewire('weekly-review-page')
    </x-pro-gate>
</x-app-layout>
