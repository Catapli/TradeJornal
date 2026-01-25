<div class="min-h-screen bg-gray-50 pb-20"
     {{-- AQUÍ CONECTAMOS ALPINE CON LIVEWIRE --}}
     x-data="{
         impacts: @entangle('filterImpact').live,
         currencies: @entangle('filterCurrency').live
     }">

    {{-- HEADER (Sin cambios, funciona bien con Livewire) --}}
    <div class="sticky top-0 z-30 border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <h1 class="hidden items-center gap-2 text-xl font-bold text-gray-900 md:flex">
                    <i class="fa-solid fa-calendar-days text-indigo-600"></i> Calendario
                </h1>

                <div class="flex items-center rounded-lg bg-gray-100 p-1">
                    <button class="rounded-md p-2 text-gray-500 hover:bg-white hover:shadow-sm"
                            wire:click="prevDay"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="relative">
                        <input class="cursor-pointer border-none bg-transparent py-1 pl-2 pr-0 text-center font-mono text-sm font-bold text-gray-700 focus:ring-0"
                               type="date"
                               wire:model.live="selectedDate">
                    </div>
                    <button class="rounded-md p-2 text-gray-500 hover:bg-white hover:shadow-sm"
                            wire:click="nextDay"><i class="fa-solid fa-chevron-right"></i></button>
                    <button class="ml-2 rounded bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 hover:bg-indigo-100"
                            wire:click="setToday">HOY</button>
                </div>
            </div>

            <button class="flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-gray-700"
                    wire:click="syncWithApi"
                    wire:loading.attr="disabled">
                <span wire:loading.remove
                      wire:target="syncWithApi"><i class="fa-solid fa-cloud-arrow-down"></i> Sync</span>
                <span wire:loading
                      wire:target="syncWithApi"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
            </button>
        </div>
    </div>

    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-6 lg:grid-cols-4">

        {{-- BARRA LATERAL (CONTROLADA POR ALPINE) --}}
        <div class="space-y-6 lg:col-span-1">

            {{-- 1. FILTRO IMPACTO --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-xs font-bold uppercase text-gray-500">Impacto</h3>

                {{-- Opción Cualquiera: Modifica la variable Alpine INSTANTÁNEAMENTE --}}
                <div class="mb-2 flex cursor-pointer items-center gap-2 rounded p-1 hover:bg-gray-50"
                     @click="impacts = []"> {{-- ESTO ES LO QUE BUSCABAS --}}

                    <div class="flex h-4 w-4 items-center justify-center rounded-full border transition-colors"
                         :class="impacts.length === 0 ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                        <i class="fa-solid fa-check text-[10px] text-white"
                           x-show="impacts.length === 0"></i>
                    </div>
                    {{-- Clase dinámica basada en JS, no en PHP --}}
                    <span class="text-sm"
                          :class="impacts.length === 0 ? 'font-bold text-indigo-700' : 'text-gray-600'">Cualquiera</span>
                </div>

                <div class="space-y-1">
                    @foreach (['high', 'medium', 'low'] as $lvl)
                        <label class="flex cursor-pointer items-center gap-3 rounded p-1 hover:bg-gray-50">
                            {{-- x-model: Actualiza el JS localmente primero --}}
                            <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   type="checkbox"
                                   value="{{ $lvl }}"
                                   x-model="impacts">

                            <div class="flex text-xs text-amber-400">
                                <i class="fa-solid fa-star"></i>
                                <i class="{{ $lvl !== 'low' ? 'fa-solid fa-star' : 'fa-regular fa-star text-gray-300' }}"></i>
                                <i class="{{ $lvl === 'high' ? 'fa-solid fa-star' : 'fa-regular fa-star text-gray-300' }}"></i>
                            </div>
                            <span class="text-sm capitalize text-gray-700">{{ $lvl === 'high' ? 'Alta' : ($lvl === 'medium' ? 'Media' : 'Baja') }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 2. FILTRO DIVISAS --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-xs font-bold uppercase text-gray-500">Divisas</h3>

                <div class="mb-2 flex cursor-pointer items-center gap-2 rounded p-1 hover:bg-gray-50"
                     @click="currencies = []">

                    <div class="flex h-4 w-4 items-center justify-center rounded-full border transition-colors"
                         :class="currencies.length === 0 ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                        <i class="fa-solid fa-check text-[10px] text-white"
                           x-show="currencies.length === 0"></i>
                    </div>
                    <span class="text-sm"
                          :class="currencies.length === 0 ? 'font-bold text-indigo-700' : 'text-gray-600'">Todas</span>
                </div>

                <div class="grid grid-cols-2 gap-1">
                    @foreach (['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD'] as $curr)
                        <label class="flex cursor-pointer items-center gap-2 rounded p-1 hover:bg-gray-50">
                            <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   type="checkbox"
                                   value="{{ $curr }}"
                                   x-model="currencies">
                            <span class="text-sm font-bold text-gray-700">{{ $curr }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- TABLA (RENDERIZADA POR LIVEWIRE STANDARD) --}}
        <div class="lg:col-span-3">
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                {{-- LOADING OVERLAY PARA LA TABLA --}}
                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[1px]"
                     wire:loading.flex
                     wire:target="filterImpact, filterCurrency, prevDay, nextDay, setToday, selectedDate">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-600"></i>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase text-gray-500">Hora</th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase text-gray-500">Divisa</th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase text-gray-500">Impacto</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase text-gray-500">Evento</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase text-gray-500">Actual</th>
                            <th class="hidden px-6 py-3 text-right text-xs font-bold uppercase text-gray-500 sm:table-cell">Prev.</th>
                            <th class="hidden px-6 py-3 text-right text-xs font-bold uppercase text-gray-500 sm:table-cell">Ant.</th>
                        </tr>
                    </thead>
                    <tbody class="relative bg-white">
                        @php $linePrinted = false; @endphp

                        @forelse($events as $event)
                            {{-- LÍNEA DE TIEMPO (PHP) --}}
                            @if ($isToday && !$linePrinted && $event->time > $now)
                                <tr class="relative z-10">
                                    <td class="relative h-0 p-0"
                                        colspan="7">
                                        <div class="absolute -top-3 left-0 right-0 flex w-full items-center">
                                            <div class="h-px flex-grow bg-rose-500"></div>
                                            <div class="animate-pulse rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                                AHORA {{ substr($now, 0, 5) }}
                                            </div>
                                            <div class="h-px flex-grow bg-rose-500"></div>
                                        </div>
                                    </td>
                                </tr>
                                @php $linePrinted = true; @endphp
                            @endif

                            <tr class="{{ $isToday && $event->time < $now ? 'opacity-50 grayscale' : '' }} group transition-colors hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <img class="h-3 w-4 rounded-[1px] shadow-sm"
                                             src="https://flagcdn.com/24x18/{{ $flags[$event->currency] ?? 'un' }}.png">
                                        <span class="font-bold text-gray-700">{{ $event->currency }}</span>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <div class="flex justify-center gap-0.5 text-xs text-amber-400">
                                        <i class="fa-solid fa-star"></i>
                                        <i class="{{ $event->impact !== 'low' ? 'fa-solid fa-star' : 'fa-regular fa-star text-gray-300' }}"></i>
                                        <i class="{{ $event->impact === 'high' ? 'fa-solid fa-star' : 'fa-regular fa-star text-gray-300' }}"></i>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm font-bold text-gray-900 group-hover:text-indigo-600">
                                    {{ $event->event }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-black text-gray-900">{{ $event->actual ?? '-' }}</td>
                                <td class="hidden whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 sm:table-cell">{{ $event->forecast ?? '-' }}</td>
                                <td class="hidden whitespace-nowrap px-6 py-4 text-right text-sm text-gray-400 sm:table-cell">{{ $event->previous ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-12 text-center italic text-gray-400"
                                    colspan="7">
                                    No hay eventos con estos filtros.
                                </td>
                            </tr>
                        @endforelse

                        {{-- FIN DEL DÍA --}}
                        @if ($isToday && !$linePrinted && count($events) > 0)
                            <tr class="relative z-10">
                                <td class="relative h-0 p-0"
                                    colspan="7">
                                    <div class="absolute -top-3 left-0 right-0 flex w-full items-center">
                                        <div class="h-px flex-grow bg-rose-500"></div>
                                        <div class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">FIN DEL DÍA</div>
                                        <div class="h-px flex-grow bg-rose-500"></div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
