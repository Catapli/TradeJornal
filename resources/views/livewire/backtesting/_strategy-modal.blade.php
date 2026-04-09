<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     x-show="showModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="closeModal()"
     style="display:none">

    <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-gray-900">
                    <span x-show="!isEditing">Nueva estrategia</span>
                    <span x-show="isEditing">Editar estrategia</span>
                </h2>
                <p class="mt-0.5 text-xs text-gray-400">Define los parámetros del sistema</p>
            </div>
            <button class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    type="button"
                    @click="closeModal()">
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

        {{-- Form --}}
        <form class="max-h-[78vh] overflow-y-auto"
              wire:submit="save">
            <div class="space-y-4 px-6 py-5">

                {{-- ── Nombre ────────────────────────────── --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre</label>
                    <input class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                           wire:model="name"
                           type="text"
                           placeholder="Ej: Ruptura London Open" />
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ── Símbolo + Timeframe ───────────────── --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Símbolo</label>
                        <input class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm uppercase text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                               wire:model="symbol"
                               type="text"
                               placeholder="XAUUSD" />
                        @error('symbol')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Timeframe</label>
                        <select class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                wire:model="timeframe">
                            @foreach (['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1'] as $tf)
                                <option value="{{ $tf }}">{{ $tf }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ── Dirección ─────────────────────────── --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Dirección</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['both' => 'Long & Short', 'long' => 'Solo Long', 'short' => 'Solo Short'] as $val => $label)
                            <button class="{{ $direction === $val ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }} h-9 rounded-lg border text-xs font-semibold transition-colors"
                                    type="button"
                                    wire:click="$set('direction', '{{ $val }}')">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- ── Separador Setup ───────────────────── --}}
                <div class="flex items-center gap-3 pt-1">
                    <div class="h-px flex-1 bg-gray-100"></div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Setup</span>
                    <div class="h-px flex-1 bg-gray-100"></div>
                </div>

                {{-- ── Descripción ───────────────────────── --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Descripción <span class="font-normal normal-case text-gray-400">(opcional)</span>
                    </label>
                    <textarea class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                              wire:model="description"
                              rows="2"
                              placeholder="Lógica de la estrategia..."></textarea>
                </div>

                {{-- ── Reglas ────────────────────────────── --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Reglas del setup</label>
                    @if (count($rules))
                        <ul class="mb-2 space-y-1">
                            @foreach ($rules as $i => $rule)
                                <li class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-1.5">
                                    <span class="text-xs font-medium text-gray-400">{{ $i + 1 }}.</span>
                                    <span class="flex-1 text-xs text-gray-700">{{ $rule }}</span>
                                    <button class="text-gray-300 transition-colors hover:text-red-400"
                                            type="button"
                                            wire:click="removeRule({{ $i }})">
                                        <svg class="h-3.5 w-3.5"
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
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="flex gap-2">
                        <input class="h-9 flex-1 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                               wire:model="newRule"
                               wire:keydown.enter.prevent="addRule"
                               type="text"
                               placeholder="Ej: El precio llega a un POI en H1" />
                        <button class="h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-100"
                                type="button"
                                wire:click="addRule">+</button>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between rounded-b-2xl border-t border-gray-100 bg-gray-50/60 px-6 py-4">
                <button class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 transition-colors hover:text-gray-700"
                        type="button"
                        @click="closeModal()">
                    Cancelar
                </button>
                <button class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-500"
                        type="submit">
                    <span x-show="!isEditing">Crear estrategia</span>
                    <span x-show="isEditing">Guardar cambios</span>
                </button>
            </div>

        </form>
    </div>
</div>
