{{--
    Visor de la operación: gráfico, marcos temporales, volumen, EMA, pantalla
    completa y reproductor barra a barra (Fase 6 · P8).

    Vive aquí y no dentro del modal porque lo usan dos pantallas: el detalle de la
    operación y el repaso de errores. Tener dos copias del visor era exactamente el
    problema que ya teníamos con las dos formas de etiquetar.

    El `$slot` es para paneles que se superponen al gráfico —en el modal, la
    captura de pantalla—. Si viene vacío no se pintan los botones de pestaña:
    sin nada que alternar, sobran.

    Uso:
        <x-trade-chart :trade="$this->trade" />

        <x-trade-chart :trade="$this->trade">
            ... panel de la captura ...
        </x-trade-chart>
--}}
@props(['trade', 'gate' => null, 'height' => 'aspect-video'])

<div class="relative {{ $height }} w-full overflow-hidden rounded-2xl border border-gray-700 bg-gray-900 shadow-lg"
     data-path="{{ $trade?->chart_data_path ? route('trades.chart-data', $trade->id) : '' }}"
     data-entry="{{ $trade?->entry_price }}"
     data-exit="{{ $trade?->exit_price }}"
     data-dir="{{ $trade?->direction }}"
     data-mae="{{ $trade?->mae_price }}"
     data-mfe="{{ $trade?->mfe_price }}"
     {{-- `gate` es una condición del contenedor (el modal pasa `!isLoading`).
          Sin ella el visor se pinta directamente: la pantalla de repaso no tiene
          ningún estado de carga que esconderlo detrás. --}}
     @if ($gate)
         x-show="{{ $gate }}"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         style="display:none"
     @endif
     x-data="chartViewer({{ $trade?->chart_data_path || $slot->isEmpty() ? '\'chart\'' : '\'image\'' }})"
     {{-- Sin `@trade-selected.window` aquí: `chartViewer.init()` ya escucha ese
          evento y lo hace entero (para el reproductor y con MAE/MFE). Tener las
          dos escuchas hacía que la segunda pisara a la primera y las dos líneas
          de excursión se perdieran al pasar de operación con las flechas. --}}
     x-init="setTimeout(() => { if ($el.dataset.path) load($el.dataset.path, $el.dataset.entry, $el.dataset.exit, $el.dataset.dir, $el.dataset.mae, $el.dataset.mfe) }, 100)">

    {{-- BARRA DE HERRAMIENTAS (Sin cambios) --}}
    <div class="absolute left-4 top-4 z-30 flex items-center space-x-1 rounded-lg border border-gray-700/50 bg-gray-800/90 p-1 backdrop-blur-sm"
         wire:ignore>
        @if ($trade?->chart_data_path)
            <template x-for="tf in ['1m', '5m', '15m', '1h', '4h']">
                <button class="rounded px-2 py-1 text-[10px] font-bold text-gray-400 dark:text-gray-500 transition-all hover:text-white"
                        @click="changeTimeframe(tf)"
                        :class="currentTimeframe === tf ? 'bg-indigo-600 text-white shadow-md' : ''"
                        x-text="tf.toUpperCase()"></button>
            </template>
            <div class="mx-1 h-3 w-px bg-gray-600"></div>
            {{-- BOTÓN VOLUMEN --}}
            <button class="flex items-center space-x-1 rounded border border-transparent px-2 py-1 text-xs font-bold transition-all"
                    @click="toggleVol()"
                    :class="showVolume ? 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20' : 'text-gray-500 dark:text-gray-400 hover:text-gray-300 dark:hover:text-gray-600'"
                    title="{{ __('labels.show_hide_volume') }}">

                {{-- Icono de barras (FontAwesome o SVG manual) --}}
                <i class="fa-solid fa-chart-column"></i>
                <span>{{ __('labels.vol') }}</span>
            </button>
            {{-- BOTÓN EMA --}}
            <button class="ml-1 flex items-center space-x-1 rounded border border-transparent px-2 py-1 text-xs font-bold transition-all"
                    @click="toggleEma()"
                    :class="showEma ? 'text-amber-400 bg-amber-400/10 border-amber-400/20' : 'text-gray-500 dark:text-gray-400 hover:text-gray-300 dark:hover:text-gray-600'"
                    title="{{ __('labels.show_hide_ema') }}">

                {{-- Icono de línea --}}
                <i class="fa-solid fa-wave-square"></i>
                <span>{{ __('labels.ema_50') }}</span>
            </button>

            {{-- SEPARADOR FLEXIBLE (Empuja el siguiente botón a la derecha) --}}
            <div class="flex-grow"></div>
        @endif
        {{-- LADO DERECHO: pestañas (solo si hay panel que alternar) y pantalla completa --}}
        <div class="flex items-center space-x-1 rounded-lg border border-gray-700/50 bg-gray-800/90 p-1 backdrop-blur-sm">
            {{-- Botón Ver Gráfico --}}
            @if (!$slot->isEmpty() && $trade?->chart_data_path)
                <button class="flex items-center gap-2 rounded px-3 py-1 text-xs font-bold transition-all"
                        @click="activeTab = 'chart'"
                        :class="activeTab === 'chart' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 dark:text-gray-500 hover:text-white'">
                    <i class="fa-solid fa-chart-line"></i>
                    <span class="hidden sm:inline">{{ __('labels.chart') }}</span>
                </button>
            @endif

            {{-- Botón Ver Captura --}}
            @unless ($slot->isEmpty())
                <button class="flex items-center gap-2 rounded px-3 py-1 text-xs font-bold transition-all"
                        @click="activeTab = 'image'"
                        :class="activeTab === 'image' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 dark:text-gray-500 hover:text-white'">
                    <i class="fa-solid fa-image"></i>
                    <span class="hidden sm:inline">{{ __('labels.screenshot') }}</span>
                </button>

                <div class="mx-1 h-3 w-px bg-gray-600"></div>
            @endunless

            <button class="ml-2 px-2 text-gray-400 dark:text-gray-500 transition-colors hover:text-white"
                    @click="toggleFullscreen()"
                    :title="isFullscreen ? '{{ __('labels.exit_screen_complete') }}' : '{{ __('labels.screen_complete') }}'">

                {{-- Icono Cambiante --}}
                <template x-if="!isFullscreen">
                    <i class="fa-solid fa-expand"></i>
                </template>
                <template x-if="isFullscreen">
                    <i class="fa-solid fa-compress"></i>
                </template>
            </button>
        </div>

    </div>

    {{-- CONTENEDOR GRÁFICO --}}
    <div id="firstContainer"
         class="h-full w-full bg-gray-900"
         wire:ignore
         x-show="activeTab === 'chart'"
         x-ref="chartContainer"></div>

    {{-- REPRODUCTOR BARRA A BARRA (Fase 6 · P8).

         Vive fuera del contenedor del gráfico —ese lleva
         `wire:ignore` y Livewire no lo repinta nunca— y solo
         aparece si la operación tiene velas guardadas. Sin
         `chart_data_path` no hay nada que reproducir. --}}
    @if ($trade?->chart_data_path)
        <div class="absolute inset-x-0 bottom-0 z-30 flex flex-wrap items-center gap-2 border-t border-gray-700/50 bg-gray-800/95 px-3 py-2 backdrop-blur-sm"
             wire:ignore
             x-show="activeTab === 'chart' && hasData"
             style="display:none">

            {{-- Entrar y salir del reproductor --}}
            <button class="flex items-center gap-2 rounded px-2 py-1 text-xs font-bold transition-all"
                    type="button"
                    @click="toggleReplay()"
                    :class="replay ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white'"
                    :title="replay ? '{{ __('labels.replay_exit') }}' : '{{ __('labels.replay_enter') }}'">
                <i class="fa-solid fa-clapperboard"></i>
                <span class="hidden sm:inline">{{ __('labels.replay') }}</span>
            </button>

            <template x-if="replay">
                <div class="flex flex-1 flex-wrap items-center gap-2">

                    {{-- Atrás, play/pausa, adelante --}}
                    <button class="px-2 py-1 text-xs text-gray-400 transition-colors hover:text-white"
                            type="button"
                            @click="stepReplay(-1)"
                            title="{{ __('labels.replay_back') }}">
                        <i class="fa-solid fa-backward-step"></i>
                    </button>

                    <button class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs text-white transition hover:bg-indigo-500"
                            type="button"
                            @click="playPause()"
                            :title="playing ? '{{ __('labels.replay_pause') }}' : '{{ __('labels.replay_play') }}'">
                        <i class="fa-solid"
                           :class="playing ? 'fa-pause' : 'fa-play'"></i>
                    </button>

                    <button class="px-2 py-1 text-xs text-gray-400 transition-colors hover:text-white"
                            type="button"
                            @click="stepReplay(1)"
                            title="{{ __('labels.replay_forward') }}">
                        <i class="fa-solid fa-forward-step"></i>
                    </button>

                    {{-- Barra de progreso: arrastrar mueve el cursor --}}
                    <input class="h-1 min-w-[6rem] flex-1 cursor-pointer appearance-none rounded-full bg-gray-600 accent-indigo-500"
                           type="range"
                           min="1"
                           :max="total"
                           :value="cursor"
                           @input="seekReplay($event.target.value)">

                    <span class="min-w-[4.5rem] text-right text-[10px] font-bold tabular-nums text-gray-400"
                          x-text="cursor + ' / ' + total"></span>

                    {{-- Velocidad --}}
                    <template x-for="v in [1, 2, 4, 8]"
                              :key="v">
                        <button class="rounded px-1.5 py-0.5 text-[10px] font-bold transition-all"
                                type="button"
                                @click="setSpeed(v)"
                                :class="speed === v ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:text-gray-300'"
                                x-text="v + '×'"></button>
                    </template>
                </div>
            </template>
        </div>
    @endif

    {{ $slot }}

    {{-- LOADING OVERLAY --}}
    <div class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-gray-900/90"
         x-show="loading"
         x-transition>
        <i class="fa-solid fa-circle-notch fa-spin mb-2 text-2xl text-indigo-500"></i>
    </div>
</div>
