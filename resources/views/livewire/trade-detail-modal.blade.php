<div class="fixed inset-0 z-[250] overflow-y-auto"
     x-data="{
         open: false,
         init() {
             // Escuchar evento global disparado desde cualquier parte (Dashboard, Tablas, etc.)
             window.addEventListener('open-trade-detail', event => {
                 this.open = true; // 1. Abrir visualmente YA (Instantáneo)
                 $wire.loadTradeData(event.detail.tradeId); // 2. Pedir datos al servidor
             });
         },
         close() {
             this.open = false;
             // Opcional: Podrías resetear variables visuales aquí si fuera necesario
         }
     }"
     x-show="open"
     @keydown.escape.window="close()"
     style="display: none;">

    {{-- Backdrop (Cierre instantáneo) --}}
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
         x-show="open"
         x-transition.opacity
         @click="close()">
    </div>

    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle"
              aria-hidden="true">&#8203;</span>

        {{-- CONTENEDOR MODAL --}}
        <div class="inline-block w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            {{-- HEADER: NAVEGACIÓN Y CIERRE --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                {{-- Navegación --}}
                <div class="flex items-center gap-3">
                    <button class="group flex items-center font-medium text-gray-500 transition hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-30"
                            wire:click="goToPrev"
                            @if (!$prevTradeId) disabled @endif>
                        <div class="mr-2 rounded-full bg-gray-100 p-2 transition group-hover:bg-indigo-100 group-disabled:bg-gray-50">
                            <i class="fa-solid fa-arrow-left text-sm"></i>
                        </div>
                        <span class="text-sm font-bold">Anterior</span>
                    </button>

                    <div class="mx-2 hidden h-4 w-px bg-gray-200 sm:block"></div>

                    <button class="group flex items-center font-medium text-gray-500 transition hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-30"
                            wire:click="goToNext"
                            @if (!$nextTradeId) disabled @endif>
                        <span class="mr-2 text-sm font-bold">Siguiente</span>
                        <div class="rounded-full bg-gray-100 p-2 transition group-hover:bg-indigo-100 group-disabled:bg-gray-50">
                            <i class="fa-solid fa-arrow-right text-sm"></i>
                        </div>
                    </button>
                </div>

                {{-- Botón Cerrar (Alpine Directo) --}}
                <button class="text-gray-400 transition-colors hover:text-gray-500"
                        @click="close()">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            {{-- CUERPO DEL MODAL --}}
            <div class="min-h-[500px] bg-white p-6">

                {{-- ESTADO DE CARGA (Skeleton) --}}
                {{-- Se muestra si $selectedTrade es null (carga inicial) --}}
                @if (!$selectedTrade)
                    <div class="w-full animate-pulse space-y-6">
                        <div class="flex justify-between">
                            <div class="h-8 w-1/3 rounded bg-gray-200"></div>
                            <div class="h-8 w-1/4 rounded bg-gray-200"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-6">
                            <div class="col-span-1 space-y-4">
                                <div class="h-40 rounded-xl bg-gray-200"></div>
                                <div class="h-24 rounded-xl bg-gray-200"></div>
                            </div>
                            <div class="col-span-2 h-80 rounded-xl bg-gray-200"></div>
                        </div>
                        <div class="mt-12 flex justify-center">
                            <span class="flex items-center gap-2 text-sm text-gray-400">
                                <i class="fa-solid fa-circle-notch fa-spin text-indigo-500"></i> Cargando operación...
                            </span>
                        </div>
                    </div>
                @else
                    {{-- CONTENIDO REAL DEL TRADE --}}
                    {{-- 'wire:loading.class' añade opacidad cuando navegas entre trades (Prev/Next) --}}
                    <div wire:loading.class="opacity-50 pointer-events-none"
                         wire:target="goToPrev, goToNext">

                        {{-- 1. CABECERA TRADE --}}
                        <div class="mb-4 flex items-end justify-between">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Ticket #{{ $selectedTrade->ticket }}</span>
                                <h2 class="mt-1 flex items-center gap-3 text-4xl font-black text-gray-900">
                                    {{ $selectedTrade->tradeAsset->name ?? 'N/A' }}
                                    <span class="{{ $selectedTrade->direction == 'long' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded-full px-3 py-1 text-sm font-bold uppercase tracking-wide">
                                        {{ $selectedTrade->direction }}
                                    </span>
                                </h2>
                            </div>
                            <div class="text-right">
                                <span class="block text-sm font-medium text-gray-500">Resultado Neto</span>
                                <span class="{{ $selectedTrade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-4xl font-black">
                                    {{ $selectedTrade->pnl >= 0 ? '+' : '' }}{{ number_format($selectedTrade->pnl, 2) }} $
                                </span>
                            </div>
                        </div>

                        {{-- 2. GRID PRINCIPAL --}}
                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

                            {{-- MISTAKE SELECTOR --}}
                            <div class="col-span-3">
                                <livewire:mistake-selector :trade="$selectedTrade"
                                                           :wire:key="'mistake-'.$selectedTrade->id" />
                            </div>

                            {{-- COLUMNA DATOS --}}
                            <div class="space-y-6 lg:col-span-1">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6">
                                    <h4 class="mb-4 text-xs font-bold uppercase text-gray-400">Datos de Ejecución</h4>
                                    <dl class="space-y-4 text-sm">
                                        <div class="flex justify-between border-b border-gray-200 pb-2">
                                            <dt class="text-gray-500">Entrada / Salida</dt>
                                            <dd class="font-mono font-bold text-gray-900">{{ $selectedTrade->entry_price }} <i class="fa-solid fa-arrow-right mx-1 text-xs text-gray-400"></i> {{ $selectedTrade->exit_price }}</dd>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-200 pb-2">
                                            <dt class="text-gray-500">Horario</dt>
                                            <dd class="text-right font-mono font-bold text-gray-900">
                                                {{ \Carbon\Carbon::parse($selectedTrade->entry_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedTrade->exit_time)->format('H:i') }}
                                                <span class="block text-[10px] font-normal text-gray-400">{{ \Carbon\Carbon::parse($selectedTrade->exit_time)->format('d M Y') }}</span>
                                            </dd>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-200 pb-2">
                                            <dt class="text-gray-500">Volumen</dt>
                                            <dd class="font-bold text-gray-900">{{ $selectedTrade->size }} lotes</dd>
                                        </div>

                                        {{-- BARRA MAE/MFE --}}
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Eficiencia</dt>
                                            <dd class="w-32 font-medium text-gray-900">
                                                @if ($selectedTrade->mae_price && $selectedTrade->mfe_price)
                                                    @php
                                                        $distMae = abs($selectedTrade->entry_price - $selectedTrade->mae_price);
                                                        $distMfe = abs($selectedTrade->entry_price - $selectedTrade->mfe_price);
                                                        $totalRange = $distMae + $distMfe ?: 0.00001;
                                                        $pctRed = ($distMae / $totalRange) * 100;
                                                        $pctGreen = ($distMfe / $totalRange) * 100;

                                                        $distExit = $selectedTrade->exit_price - $selectedTrade->entry_price;
                                                        if ($selectedTrade->direction == 'short') {
                                                            $distExit *= -1;
                                                        }

                                                        $markerPos = $pctRed + ($distExit / $totalRange) * 100;
                                                        $markerPos = max(0, min(100, $markerPos));

                                                        // Calculo Monetario Aprox
                                                        $priceDiff = abs($selectedTrade->exit_price - $selectedTrade->entry_price) ?: 0.0000001;
                                                        $valuePerPoint = abs($selectedTrade->pnl) / $priceDiff;
                                                        $maeMoney = abs(($selectedTrade->mae_price - $selectedTrade->entry_price) * $valuePerPoint) * -1;
                                                        $mfeMoney = abs(($selectedTrade->mfe_price - $selectedTrade->entry_price) * $valuePerPoint);
                                                    @endphp
                                                    <div class="group/bar relative mx-auto flex h-4 w-32 select-none items-center">
                                                        <div class="pointer-events-none absolute inset-x-0 flex h-1.5 overflow-hidden rounded-full">
                                                            <div class="h-full bg-rose-400"
                                                                 style="width: {{ $pctRed }}%"></div>
                                                            <div class="z-10 h-full w-[2px] bg-white opacity-50"></div>
                                                            <div class="h-full bg-emerald-400"
                                                                 style="width: {{ $pctGreen }}%"></div>
                                                        </div>
                                                        <div class="absolute inset-0 flex h-full w-full items-center">
                                                            <div class="group/red relative h-4 w-full cursor-help"
                                                                 style="width: {{ $pctRed }}%">
                                                                <div
                                                                     class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-rose-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/red:block">
                                                                    Riesgo: {{ number_format($maeMoney, 0) }} $
                                                                </div>
                                                            </div>
                                                            <div class="group/green relative h-4 w-full cursor-help"
                                                                 style="width: {{ $pctGreen }}%">
                                                                <div
                                                                     class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                                    Potencial: +{{ number_format($mfeMoney, 0) }} $
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="pointer-events-none absolute z-20 h-2.5 w-1 rounded-full bg-gray-900 shadow-sm ring-1 ring-white"
                                                             style="left: {{ $markerPos }}%; transform: translateX(-50%);"></div>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-300">-</span>
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="rounded-2xl border border-yellow-100 bg-yellow-50 p-5">
                                    <h4 class="mb-2 flex items-center gap-2 text-xs font-bold uppercase text-yellow-700">
                                        <i class="fa-regular fa-note-sticky"></i> Notas
                                    </h4>
                                    <p class="text-sm italic leading-relaxed text-yellow-900">
                                        {{ $selectedTrade->notes ?: 'Sin notas registradas.' }}
                                    </p>
                                </div>
                            </div>

                            {{-- COLUMNA GRÁFICO + IA --}}
                            <div class="space-y-6 lg:col-span-2">

                                {{-- GRÁFICO --}}
                                <div class="relative aspect-video w-full overflow-hidden rounded-2xl border border-gray-700 bg-gray-900 shadow-lg"
                                     x-data="chartViewer()"
                                     x-init="$nextTick(() => {
                                         window.addEventListener('trade-selected', (e) => load(e.detail.path));
                                         @if($selectedTrade->chart_data_path)
                                         load('{{ $selectedTrade->chart_data_path }}', {{ $selectedTrade->entry_price }}, {{ $selectedTrade->exit_price }}, '{{ $selectedTrade->direction }}');
                                         @endif
                                     })"
                                     wire:ignore>

                                    {{-- Barra Herramientas Gráfico --}}
                                    <div class="absolute left-4 top-4 z-30 flex items-center space-x-1 rounded-lg border border-gray-700/50 bg-gray-800/90 p-1 backdrop-blur-sm">
                                        <template x-for="tf in ['1m', '5m', '15m', '1h', '4h']">
                                            <button class="rounded px-2 py-1 text-[10px] font-bold text-gray-400 transition-all hover:text-white"
                                                    @click="changeTimeframe(tf)"
                                                    :class="currentTimeframe === tf ? 'bg-indigo-600 text-white shadow-md' : ''"
                                                    x-text="tf.toUpperCase()"></button>
                                        </template>
                                        <div class="mx-1 h-3 w-px bg-gray-600"></div>
                                        <button class="px-2 py-1 text-[10px] font-bold text-gray-400 hover:text-white"
                                                @click="toggleVol()">VOL</button>
                                        <button class="ml-1 px-2 text-gray-400 hover:text-white"
                                                @click="toggleFullscreen()"><i class="fa-solid fa-expand"></i></button>
                                    </div>

                                    <div id="firstContainer"
                                         class="h-full w-full"
                                         x-ref="chartContainer"></div>

                                    {{-- Loading Overlay --}}
                                    <div class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-gray-900/90"
                                         x-show="loading"
                                         x-transition>
                                        <i class="fa-solid fa-circle-notch fa-spin mb-2 text-2xl text-indigo-500"></i>
                                    </div>

                                    {{-- Fallback Imagen --}}
                                    <div class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-gray-900"
                                         x-show="!loading && !hasData"
                                         style="display: none;">
                                        @if ($selectedTrade->screenshot_path)
                                            <img class="absolute inset-0 h-full w-full object-contain opacity-60"
                                                 src="{{ Storage::url($selectedTrade->screenshot_path) }}">
                                            <a class="relative z-30 rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur-md"
                                               href="{{ Storage::url($selectedTrade->screenshot_path) }}"
                                               target="_blank">Ver Imagen Original</a>
                                        @else
                                            <i class="fa-solid fa-chart-line mb-3 text-5xl text-gray-700 opacity-50"></i>
                                            <span class="text-xs text-gray-500">Sin gráfico disponible</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- IA --}}
                                <div class="relative overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm">
                                    <div class="relative z-10 mb-4 flex items-start justify-between">
                                        <div>
                                            <h4 class="flex items-center gap-2 text-sm font-bold text-indigo-900">
                                                <i class="fa-solid fa-brain text-indigo-600"></i> Análisis del Mentor
                                            </h4>
                                            @if (!$selectedTrade->ai_analysis)
                                                <p class="mt-1 text-xs text-indigo-600">
                                                    Obtén feedback instantáneo sobre tu ejecución.
                                                </p>
                                            @endif
                                        </div>

                                        @if (!$selectedTrade->ai_analysis)
                                            <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                    wire:click="analyzeIndividualTrade"
                                                    wire:loading.attr="disabled">
                                                <span wire:loading.remove
                                                      wire:target="analyzeIndividualTrade">Analizar</span>
                                                <span wire:loading
                                                      wire:target="analyzeIndividualTrade"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                                            </button>
                                        @endif
                                    </div>

                                    @if ($selectedTrade->ai_analysis)
                                        <div class="prose prose-sm rounded-lg border border-indigo-50/50 bg-white/50 p-3 text-sm text-gray-800">
                                            {!! Str::markdown($selectedTrade->ai_analysis) !!}
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
