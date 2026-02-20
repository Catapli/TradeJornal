<div class="min-h-screen bg-gray-50 p-6"
     x-data="reports">

    {{-- CONTENEDOR PRINCIPAL CON ESTADO ALPINE --}}
    <div x-data="{
        initialLoad: true,
        init() {
            document.addEventListener('livewire:initialized', () => {
                this.initialLoad = false;
            });
            setTimeout(() => { this.initialLoad = false }, 200);
        }
    }">

        {{-- 1. LOADER DE CARGA INICIAL --}}
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex flex-col items-center">
                <x-loader />
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400">Cargando Dashboard...</span>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- SISTEMA DE ALERTAS (Alpine-Driven) --}}
    {{-- ============================================ --}}

    <div class="fixed right-4 top-4 z-50 max-w-md"
         x-show="showAlert"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="rounded-r-lg border-l-4 p-4 shadow-lg"
             :class="{
                 'bg-red-50 border-red-500 text-red-800': typeAlert === 'error',
                 'bg-yellow-50 border-yellow-500 text-yellow-800': typeAlert === 'warning',
                 'bg-blue-50 border-blue-500 text-blue-800': typeAlert === 'info',
                 'bg-green-50 border-green-500 text-green-800': typeAlert === 'success'
             }">

            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500"
                         x-show="typeAlert === 'error'"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                              clip-rule="evenodd" />
                    </svg>
                    <svg class="h-5 w-5 text-yellow-500"
                         x-show="typeAlert === 'warning'"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                              clip-rule="evenodd" />
                    </svg>
                    <svg class="h-5 w-5 text-blue-500"
                         x-show="typeAlert === 'info'"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                              clip-rule="evenodd" />
                    </svg>
                    <svg class="h-5 w-5 text-green-500"
                         x-show="typeAlert === 'success'"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd" />
                    </svg>
                </div>

                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium"
                       x-text="bodyAlert"></p>
                </div>

                <button class="ml-4 inline-flex flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none"
                        @click="closeAlert()">
                    <svg class="h-4 w-4"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                              clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- HEADER CON SELECTOR Y BADGES --}}
    {{-- ============================================ --}}

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-flask-vial text-2xl text-indigo-600"></i>
                <h1 class="text-3xl font-black text-gray-900">Laboratorio</h1>
            </div>
            <p class="text-sm text-gray-500">Analiza el impacto de tu disciplina en el resultado final.</p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Selector de cuenta (Livewire) --}}
            <select class="rounded-lg border-gray-300 text-sm font-bold shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    wire:model.live="accountId">
                <option value="all">Todas las Cuentas</option>
                @foreach ($this->accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>

            {{-- Badge de escenarios activos --}}
            <div class="flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-700"
                 x-show="hasActiveScenarios()"
                 x-transition>
                <svg class="h-4 w-4"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                          clip-rule="evenodd" />
                </svg>
                <span x-text="countActiveScenarios() + ' escenario' + (countActiveScenarios() > 1 ? 's' : '') + ' activo' + (countActiveScenarios() > 1 ? 's' : '')"></span>
            </div>

            {{-- Badge de cambios pendientes --}}
            <div class="flex animate-pulse items-center gap-2 rounded-full bg-yellow-100 px-3 py-1.5 text-xs font-bold text-yellow-700"
                 x-show="hasUnsavedChanges"
                 x-transition>
                <svg class="h-4 w-4"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                          clip-rule="evenodd" />
                </svg>
                Cambios pendientes
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- ============================================ --}}
        {{-- COLUMNA IZQUIERDA: CONTROLES (ALPINE-FIRST) --}}
        {{-- ============================================ --}}

        <div class="col-span-12 space-y-6 lg:col-span-3">

            {{-- TARJETA 1: LABORATORIO DE ESTRATEGIA (VERSIÓN FINAL) --}}
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                        <i class="fa-solid fa-flask text-indigo-600"></i> Laboratorio
                    </h3>

                    <button class="flex items-center gap-1 text-xs text-gray-500 transition hover:text-red-600"
                            @click="resetAllScenarios()"
                            x-show="hasActiveScenarios()"
                            x-transition
                            type="button">
                        <svg class="h-3.5 w-3.5"
                             fill="currentColor"
                             viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                  clip-rule="evenodd" />
                        </svg>
                        Reset
                    </button>
                </div>

                {{-- Tabs Navigation (Solo 2) --}}
                <div class="flex border-b border-gray-200">
                    <button class="flex-1 border-b-2 px-4 py-3 text-xs font-bold uppercase transition"
                            @click="activeTab = 'mechanical'"
                            :class="activeTab === 'mechanical' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                        <i class="fa-solid fa-gears mr-1"></i> Mecánico
                    </button>
                    <button class="flex-1 border-b-2 px-4 py-3 text-xs font-bold uppercase transition"
                            @click="activeTab = 'discipline'"
                            :class="activeTab === 'discipline' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                        <i class="fa-solid fa-brain mr-1"></i> Disciplina
                    </button>
                </div>

                {{-- Tab Content --}}
                <div class="p-6">
                    {{-- TAB 1: SIMULADOR MECÁNICO --}}
                    <div class="space-y-4"
                         x-show="activeTab === 'mechanical'"
                         x-transition>
                        <p class="text-[10px] leading-tight text-gray-400">
                            Recalcula resultados usando SL/TP fijos en puntos. Basado en tus datos MAE/MFE reales.
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            {{-- Fixed SL --}}
                            <div>
                                <label class="mb-1 block text-[10px] font-bold text-gray-500">Fixed SL</label>
                                <div class="relative">
                                    <input class="w-full rounded-lg border-gray-200 bg-white py-2 pl-2 pr-12 text-xs font-bold text-rose-600 placeholder-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                           type="number"
                                           step="0.1"
                                           x-model="scenarios.fixed_sl"
                                           @input="onScenarioChange()"
                                           placeholder="Ej: 15">
                                    <span class="absolute right-2 top-2 text-[10px] font-bold text-gray-400">pts</span>
                                </div>
                            </div>

                            {{-- Fixed TP --}}
                            <div>
                                <label class="mb-1 block text-[10px] font-bold text-gray-500">Fixed TP</label>
                                <div class="relative">
                                    <input class="w-full rounded-lg border-gray-200 bg-white py-2 pl-2 pr-12 text-xs font-bold text-emerald-600 placeholder-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                           type="number"
                                           step="0.1"
                                           x-model="scenarios.fixed_tp"
                                           @input="onScenarioChange()"
                                           placeholder="Ej: 30">
                                    <span class="absolute right-2 top-2 text-[10px] font-bold text-gray-400">pts</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-blue-50 p-3 text-[10px] leading-relaxed text-blue-800">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            El simulador usa <strong>MAE (peor precio)</strong> y <strong>MFE (mejor precio)</strong> para determinar si habrías tocado el SL o TP fijo.
                        </div>
                    </div>

                    {{-- TAB 2: FILTROS DE DISCIPLINA --}}
                    <div class="space-y-4"
                         x-show="activeTab === 'discipline'"
                         x-transition>
                        {{-- Fatiga --}}
                        <div>
                            <label class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                Control de Fatiga
                            </label>
                            <select class="w-full rounded-lg border-gray-200 text-xs font-bold text-gray-600 focus:border-indigo-500 focus:ring-indigo-500"
                                    x-model="scenarios.max_daily_trades"
                                    @change="onScenarioChange()">
                                <option value="">Todas las operaciones</option>
                                <option value="1">Solo la 1ª del día (Sniper)</option>
                                <option value="2">Max 2 trades/día</option>
                                <option value="3">Max 3 trades/día</option>
                                <option value="4">Max 4 trades/día</option>
                            </select>
                        </div>

                        {{-- Días de la semana --}}
                        <div>
                            <label class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                Días Excluidos
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50"
                                       :class="isDayExcluded(1) ? 'border-indigo-500 bg-indigo-50' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(1)"
                                           @change="toggleDay(1)">
                                    <span class="text-xs font-medium text-gray-700">Lunes</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50"
                                       :class="isDayExcluded(2) ? 'border-indigo-500 bg-indigo-50' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(2)"
                                           @change="toggleDay(2)">
                                    <span class="text-xs font-medium text-gray-700">Martes</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50"
                                       :class="isDayExcluded(3) ? 'border-indigo-500 bg-indigo-50' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(3)"
                                           @change="toggleDay(3)">
                                    <span class="text-xs font-medium text-gray-700">Miércoles</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50"
                                       :class="isDayExcluded(4) ? 'border-indigo-500 bg-indigo-50' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(4)"
                                           @change="toggleDay(4)">
                                    <span class="text-xs font-medium text-gray-700">Jueves</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50"
                                       :class="isDayExcluded(5) ? 'border-indigo-500 bg-indigo-50' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(5)"
                                           @change="toggleDay(5)">
                                    <span class="text-xs font-medium text-gray-700">Viernes</span>
                                </label>
                            </div>
                        </div>

                        {{-- Toggles compactos --}}
                        <div class="space-y-2 border-t border-gray-100 pt-4">
                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50">
                                <span class="text-xs font-medium text-gray-700">Solo operaciones Long</span>
                                <div class="relative inline-block h-5 w-9 select-none align-middle">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="scenarios.only_longs"
                                           @change="onScenarioChange()" />
                                    <div
                                         class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-4">
                                    </div>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50">
                                <span class="text-xs font-medium text-gray-700">Solo operaciones Short</span>
                                <div class="relative inline-block h-5 w-9 select-none align-middle">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="scenarios.only_shorts"
                                           @change="onScenarioChange()" />
                                    <div
                                         class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-rose-500 peer-checked:after:translate-x-4">
                                    </div>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50">
                                <span class="text-xs font-medium text-gray-700">Eliminar 5 peores trades</span>
                                <div class="relative inline-block h-5 w-9 select-none align-middle">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="scenarios.remove_worst"
                                           @change="onScenarioChange()" />
                                    <div
                                         class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-4">
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Footer: Botón Aplicar --}}
                <div class="border-t border-gray-100 px-6 py-4">
                    <button class="flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-bold transition"
                            @click="applyScenarios()"
                            :disabled="!hasUnsavedChanges || isApplying"
                            :class="{
                                'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md': hasUnsavedChanges && !isApplying,
                                'bg-gray-200 text-gray-400 cursor-not-allowed': !hasUnsavedChanges || isApplying
                            }">

                        {{-- Loading Spinner --}}
                        <svg class="h-4 w-4 animate-spin"
                             x-show="isApplying"
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

                        {{-- Icono normal --}}
                        <svg class="h-4 w-4"
                             x-show="!isApplying"
                             fill="currentColor"
                             viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                  clip-rule="evenodd" />
                        </svg>

                        <span x-text="isApplying ? 'Aplicando...' : 'Aplicar Simulación'"></span>
                    </button>
                </div>
            </div>



            {{-- TARJETA 2: CALIDAD DEL SISTEMA (SQN) --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-1 font-bold text-gray-900">System Quality (SQN)</h3>

                <div class="mb-2 mt-4 flex items-end gap-3">
                    {{-- Valor Real --}}
                    <div>
                        <span class="text-4xl font-black text-gray-900">{{ $this->realStats['sqn'] ?? '0.0' }}</span>
                        <span class="block text-[10px] font-bold uppercase text-gray-400">Actual</span>
                    </div>

                    {{-- Valor Simulado (si existe) --}}
                    @if ($this->simulatedData['stats'])
                        <div class="mb-1 text-gray-300"><i class="fa-solid fa-arrow-right"></i></div>
                        <div>
                            <span class="text-2xl font-black text-indigo-500">{{ $this->simulatedData['stats']['sqn'] }}</span>
                            <span class="block text-[10px] font-bold uppercase text-indigo-300">Simulado</span>
                        </div>
                    @endif
                </div>

                {{-- Barra de Progreso --}}
                <div class="mb-2 mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-gray-800 transition-all duration-500"
                         style="width: {{ min(100, (($this->realStats['sqn'] ?? 0) / 5) * 100) }}%"></div>
                </div>

                <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                    <span>Pobre (< 1.6)</span>
                            <span>Santo Grial (> 5.0)</span>
                </div>

                {{-- Diagnóstico --}}
                <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-xs leading-relaxed text-indigo-800">
                    @if (($this->realStats['sqn'] ?? 0) < 1.6)
                        <div class="flex gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                            <span>Difícil de operar. Mucha volatilidad para poco beneficio.</span>
                        </div>
                    @elseif(($this->realStats['sqn'] ?? 0) < 3.0)
                        <div class="flex gap-2">
                            <i class="fa-solid fa-check mt-0.5"></i>
                            <span>Buen sistema. Tienes ventaja estadística clara.</span>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <i class="fa-solid fa-trophy mt-0.5"></i>
                            <span>Sistema excelente. Considera aumentar el tamaño de posición.</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ============================================ --}}
        {{-- COLUMNA DERECHA: DASHBOARD COMPLETO --}}
        {{-- ============================================ --}}

        <div class="col-span-12 space-y-6 lg:col-span-9">

            {{-- 1. GRÁFICO EQUITY CURVE --}}
            <div class="relative flex flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                 x-data="equityChart(@js($this->realCurve), @js($this->simulatedData['curve']))"
                 x-effect="updateData(@js($this->realCurve), @js($this->simulatedData['curve']))">

                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Curva de Crecimiento (Equity Curve)</h3>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                            <span class="h-3 w-3 rounded-full bg-slate-300"></span> Realidad
                        </div>
                        @if (!empty($this->simulatedData['curve']))
                            <div class="flex items-center gap-2 text-xs font-bold text-indigo-600">
                                <span class="h-3 w-3 rounded-full bg-indigo-500"></span> Simulación
                            </div>
                        @endif
                    </div>
                </div>

                <div id="equityChart"
                     class="h-[400px] w-full"></div>

                {{-- Loading Overlay --}}
                <div class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/50 backdrop-blur-[1px]"
                     x-show="isApplying"
                     x-transition>
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                </div>
            </div>

            {{-- 2. REPORTES TEMPORALES --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="barChart(@js($this->hourlyReportData), 'hour', 'P&L por Hora')">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                        <i class="fa-regular fa-clock text-blue-600"></i> Rendimiento por Hora
                    </h3>
                    <div id="hourlyChart"
                         class="h-[250px] w-full"></div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="barChart(@js($this->sessionReportData), 'session', 'P&L por Sesión')">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                        <i class="fa-solid fa-globe-americas text-green-600"></i> Rendimiento por Sesión
                    </h3>
                    <div id="sessionChart"
                         class="h-[250px] w-full"></div>
                </div>
            </div>

            {{-- 3. PSICOLOGÍA Y GESTIÓN --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- Efficiency Chart --}}
                <div class="col-span-1 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm md:col-span-2"
                     x-data="efficiencyChart(@js($this->efficiencyData))">

                    <div class="mb-6 flex flex-col justify-between sm:flex-row sm:items-end">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-crosshairs text-indigo-600"></i> Eficiencia del Trader
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Comparativa de los últimos 15 trades:
                                <span class="font-bold text-rose-500">Riesgo sufrido (MAE)</span> vs
                                <span class="font-bold text-gray-900">Resultado (PnL)</span> vs
                                <span class="font-bold text-emerald-500">Potencial máximo (MFE)</span>.
                            </p>
                        </div>
                    </div>

                    <div id="efficiencyChart"
                         class="h-[350px] w-full"></div>

                    <div class="mt-4 flex flex-wrap justify-center gap-4 text-[10px] text-gray-400">
                        <div class="flex items-center gap-1">
                            <span class="block h-2 w-2 rounded-full bg-rose-400"></span> MAE: Cuanto se fue en contra antes de cerrar.
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="block h-2 w-2 rounded-full bg-emerald-400"></span> MFE: Cuanto dinero llegó a marcar flotante.
                        </div>
                    </div>
                </div>

                {{-- Distribution Chart --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="distributionChart(@js($this->distributionData))">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-chart-column text-purple-500"></i> Distribución de PnL
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">Frecuencia de resultados. ¿Hacia dónde se inclina?</p>
                        </div>
                    </div>
                    <div id="distChart"
                         class="h-[250px] w-full"></div>
                </div>

                {{-- Radar Chart --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="radarChart(@js($this->radarData))">

                    <div class="mb-2 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-fingerprint text-purple-600"></i> Perfil del Trader
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">Análisis 360º de tu estilo actual.</p>
                        </div>
                    </div>

                    <div id="radarChart"
                         class="flex h-[250px] w-full items-center justify-center"></div>
                </div>

                {{-- Risk Analysis --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data>
                    <div class="mb-4">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                            <i class="fa-solid fa-skull-crossbones text-gray-800"></i> Análisis de Riesgo & Ruina
                        </h3>
                        <p class="mt-1 text-xs text-gray-400">Probabilidades matemáticas basadas en tu Winrate ({{ $this->riskData['win_rate'] ?? 0 }}%) y Ratio (1:{{ $this->riskData['payoff'] ?? 0 }}).</p>
                    </div>

                    @if (empty($this->riskData))
                        <div class="flex h-32 items-center justify-center text-xs text-gray-400">
                            Necesitas al menos 10 trades para calcular el riesgo.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            {{-- Gauge de Ruina --}}
                            <div class="flex flex-col items-center justify-center border-r border-gray-100 pr-0 md:pr-6">

                                <div class="relative flex h-32 w-48 items-end justify-center overflow-hidden">
                                    <div class="absolute top-0 h-full w-full rounded-t-full bg-gray-100"></div>

                                    @php
                                        $deg = ($this->riskData['risk_of_ruin'] / 100) * 180;
                                        $colorClass = $this->riskData['risk_of_ruin'] < 1 ? 'bg-emerald-500' : ($this->riskData['risk_of_ruin'] < 20 ? 'bg-amber-400' : 'bg-rose-600');
                                    @endphp
                                    <div class="{{ $colorClass }} absolute top-0 h-full w-full origin-bottom rounded-t-full opacity-80 transition-transform duration-1000 ease-out"
                                         style="transform: rotate({{ $deg - 180 }}deg);"></div>

                                    <div class="absolute bottom-0 z-10 flex h-20 w-32 items-end justify-center rounded-t-full bg-white pb-2">
                                        <div class="text-center">
                                            <span class="block text-3xl font-black text-gray-900">{{ $this->riskData['risk_of_ruin'] }}%</span>
                                            <span class="text-[10px] font-bold uppercase text-gray-400">Prob. Ruina</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 text-center text-xs">
                                    @if ($this->riskData['risk_of_ruin'] < 1)
                                        <span class="font-bold text-emerald-600">✅ Zona Segura</span>
                                    @elseif($this->riskData['risk_of_ruin'] < 10)
                                        <span class="font-bold text-amber-500">⚠️ Precaución</span>
                                    @else
                                        <span class="font-bold text-rose-600">🚨 Peligro Crítico</span>
                                    @endif
                                    <p class="mt-1 text-[10px] text-gray-400">
                                        @if ($this->riskData['edge'] > 0)
                                            Tienes ventaja estadística (Edge: {{ $this->riskData['edge'] }}).
                                        @else
                                            Tu esperanza matemática es negativa.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Tabla de Rachas --}}
                            <div>
                                <h4 class="mb-3 text-xs font-bold text-gray-500">Probabilidad de Racha (Losing Streak)</h4>
                                <div class="space-y-3">

                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600">
                                            <span>3 Pérdidas seguidas</span>
                                            <span>{{ $this->riskData['streak_prob']['3'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100">
                                            <div class="h-1.5 rounded-full bg-gray-400"
                                                 style="width: {{ min(100, $this->riskData['streak_prob']['3']) }}%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600">
                                            <span>5 Pérdidas seguidas</span>
                                            <span class="{{ $this->riskData['streak_prob']['5'] > 50 ? 'text-rose-500 font-bold' : '' }}">{{ $this->riskData['streak_prob']['5'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100">
                                            <div class="{{ $this->riskData['streak_prob']['5'] > 20 ? 'bg-amber-400' : 'bg-gray-400' }} h-1.5 rounded-full"
                                                 style="width: {{ min(100, $this->riskData['streak_prob']['5']) }}%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600">
                                            <span>8 Pérdidas seguidas</span>
                                            <span>{{ $this->riskData['streak_prob']['8'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100">
                                            <div class="h-1.5 rounded-full bg-rose-400"
                                                 style="width: {{ min(100, $this->riskData['streak_prob']['8']) }}%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-3 rounded-md bg-indigo-50 p-2 text-[10px] leading-tight text-indigo-800">
                                        <i class="fa-solid fa-circle-info mr-1"></i>
                                        Si tienes un <strong>{{ $this->riskData['streak_prob']['5'] }}%</strong> de perder 5 veces seguidas, asegúrate de que 5 pérdidas no quemen más del 10% de tu cuenta.
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Mistakes Chart --}}
                <div class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="mistakesChart(@js($this->mistakesData))">

                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-bug text-rose-500"></i> Ranking de Errores
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">Coste total y frecuencia.</p>
                        </div>
                    </div>

                    <div class="relative min-h-[250px] w-full flex-1">
                        <template x-if="!hasData">
                            <div class="absolute inset-0 flex h-full flex-col items-center justify-center text-center text-gray-400">
                                <div class="mb-3 rounded-full bg-emerald-50 p-4">
                                    <i class="fa-solid fa-shield-halved text-2xl text-emerald-400"></i>
                                </div>
                                <p class="text-xs font-medium">Operativa limpia.</p>
                            </div>
                        </template>

                        <div id="mistakesChart"
                             class="w-full"
                             x-show="hasData"></div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
