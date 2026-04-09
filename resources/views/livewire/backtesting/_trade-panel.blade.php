<div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 pt-12 backdrop-blur-sm"
     x-show="showTradePanel"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="closeTradePanel()"
     style="display:none">

    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl"
         x-show="showTradePanel"
         x-data="{ showOptional: false }"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-gray-900">
                    <span x-show="!isEditingTrade">Nuevo Trade</span>
                    <span x-show="isEditingTrade">Editar Trade</span>
                </h2>
                <p class="mt-0.5 text-xs text-gray-400">Completa los datos del setup</p>
            </div>
            <button class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    type="button"
                    @click="closeTradePanel()">
                <svg class="h-4 w-4"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form>
            <div class="px-6 py-5">

                {{-- ══ CAMPOS ESENCIALES ═══════════════════════════════ --}}
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                    {{-- Fecha --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha</label>
                        <input class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                               wire:model="trade_date"
                               type="date" />
                        @error('trade_date')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Dirección --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Dirección</label>
                        <div class="grid h-10 grid-cols-2 gap-2">
                            <button class="rounded-lg border text-sm font-semibold transition-all duration-150"
                                    type="button"
                                    @click="setDirection('long')"
                                    :class="tradeDirection === 'long'
                                        ?
                                        'bg-emerald-500 border-emerald-500 text-white shadow-sm' :
                                        'bg-white border-gray-200 text-gray-500 hover:border-emerald-400 hover:text-emerald-600'">
                                Long
                            </button>
                            <button class="rounded-lg border text-sm font-semibold transition-all duration-150"
                                    type="button"
                                    @click="setDirection('short')"
                                    :class="tradeDirection === 'short'
                                        ?
                                        'bg-red-500 border-red-500 text-white shadow-sm' :
                                        'bg-white border-gray-200 text-gray-500 hover:border-red-400 hover:text-red-500'">
                                Short
                            </button>
                        </div>
                    </div>

                    {{-- Entrada --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Entrada</label>
                        <input class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                               wire:model="entry_price"
                               type="number"
                               step="any"
                               placeholder="0.00000" />
                        @error('entry_price')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Salida --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Salida</label>
                        <input class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                               wire:model="exit_price"
                               type="number"
                               step="any"
                               placeholder="0.00000" />
                        @error('exit_price')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Stop Loss --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Stop Loss</label>
                        <input class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                               wire:model="stop_loss"
                               type="number"
                               step="any"
                               placeholder="0.00000" />
                    </div>

                    {{-- Resultado — solo R ──────────────────────────── --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Resultado</label>
                        <div class="flex h-10 items-center justify-center rounded-lg border px-3 transition-all duration-200"
                             :class="{
                                 'border-emerald-200 bg-emerald-50': computedR > 0,
                                 'border-red-200 bg-red-50': computedR < 0,
                                 'border-gray-200 bg-gray-50': computedR === null || computedR === 0
                             }">
                            <span class="text-base font-bold tabular-nums transition-colors duration-200"
                                  :class="rColor"
                                  x-text="computedR !== null ? (computedR > 0 ? '+' : '') + computedR + 'R' : '—'">
                            </span>
                        </div>
                    </div>

                </div>

                {{-- ══ TOGGLE OPCIONAL ════════════════════════════════ --}}
                <button class="mt-5 flex w-full items-center justify-between border-t border-gray-100 pt-4 text-xs font-medium text-gray-400 transition-colors hover:text-gray-600"
                        type="button"
                        @click="showOptional = !showOptional">
                    <span x-text="showOptional ? 'Ocultar detalles' : 'Añadir detalles opcionales'"></span>
                    <svg class="h-4 w-4 transition-transform duration-200"
                         xmlns="http://www.w3.org/2000/svg"
                         :class="showOptional ? 'rotate-180' : ''"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- ══ OPCIONALES ════════════════════════════════════ --}}
                <div class="mt-4 space-y-5"
                     x-show="showOptional"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                        {{-- Sesión --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Sesión</label>
                            <div class="grid grid-cols-4 gap-1.5">
                                @foreach (['london' => 'LON', 'new_york' => 'NY', 'asia' => 'ASIA', 'other' => 'Otra'] as $val => $label)
                                    <button class="rounded-lg border py-2 text-xs font-semibold transition-all duration-150"
                                            type="button"
                                            @click="setSession('{{ $val }}')"
                                            :class="tradeSession === '{{ $val }}'
                                                ?
                                                'bg-blue-600 border-blue-600 text-white' :
                                                'bg-white border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-500'">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Rating --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Calidad del setup</label>
                            <div class="flex h-9 items-center gap-1.5">
                                @foreach ([1, 2, 3, 4, 5] as $star)
                                    <button class="flex-1 rounded-lg border py-1.5 text-base transition-all duration-150"
                                            type="button"
                                            @click="setRating({{ $star }})"
                                            :class="tradeRating >= {{ $star }} ?
                                                'bg-amber-400 border-amber-400 text-white' :
                                                'bg-white border-gray-200 text-gray-300 hover:border-amber-300'">★</button>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    {{-- ¿Siguió reglas? --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">¿Siguió las reglas?</p>
                            <p class="text-xs text-gray-400">El setup cumplía todos los criterios</p>
                        </div>
                        <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                type="button"
                                @click="toggleFollowedRules()"
                                :class="tradeFollowedRules ? 'bg-emerald-500' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                  :class="tradeFollowedRules ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    {{-- Confluencias --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Confluencias</label>
                        <div class="mb-2 flex flex-wrap gap-1.5">
                            @foreach ($confluences as $i => $tag)
                                <span class="flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs text-blue-700">
                                    {{ $tag }}
                                    <button class="text-blue-400 hover:text-blue-700"
                                            type="button"
                                            wire:click="removeConfluence({{ $i }})">
                                        <svg class="h-3 w-3"
                                             xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <input class="h-10 flex-1 rounded-lg border border-gray-200 bg-white px-3 text-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   wire:model="newConfluence"
                                   wire:keydown.enter.prevent="addConfluence"
                                   type="text"
                                   placeholder="EMA200, FVG, POI..." />
                            <button class="h-10 rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-100"
                                    type="button"
                                    wire:click="addConfluence">+</button>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Notas</label>
                        <textarea class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  wire:model="notes"
                                  rows="2"
                                  placeholder="Observaciones del trade..."></textarea>
                    </div>

                    {{-- Screenshot --}}
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Screenshot del chart</label>
                        <div class="flex w-full items-center justify-center">
                            <label class="relative flex h-48 w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-gray-50 hover:bg-gray-100"
                                   :class="isDragging ? 'border-blue-400 bg-blue-50' : 'border-gray-300'"
                                   @dragover.prevent="isDragging = true"
                                   @dragleave.prevent="isDragging = false"
                                   @drop.prevent="handleDrop($event)">

                                <template x-if="photoPreview">
                                    <img class="absolute inset-0 h-full w-full object-contain"
                                         :src="photoPreview"
                                         alt="Preview">
                                </template>

                                <template x-if="!photoPreview && existingPhotoUrl">
                                    <img class="absolute inset-0 h-full w-full object-contain"
                                         :src="existingPhotoUrl"
                                         alt="Screenshot existente">
                                </template>

                                <div class="flex flex-col items-center justify-center pb-6 pt-5"
                                     x-show="!photoPreview && !existingPhotoUrl">
                                    <svg class="mb-3 h-8 w-8 text-gray-400"
                                         xmlns="http://www.w3.org/2000/svg"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="1.5">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                    <p class="text-sm text-gray-500"><span class="font-semibold">Haz clic</span> o arrastra una imagen</p>
                                    <p class="text-xs text-gray-400">PNG, JPG, WebP — máx. 10MB</p>
                                </div>

                                {{-- Overlay carga --}}
                                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/80"
                                     wire:loading
                                     wire:target="screenshot">
                                    <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-2 shadow-lg">
                                        <svg class="h-5 w-5 animate-spin text-blue-600"
                                             xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
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
                                        <span class="text-sm font-semibold text-gray-600">Subiendo imagen...</span>
                                    </div>
                                </div>

                                <input class="hidden"
                                       x-ref="photoInput"
                                       type="file"
                                       accept="image/*"
                                       wire:model="screenshot"
                                       @change="onPhotoSelected($event)">
                            </label>
                        </div>

                        {{-- Botón limpiar foto --}}
                        <button class="mt-2 text-xs text-gray-400 underline transition-colors hover:text-red-400"
                                type="button"
                                x-show="photoPreview || existingPhotoUrl"
                                @click="clearPhoto()">
                            Eliminar imagen
                        </button>

                        @error('screenshot')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between rounded-b-2xl border-t border-gray-100 bg-gray-50/60 px-6 py-4">
                <button class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 transition-all hover:bg-gray-100 hover:text-gray-700"
                        type="button"
                        @click="closeTradePanel()">
                    Cancelar
                </button>
                <button class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-blue-700 active:bg-blue-800 disabled:opacity-60"
                        type="button"
                        wire:click="saveTrade"
                        wire:loading.attr="disabled"
                        wire:target="saveTrade">
                    <span wire:loading.remove
                          wire:target="saveTrade">
                        <span x-show="!isEditingTrade">Guardar Trade</span>
                        <span x-show="isEditingTrade">Actualizar Trade</span>
                    </span>
                    <span class="flex items-center gap-2"
                          wire:loading
                          wire:target="saveTrade">
                        <svg class="h-4 w-4 animate-spin"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4" />
                            <path class="opacity-75"
                                  fill="currentColor"
                                  d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        Guardando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
