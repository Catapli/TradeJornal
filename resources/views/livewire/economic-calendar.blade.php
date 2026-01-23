<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    {{-- Cabecera --}}
    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-globe text-indigo-500"></i>
            <h3 class="text-xs font-bold uppercase text-gray-700">Eventos Económicos</h3>
        </div>

        {{-- Botón Importar (Simulado) --}}
        <button class="text-[10px] font-bold text-indigo-500 hover:underline"
                wire:click="importKeyEvents"
                title="Cargar noticias importantes">
            <i class="fa-solid fa-cloud-arrow-down mr-1"></i> Auto
        </button>
    </div>

    {{-- Lista de Eventos --}}
    <div class="p-0">
        @if (count($events) > 0)
            <div class="divide-y divide-gray-100">
                @foreach ($events as $event)
                    <div class="group flex items-center justify-between px-4 py-2 transition hover:bg-gray-50">

                        {{-- Izquierda: Hora e Impacto --}}
                        <div class="flex items-center gap-3">
                            <span class="w-9 font-mono text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}
                            </span>

                            {{-- Semáforo --}}
                            <div class="flex w-6 flex-col items-center">
                                <span class="text-[9px] font-bold text-gray-400">{{ $event->currency }}</span>
                                @if ($event->impact == 'high')
                                    <div class="h-1.5 w-6 rounded-full bg-rose-500 shadow-sm shadow-rose-200"></div>
                                @elseif($event->impact == 'medium')
                                    <div class="h-1.5 w-6 rounded-full bg-amber-400"></div>
                                @else
                                    <div class="h-1.5 w-6 rounded-full bg-yellow-200"></div>
                                @endif
                            </div>

                            <span class="max-w-[120px] truncate text-xs font-bold text-gray-700"
                                  title="{{ $event->event }}">
                                {{ $event->event }}
                            </span>
                        </div>

                        {{-- Botón Borrar (Hover) --}}
                        <button class="text-gray-300 opacity-0 transition hover:text-rose-500 group-hover:opacity-100"
                                wire:click="deleteEvent({{ $event->id }})">
                            <i class="fa-solid fa-times text-xs"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 text-center">
                <p class="text-xs italic text-gray-400">Sin noticias relevantes registradas.</p>
            </div>
        @endif
    </div>

    {{-- Formulario de Añadir Rápido --}}
    <div class="border-t border-gray-200 bg-gray-50 p-3">
        <div class="mb-2 flex items-center gap-2">
            <input class="w-16 rounded border border-gray-200 p-1 text-xs focus:ring-indigo-500"
                   type="time"
                   wire:model="newTime">
            <select class="rounded border border-gray-200 p-1 text-xs focus:ring-indigo-500"
                    wire:model="newCurrency">
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
                <option value="JPY">JPY</option>
            </select>
            <select class="rounded border border-gray-200 p-1 text-xs focus:ring-indigo-500"
                    wire:model="newImpact">
                <option value="high">🔴 Alta</option>
                <option value="medium">🟠 Media</option>
                <option value="low">🟡 Baja</option>
            </select>
        </div>
        <div class="flex gap-2">
            <input class="w-full rounded border border-gray-200 p-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                   type="text"
                   wire:model="newEvent"
                   placeholder="Nombre del evento (ej: CPI)"
                   wire:keydown.enter="addEvent">
            <button class="rounded bg-gray-800 px-3 py-1 text-xs font-bold text-white transition hover:bg-black"
                    wire:click="addEvent">
                +
            </button>
        </div>
    </div>
</div>
