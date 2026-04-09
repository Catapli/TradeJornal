@if ($selectedStrategy)
    @php
        $rules = $selectedStrategy->rules ?? [];
    @endphp

    <div class="max-w-2xl space-y-4">

        {{-- HEADER INFO --}}
        <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-500"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="text-sm text-blue-700">
                Estas reglas definen un setup válido para <strong>{{ $selectedStrategy->name }}</strong>.
                Cuando registres un trade, el campo <em>"¿Siguió las reglas?"</em> hace referencia a este checklist.
                El Analytics te mostrará el impacto real de seguirlas.
            </p>
        </div>

        {{-- LISTA DE REGLAS --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">

            @if ($rules)
                <ul class="divide-y divide-gray-50">
                    @foreach ($rules as $i => $rule)
                        <li class="group flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50"
                            x-data="{ editing: false, value: @js($rule) }">
                            {{-- Número --}}
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500">
                                {{ $i + 1 }}
                            </span>

                            {{-- Texto o input de edición --}}
                            <div class="min-w-0 flex-1">
                                <span class="text-sm text-gray-700"
                                      x-show="!editing">{{ $rule }}</span>
                                <input class="w-full rounded-lg border border-blue-400 bg-white px-2 py-1 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       x-show="editing"
                                       x-model="value"
                                       @keydown.enter="editing = false; $wire.updateRule({{ $i }}, value)"
                                       @keydown.escape="editing = false; value = @js($rule)"
                                       type="text"
                                       x-effect="if(editing) $nextTick(() => $el.focus())" />
                            </div>

                            {{-- Acciones --}}
                            <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">

                                {{-- Editar / Guardar --}}
                                <button class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                        type="button"
                                        @click="
                                    if (editing) {
                                        editing = false;
                                        $wire.updateRule({{ $i }}, value)
                                    } else {
                                        editing = true
                                    }
                                "
                                        :title="editing ? 'Guardar' : 'Editar'">
                                    <svg class="h-3.5 w-3.5"
                                         x-show="!editing"
                                         xmlns="http://www.w3.org/2000/svg"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="2">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                    <svg class="h-3.5 w-3.5 text-blue-600"
                                         x-show="editing"
                                         xmlns="http://www.w3.org/2000/svg"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="2">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </button>

                                {{-- Subir --}}
                                @if ($i > 0)
                                    <button class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            type="button"
                                            wire:click="moveRule({{ $i }}, 'up')"
                                            title="Subir">
                                        <svg class="h-3.5 w-3.5"
                                             xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                        </svg>
                                    </button>
                                @endif

                                {{-- Bajar --}}
                                @if ($i < count($rules) - 1)
                                    <button class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            type="button"
                                            wire:click="moveRule({{ $i }}, 'down')"
                                            title="Bajar">
                                        <svg class="h-3.5 w-3.5"
                                             xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                @endif

                                {{-- Eliminar --}}
                                <button class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                                        type="button"
                                        wire:click="removeRuleFromStrategy({{ $i }})"
                                        wire:confirm="¿Eliminar esta regla?"
                                        title="Eliminar">
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

                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- EMPTY STATE --}}
            @if (empty($rules))
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg class="mb-3 h-8 w-8 text-gray-300"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="1.5">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-gray-500">Sin reglas definidas todavía</p>
                    <p class="mt-1 text-xs text-gray-400">Añade las condiciones que debe cumplir un setup válido</p>
                </div>
            @endif

            {{-- AÑADIR REGLA --}}
            <div class="border-t border-gray-100 bg-gray-50 px-5 py-4">
                <div class="flex gap-2">
                    <input class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           wire:model="newRule"
                           wire:keydown.enter.prevent="addRuleToStrategy"
                           type="text"
                           placeholder="Ej: El precio debe llegar a un POI en H1..." />
                    <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-500"
                            type="button"
                            wire:click="addRuleToStrategy">
                        Añadir
                    </button>
                </div>
            </div>

        </div>

    </div>
@endif
