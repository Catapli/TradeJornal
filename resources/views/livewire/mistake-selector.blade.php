<div class="mt-4 rounded-xl border border-rose-100 bg-white shadow-sm transition-all duration-300"
     x-data="{ open: false }"> {{-- Estado Alpine para abrir/cerrar --}}

    {{-- CABECERA (Siempre visible) --}}
    <div class="flex cursor-pointer items-center justify-between bg-rose-50/50 px-4 py-3"
         @click="open = !open">

        <div class="flex items-center gap-3">
            <h4 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-rose-800">
                <i class="fa-solid fa-bug"></i> {{ __('labels.errors_audit') }}
            </h4>

            {{-- Badge de aviso IA (Solo si hay sugerencias) --}}
            @if (count($suggestions) > 0)
                <span class="inline-flex animate-pulse items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                    <i class="fa-solid fa-lightbulb"></i> {{ count($suggestions) }} {{ __('labels.suggestions') }}
                </span>
            @endif
        </div>

        {{-- Flecha Toggle --}}
        <button class="text-rose-400 transition-transform duration-200 hover:text-rose-600"
                :class="open ? 'rotate-180' : ''">
            <i class="fa-solid fa-chevron-down"></i>
        </button>
    </div>

    {{-- CUERPO (Colapsable) --}}
    <div class="px-4 pb-4 pt-2"
         x-show="open"
         x-collapse>

        {{-- ⚠️ INFORME DEL FISCAL (Texto más grande y legible) --}}
        @if (count($suggestions) > 0)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                <p class="mb-2 flex items-center gap-2 text-xs font-bold uppercase text-amber-800">
                    <i class="fa-solid fa-user-secret text-sm"></i> {{ __('labels.analysis_detected_patterns') }}
                </p>
                <ul class="space-y-1.5">
                    @foreach ($suggestions as $suggestion)
                        <li class="flex items-start gap-2 text-xs text-amber-900">
                            <i class="fa-solid fa-arrow-right mt-0.5 text-amber-600"></i>
                            <span>
                                {{ __('labels.posible') }} <strong class="font-bold">{{ $suggestion['name'] }}</strong>:
                                <span class="font-medium text-amber-800/80">{{ $suggestion['reason'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- NUBE DE TAGS --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($availableMistakes as $mistake)
                @php
                    $isActive = in_array($mistake->id, $selectedMistakes);
                    $isSuggested = collect($suggestions)->contains('name', $mistake->name);

                    // Estilos Base
                    $class = 'group relative flex cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-bold transition-all ';

                    if ($isActive) {
                        // 1. ACTIVO (Marcado por usuario): Rojo Sólido
                        $class .= 'bg-rose-600 border-rose-600 text-white shadow-md transform scale-105';
                    } elseif ($isSuggested) {
                        // 2. SUGERIDO (Fiscal): Ámbar muy visible (Borde grueso, fondo suave)
                        $class .= 'bg-amber-50 border-amber-400 text-amber-800 shadow-sm ring-1 ring-amber-400 hover:bg-amber-100';
                    } else {
                        // 3. NORMAL: Blanco / Gris
                        $class .= 'bg-white border-gray-200 text-gray-500 hover:border-rose-300 hover:text-rose-500 hover:shadow-sm';
                    }
                @endphp

                <button class="{{ $class }}"
                        wire:click="toggleMistake({{ $mistake->id }})">
                    <span>{{ $mistake->name }}</span>

                    {{-- Iconos de estado --}}
                    @if ($isActive)
                        <i class="fa-solid fa-check text-[10px]"></i>
                    @elseif($isSuggested)
                        <i class="fa-solid fa-exclamation-circle animate-bounce text-amber-600"></i>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- FEEDBACK PIE --}}
        @if (count($selectedMistakes) > 0)
            <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-2">
                <div class="flex -space-x-1">
                    @foreach ($selectedMistakes as $id)
                        <div class="h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></div>
                    @endforeach
                </div>
                <p class="text-xs font-medium text-gray-400">
                    {{ count($selectedMistakes) }} {{ __('labels.confirmed_errors') }}
                </p>
            </div>
        @endif
    </div>
</div>
