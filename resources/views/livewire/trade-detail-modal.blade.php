<div class="fixed inset-0 z-[250] overflow-y-auto"
     x-data="tradeDetail"
     x-show="open"
     {{-- 2. ESCUCHAR EL AVISO DE PHP PARA QUITAR SKELETON --}}
     @trade-data-loaded.window="isLoading = false"
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
        <div class="inline-block w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle dark:bg-gray-800"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            {{-- HEADER: NAVEGACIÓN Y CIERRE --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                {{-- Navegación --}}
                <div class="flex items-center gap-3">
                    {{-- Botón ANTERIOR --}}
                    <button class="group flex items-center font-medium transition disabled:cursor-not-allowed"
                            @click="isLoading = true; $wire.goToPrev()"
                            :disabled="!$wire.prevTradeId || isLoading"
                            :class="$wire.prevTradeId && !isLoading ?
                                'text-gray-500 dark:text-gray-400 hover:text-indigo-600' :
                                'text-gray-300 dark:text-gray-600 pointer-events-none'">
                        <div class="mr-2 rounded-full p-2 transition"
                             :class="$wire.prevTradeId && !isLoading ?
                                 'bg-gray-100 group-hover:bg-indigo-100 dark:bg-gray-700 dark:group-hover:bg-indigo-500/30' :
                                 'bg-gray-50 dark:bg-gray-800'">
                            <i class="fa-solid fa-arrow-left text-sm transition"
                               :class="$wire.prevTradeId && !isLoading ?
                                   'text-gray-500 dark:text-gray-400 group-hover:text-indigo-600' :
                                   'text-gray-300 dark:text-gray-600'"></i>
                        </div>
                        <span class="text-sm font-bold transition"
                              :class="$wire.prevTradeId && !isLoading ? 'text-gray-600 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600'">
                            {{ __('labels.previous') }}
                        </span>
                    </button>

                    <div class="mx-2 hidden h-4 w-px bg-gray-200 sm:block dark:bg-gray-700"></div>

                    {{-- Botón SIGUIENTE --}}
                    <button class="group flex items-center font-medium transition disabled:cursor-not-allowed"
                            @click="isLoading = true; $wire.goToNext()"
                            :disabled="!$wire.nextTradeId || isLoading"
                            :class="$wire.nextTradeId && !isLoading ?
                                'text-gray-500 dark:text-gray-400 hover:text-indigo-600' :
                                'text-gray-300 dark:text-gray-600 pointer-events-none'">
                        <span class="mr-2 text-sm font-bold transition"
                              :class="$wire.nextTradeId && !isLoading ? 'text-gray-600 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600'">
                            {{ __('labels.next') }}
                        </span>
                        <div class="rounded-full p-2 transition"
                             :class="$wire.nextTradeId && !isLoading ?
                                 'bg-gray-100 group-hover:bg-indigo-100 dark:bg-gray-700 dark:group-hover:bg-indigo-500/30' :
                                 'bg-gray-50 dark:bg-gray-800'">
                            <i class="fa-solid fa-arrow-right text-sm transition"
                               :class="$wire.nextTradeId && !isLoading ?
                                   'text-gray-500 dark:text-gray-400 group-hover:text-indigo-600' :
                                   'text-gray-300 dark:text-gray-600'"></i>
                        </div>
                    </button>

                </div>

                {{-- Botón Cerrar (Alpine Directo) --}}
                <button class="text-gray-400 dark:text-gray-500 transition-colors hover:text-gray-500 dark:hover:text-gray-400"
                        @click="close()">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="min-h-[500px] bg-white p-6 dark:bg-gray-800">

                {{-- A. SKELETON DE CARGA (Controlado por Alpine) --}}
                {{-- Se muestra mientras isLoading es true --}}
                <div class="w-full animate-pulse space-y-6"
                     x-show="isLoading"
                     style="display: none;">
                    <div class="flex justify-between">
                        <div class="h-8 w-1/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-8 w-1/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-6">
                        <div class="col-span-1 space-y-4">
                            <div class="h-40 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-24 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="col-span-2 h-80 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                    <div class="mt-12 flex justify-center">
                        <span class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-circle-notch fa-spin text-indigo-500"></i> {{ __('labels.loading_operation') }}
                        </span>
                    </div>
                </div>

                {{-- B. CONTENIDO REAL (Livewire) --}}
                {{-- Usamos x-show para no destruir los componentes Livewire hijos (mistake-selector) --}}
                <div x-show="!isLoading"
                     style="display: none;">

                    @if (!$this->trade)
                        {{-- Fallback de Livewire (por si hay un micro-lapso sin datos) --}}
                        <div class="w-full animate-pulse space-y-6">
                            <div class="flex justify-between">
                                <div class="h-8 w-1/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                                <div class="h-8 w-1/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-6">
                                <div class="col-span-1 space-y-4">
                                    <div class="h-40 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-24 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                                <div class="col-span-2 h-80 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                        </div>
                    @else
                        {{-- CONTENIDO DE LA OPERACIÓN --}}
                        <div wire:loading.class="opacity-50 pointer-events-none"
                             wire:target="goToPrev, goToNext"
                             wire:key="trade-content-{{ $this->trade->id }}">

                            {{-- 1. CABECERA TRADE --}}
                            <div class="mb-4 flex items-end justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('labels.ticket') }}{{ $this->trade->ticket ?? 'N/A' }}</span>
                                    <h2 class="mt-1 flex items-center gap-3 text-4xl font-black text-gray-900 dark:text-gray-100">
                                        {{ $this->trade->tradeAsset->name ?? 'N/A' }}
                                        <span class="{{ $this->trade->direction == 'long' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' }} rounded-full px-3 py-1 text-sm font-bold uppercase tracking-wide">
                                            {{ $this->trade->direction }}
                                        </span>
                                    </h2>
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-bold uppercase tracking-wide text-blue-700 dark:bg-blue-500/10 dark:text-blue-400"> {{ $this->trade->account->name }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('labels.net_result') }}</span>
                                    {{--  --}}
                                    <span class="{{ $this->trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-4xl font-black"
                                          x-text="$store.viewMode.format({{ $this->trade->pnl }}, {{ $this->trade->pnl_percentage ?? 0 }})">
                                        {{ $this->trade->pnl >= 0 ? '+' : '' }}{{ number_format($this->trade->pnl, 2) }} $
                                    </span>
                                </div>
                            </div>

                            {{-- 2. GRID PRINCIPAL --}}
                            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

                                {{-- MISTAKE SELECTOR --}}
                                {{-- Al estar bajo x-show, Livewire NO PIERDE la referencia a este componente --}}
                                <div class="col-span-3">
                                    <livewire:mistake-selector :trade="$this->trade"
                                                               :wire:key="'mistake-'.$this->trade->id" />
                                </div>

                                {{-- COLUMNA DATOS --}}
                                <div class="space-y-6 lg:col-span-1">
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900/40">
                                        <h4 class="mb-4 text-xs font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.execution_data') }}</h4>
                                        <dl class="space-y-4 text-sm">
                                            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('labels.entry_exit') }}</dt>
                                                <dd class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ $this->trade->entry_price }} <i class="fa-solid fa-arrow-right mx-1 text-xs text-gray-400 dark:text-gray-500"></i> {{ $this->trade->exit_price }}</dd>
                                            </div>
                                            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('labels.timetable') }}</dt>
                                                {{-- Después (CORRECTO) --}}
                                                <dd class="text-right font-mono font-bold text-gray-900 dark:text-gray-100">
                                                    {{ \Carbon\Carbon::parse($this->trade->entry_time)->format('H:i') }}
                                                    @if ($this->trade->exit_time)
                                                        - {{ \Carbon\Carbon::parse($this->trade->exit_time)->format('H:i') }}
                                                        <span class="block text-[10px] font-normal text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($this->trade->exit_time)->format('d M Y') }}</span>
                                                    @else
                                                        <span class="text-xs text-yellow-500">({{ __('labels.open') }})</span>
                                                    @endif
                                                </dd>
                                            </div>
                                            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('labels.volume') }}</dt>
                                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ $this->trade->size }} {{ __('labels.lots') }}</dd>
                                            </div>

                                            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('labels.pips_moved') }}</dt>
                                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ $this->trade->pips_traveled }} {{ __('labels.pips') }}</dd>
                                            </div>

                                            {{-- BARRA MAE/MFE --}}
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('labels.execution') }}</dt>
                                                <dd class="w-32 font-medium text-gray-900 dark:text-gray-100">
                                                    @if ($this->trade->mae_price && $this->trade->mfe_price)
                                                        @php
                                                            // 1. Distancias Absolutas
                                                            $distMae = abs($this->trade->entry_price - $this->trade->mae_price);
                                                            $distMfe = abs($this->trade->entry_price - $this->trade->mfe_price);
                                                            $distReal = abs($this->trade->entry_price - $this->trade->exit_price);

                                                            // 2. Rango Visual
                                                            $totalRange = $distMae + $distMfe;
                                                            $totalRange = $totalRange > 0 ? $totalRange : 0.00001;

                                                            $pctRed = ($distMae / $totalRange) * 100;
                                                            $pctGreen = ($distMfe / $totalRange) * 100;

                                                            // 3. Posición Marcador
                                                            $isBetterThanEntry = $this->trade->direction == 'long' ? $this->trade->exit_price >= $this->trade->entry_price : $this->trade->exit_price <= $this->trade->entry_price;

                                                            if ($isBetterThanEntry) {
                                                                $markerPos = $pctRed + ($distReal / $totalRange) * 100;
                                                            } else {
                                                                $markerPos = $pctRed - ($distReal / $totalRange) * 100;
                                                            }
                                                            $markerPos = max(0, min(100, $markerPos));

                                                            // 4. CÁLCULO MONETARIO INTELIGENTE
                                                            $maeMoney = 0;
                                                            $mfeMoney = 0;

                                                            // Umbral de fiabilidad: 2 pips (0.0002) aprox.
                                                            // Si el precio se movió MENOS que esto, el PnL es mayormente comisiones/swap
                                                            // y no sirve para calcular el valor del punto matemáticamente.
                                                            if ($distReal > 0.0002) {
                                                                // Cálculo exacto basado en lo que pasó
                                                                $valuePerPoint = abs($this->trade->pnl) / $distReal;
                                                            } else {
                                                                // FALLBACK: Estimación basada en Lotes (Size)
                                                                // Asumimos estándar Forex (100k unidades).
                                                                // Si operas Índices/Crypto esto será una aproximación, pero mucho mejor que 0 o Infinito.
                                                                $valuePerPoint = $this->trade->size * 100000;
                                                            }

                                                            // Aplicamos el valor del punto a las distancias MAE/MFE
                                                            $maeMoney = $distMae * $valuePerPoint;
                                                            $mfeMoney = $distMfe * $valuePerPoint;
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
                                                                        {{ __('labels.max_risk') }} {{ number_format($maeMoney, 0) }} $
                                                                    </div>
                                                                </div>
                                                                <div class="group/green relative h-4 w-full cursor-help"
                                                                     style="width: {{ $pctGreen }}%">
                                                                    <div
                                                                         class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                                        {{ __('labels.max_potencial') }} +{{ number_format($mfeMoney, 0) }} $
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="pointer-events-none absolute z-20 h-2.5 w-1 rounded-full bg-gray-900 shadow-sm ring-1 ring-white"
                                                                 style="left: {{ $markerPos }}%; transform: translateX(-50%);"></div>
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-300 dark:text-gray-600">-</span>
                                                    @endif
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm transition-all focus-within:ring-2 focus-within:ring-yellow-400 focus-within:ring-offset-2 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:focus-within:ring-offset-gray-800"
                                         x-data="{
                                             notes: '',
                                             saving: false,
                                             saved: false,
                                             async save() {
                                                 if (this.saving) return;
                                                 this.saving = true;
                                                 this.saved = false;
                                                 try {
                                                     await this.$wire.saveNotes(this.notes);
                                                     this.saved = true;
                                                     setTimeout(() => this.saved = false, 2500);
                                                 } finally {
                                                     this.saving = false;
                                                 }
                                             }
                                         }">
                                        <div class="mb-2 flex items-center justify-between">
                                            <h4 class="flex items-center gap-2 text-xs font-bold uppercase text-yellow-700 dark:text-yellow-400">
                                                <i class="fa-regular fa-note-sticky"></i> {{ __('labels.session_notes') }}
                                            </h4>

                                            {{-- Indicador 100% Alpine: sin wire:loading, sin isSavingNotes en PHP --}}
                                            <div class="flex items-center gap-1 text-xs font-medium text-yellow-600">
                                                <span x-show="saved"
                                                      x-transition
                                                      style="display:none">
                                                    <i class="fa-solid fa-check"></i> {{ __('labels.saved') }}
                                                </span>
                                                <span x-show="saving"
                                                      x-transition
                                                      style="display:none">
                                                    <i class="fa-solid fa-circle-notch fa-spin"></i> {{ __('labels.saving') }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- x-model en lugar de wire:model: Alpine gestiona el valor localmente --}}
                                        {{-- @blur dispara save(): en ese momento, .defer sube notes + llama saveNotes en 1 request --}}
                                        <textarea class="w-full resize-none border-0 bg-transparent p-0 text-sm leading-relaxed text-gray-800 placeholder-yellow-800/50 focus:ring-0 dark:text-gray-100 dark:placeholder-yellow-200/30"
                                                  x-model="notes"
                                                  @blur="save()"
                                                  rows="4"
                                                  placeholder="{{ __('labels.placeholder_notes') }}">
    </textarea>
                                    </div>
                                </div>

                                {{-- COLUMNA GRÁFICO + IA --}}
                                <div class="space-y-6 lg:col-span-2">

                                    {{-- El visor vive en <x-trade-chart>: lo comparten este modal y la
                                         pantalla de repaso. Aquí se le cuelga el panel de la captura,
                                         que es lo único propio del detalle de la operación. --}}
                                    <x-trade-chart :trade="$this->trade"
                                                   gate="!isLoading">
                                        {{-- 2. CONTENEDOR IMAGEN / UPLOAD --}}
                                        <div class="absolute inset-0 z-10 flex h-full w-full flex-col items-center justify-center bg-gray-900"
                                             x-show="activeTab === 'image'"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             style="display: none;">

                                            {{-- 
       IMPORTANTE: Esta wire:key cambia cuando se sube la foto.
       Esto obliga a Livewire a repintar todo el bloque sí o sí.
    --}}
                                            <div class="h-full w-full"
                                                 wire:key="media-box-{{ $this->trade->id }}-{{ $currentScreenshot ? 'img' : 'drop' }}">

                                                @if ($currentScreenshot)
                                                    {{-- CASO A: YA HAY IMAGEN --}}
                                                    <div class="group relative h-full w-full">
                                                        {{-- Añadimos un timestamp a la URL para evitar caché del navegador si cambias la imagen --}}
                                                        <img class="h-full w-full object-contain"
                                                             src="{{ $this->screenshotUrl }}"
                                                             alt="Trade Screenshot">

                                                        {{-- Overlay para cambiar imagen --}}
                                                        <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                                             x-show="!isFullscreen">
                                                            <label class="cursor-pointer rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur-md transition hover:bg-white/20">
                                                                <i class="fa-solid fa-cloud-arrow-up mr-2"></i> {{ __('labels.change_image') }}
                                                                {{-- IMPORTANTE: wire:model.live --}}
                                                                <input class="hidden"
                                                                       type="file"
                                                                       wire:model.live="uploadedScreenshot"
                                                                       accept="image/*">
                                                            </label>
                                                        </div>
                                                    </div>
                                                @else
                                                    {{-- CASO B: NO HAY IMAGEN (DROPZONE) --}}
                                                    <div class="flex h-full w-full flex-col items-center justify-center p-8 text-center"
                                                         x-data="{ isDropping: false }"
                                                         @dragover.prevent="isDropping = true"
                                                         @dragleave.prevent="isDropping = false"
                                                         {{-- Evento JS Manual --}}
                                                         @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))">

                                                        {{-- IMPORTANTE: wire:model.live --}}
                                                        <input class="hidden"
                                                               type="file"
                                                               x-ref="fileInput"
                                                               wire:model.live="uploadedScreenshot"
                                                               accept="image/*">

                                                        <label class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-10 transition-all"
                                                               :class="isDropping ? 'border-indigo-500 bg-indigo-500/10' : 'border-gray-700 hover:border-indigo-500 hover:bg-gray-800'"
                                                               @click="$refs.fileInput.click()">

                                                            <div wire:loading.remove
                                                                 wire:target="uploadedScreenshot">
                                                                <div class="mb-4 rounded-full bg-gray-800 p-4 text-indigo-500 shadow-lg transition-transform group-hover:scale-110">
                                                                    <i class="fa-solid fa-cloud-arrow-up text-3xl"></i>
                                                                </div>
                                                                <h3 class="mb-1 text-lg font-bold text-white">{{ __('labels.upload_screenshot') }}</h3>
                                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('labels.drag_drop_or_click') }}</p>
                                                            </div>

                                                            <div class="text-center"
                                                                 wire:loading
                                                                 wire:target="uploadedScreenshot">
                                                                <i class="fa-solid fa-circle-notch fa-spin mb-3 text-3xl text-indigo-500"></i>
                                                                <p class="text-sm font-bold text-white">{{ __('labels.uploading') }}...</p>
                                                            </div>
                                                        </label>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </x-trade-chart>
                                    {{-- </template> --}}
                                    {{-- IA --}}
                                    @if (Auth::user()->hasProAccess())
                                        <div class="relative overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10">
                                            <div class="relative z-10 mb-4 flex items-start justify-between">
                                                <div>
                                                    <h4 class="flex items-center gap-2 text-sm font-bold text-indigo-900 dark:text-indigo-300">
                                                        <i class="fa-solid fa-brain text-indigo-600 dark:text-indigo-400"></i> {{ __('labels.mentor_analyze') }}
                                                    </h4>
                                                    {{-- CONTADOR VISUAL --}}
                                                    <p class="mt-1 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                                        {{ __('labels.daily_uses') }}
                                                        <span class="{{ $this->aiCreditsLeft() > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                            {{ $this->aiCreditsLeft() }} / {{ $this->aiDailyLimit() }}
                                                        </span>
                                                    </p>
                                                    @if (!$this->trade->ai_analysis)
                                                        <p class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">
                                                            {{ __('labels.explain_analyze_mentor') }}
                                                        </p>
                                                    @endif
                                                </div>


                                                @if (!$this->trade->ai_analysis)
                                                    <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                            wire:click="analyzeIndividualTrade"
                                                            wire:loading.attr="disabled">
                                                        <span wire:loading.remove
                                                              wire:target="analyzeIndividualTrade">
                                                            {{ $this->aiCreditsLeft() > 0 ? __('labels.analyze_trade') : 'Límite alcanzado' }}
                                                        </span>
                                                        <span class="flex items-center gap-2"
                                                              wire:loading
                                                              wire:target="analyzeIndividualTrade">
                                                            <svg class="h-3 w-3 animate-spin text-white"
                                                                 viewBox="0 0 24 24">
                                                                <circle class="opacity-25"
                                                                        cx="12"
                                                                        cy="12"
                                                                        r="10"
                                                                        stroke="currentColor"
                                                                        stroke-width="4"></circle>
                                                                <path class="opacity-75"
                                                                      fill="currentColor"
                                                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                            {{ __('labels.thinking') }}
                                                        </span>
                                                    </button>
                                                @endif



                                            </div>

                                            <div class="w-full animate-pulse space-y-2"
                                                 wire:loading
                                                 wire:target="analyzeIndividualTrade">
                                                <div class="h-3 w-full rounded bg-indigo-200 dark:bg-indigo-500/20"></div>
                                                <div class="h-3 w-5/6 rounded bg-indigo-200 dark:bg-indigo-500/20"></div>
                                                <div class="h-3 w-4/6 rounded bg-indigo-200 dark:bg-indigo-500/20"></div>
                                            </div>


                                            @if ($this->trade->ai_analysis)
                                                <div class="prose prose-sm rounded-lg border border-indigo-50/50 bg-white/50 p-3 text-sm text-gray-800 dark:prose-invert dark:border-indigo-500/20 dark:bg-gray-900/40 dark:text-gray-200">
                                                    {!! Str::markdown($this->trade->ai_analysis) !!}
                                                </div>
                                                <div class="mt-2 text-right">
                                                    <button class="text-[10px] text-indigo-400 underline hover:text-indigo-600"
                                                            wire:click="analyzeIndividualTrade">
                                                        {{ __('labels.regenerate_analyze') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                    @endif

                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
