<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    {{-- Cabecera --}}
    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-globe text-indigo-500"></i>
            <h3 class="text-xs font-bold uppercase text-gray-700">{{ __('labels.economics_events') }}</h3>
        </div>
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

                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 text-center">
                <p class="text-xs italic text-gray-400">{{ __('labels.not_events_importants') }}</p>
            </div>
        @endif
    </div>
</div>
