<div class="min-h-screen bg-gray-50 pb-20">

    {{-- CONTENEDOR PRINCIPAL CON ESTADO ALPINE --}}
    <div x-data="{
        initialLoad: true,
        init() {
            // Cuando Livewire termine de cargar sus scripts y efectos, quitamos el loader
            document.addEventListener('livewire:initialized', () => {
                this.initialLoad = false;
            });
    
            // Fallback de seguridad: por si Livewire ya cargó antes de este script
            setTimeout(() => { this.initialLoad = false }, 800);
        }
    }">

        {{-- 1. LOADER DE CARGA INICIAL (Pantalla completa al refrescar) --}}
        {{-- Se muestra mientras 'initialLoad' sea true. Tiene z-index máximo (z-50) --}}
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Aquí tu componente loader --}}
            <div class="flex flex-col items-center">
                <x-loader />
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400">Cargando Dashboard...</span>
            </div>
        </div>
    </div>

    {{-- HEADER FIJO --}}
    <div class="sticky top-0 z-30 border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <h1 class="flex items-center gap-2 text-xl font-bold text-gray-900">
                <i class="fa-solid fa-calendar-days text-indigo-600"></i> Calendario Económico
            </h1>

            {{-- BOTÓN SYNC --}}
            <button class="flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-gray-700"
                    wire:click="syncWithApi"
                    wire:loading.attr="disabled">

                <span wire:loading.remove
                      wire:target="syncWithApi">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Sincronizar
                </span>

                <span wire:loading
                      wire:target="syncWithApi">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> Cargando...
                </span>
            </button>

            {{-- Navegación Fecha --}}
            <div class="flex items-center rounded-lg bg-gray-100 p-1">
                <button class="rounded-md p-2 text-gray-500 shadow-sm transition hover:bg-white"
                        wire:click="prevDay"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="min-w-[140px] px-4 text-center font-mono font-bold text-gray-700">
                    {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('D, d M Y') }}
                </div>
                <button class="rounded-md p-2 text-gray-500 shadow-sm transition hover:bg-white"
                        wire:click="nextDay"><i class="fa-solid fa-chevron-right"></i></button>
                <button class="ml-2 rounded bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 hover:bg-indigo-100"
                        wire:click="setToday">HOY</button>
            </div>
        </div>
    </div>

    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-6 lg:grid-cols-4">

        {{-- BARRA LATERAL: FILTROS --}}
        <div class="space-y-6 lg:col-span-1">

            {{-- Filtro Impacto --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-xs font-bold uppercase text-gray-500">Impacto</h3>
                <div class="space-y-2">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input class="rounded border-gray-300 text-rose-500 focus:ring-rose-500"
                               type="checkbox"
                               value="high"
                               wire:model.live="filterImpact">
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <span class="h-3 w-3 rounded-full bg-rose-500"></span> Alta
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input class="rounded border-gray-300 text-amber-500 focus:ring-amber-500"
                               type="checkbox"
                               value="medium"
                               wire:model.live="filterImpact">
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <span class="h-3 w-3 rounded-full bg-amber-400"></span> Media
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input class="rounded border-gray-300 text-yellow-400 focus:ring-yellow-400"
                               type="checkbox"
                               value="low"
                               wire:model.live="filterImpact">
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <span class="h-3 w-3 rounded-full bg-yellow-300"></span> Baja
                        </span>
                    </label>
                </div>
            </div>

            {{-- Filtro Divisas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-xs font-bold uppercase text-gray-500">Divisas</h3>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD'] as $curr)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   type="checkbox"
                                   value="{{ $curr }}"
                                   wire:model.live="filterCurrency">
                            <span class="text-sm font-bold text-gray-700">{{ $curr }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- TABLA PRINCIPAL --}}
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Hora</th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Impacto</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Evento</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Actual</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Prev.</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Ant.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($events as $event)
                            <tr class="transition-colors hover:bg-gray-50">
                                {{-- Hora --}}
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}
                                </td>

                                {{-- Divisa + Impacto --}}
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <span
                                          class="@if ($event->impact == 'high') bg-rose-100 text-rose-800 border border-rose-200
                                        @elseif($event->impact == 'medium') bg-amber-100 text-amber-800 border border-amber-200
                                        @else bg-yellow-100 text-yellow-800 border border-yellow-200 @endif inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium">
                                        {{ $event->currency }}
                                    </span>
                                </td>

                                {{-- Nombre Evento --}}
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">
                                    {{ $event->event }}
                                </td>

                                {{-- Actual --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-gray-900">
                                    {{ $event->actual ?? '-' }}
                                </td>

                                {{-- Previsión --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                                    {{ $event->forecast ?? '-' }}
                                </td>

                                {{-- Anterior --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-400">
                                    {{ $event->previous ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-12 text-center italic text-gray-400"
                                    colspan="6">
                                    <i class="fa-solid fa-mug-hot mb-2 text-2xl opacity-50"></i>
                                    <p>No hay eventos registrados para este día.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
