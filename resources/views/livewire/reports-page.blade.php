<div class="min-h-screen bg-gray-50 p-6 dark:bg-gray-900"
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
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white dark:bg-gray-900"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex flex-col items-center">
                <x-loader />
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- HEADER CON SELECTOR Y BADGES --}}
    {{-- ============================================ --}}

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-flask-vial text-2xl text-indigo-600 dark:text-indigo-400"></i>
                <h1 class="text-3xl font-black text-gray-900 dark:text-gray-100">{{ __('menu.laboratory') }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('menu.resume_laboratory') }}</p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Selector de cuenta (Livewire) --}}
            <select class="rounded-lg border-gray-300 text-sm font-bold shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    wire:model.live="accountId">
                <option value="all">{{ __('labels.all_accounts') }}</option>
                @foreach ($this->accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>

            {{-- Badge de escenarios activos --}}
            <div class="flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300"
                 x-show="hasActiveScenarios()"
                 x-transition>
                <svg class="h-4 w-4"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                          clip-rule="evenodd" />
                </svg>
                <span x-text="countActiveScenarios() + '{{ __('labels.scenario') }}' + (countActiveScenarios() > 1 ? 's' : '') + '{{ __('labels._active') }}' + (countActiveScenarios() > 1 ? 's' : '')"></span>
            </div>

            {{-- Badge de cambios pendientes --}}
            <div class="flex animate-pulse items-center gap-2 rounded-full bg-yellow-100 px-3 py-1.5 text-xs font-bold text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-300"
                 x-show="hasUnsavedChanges"
                 x-transition>
                <svg class="h-4 w-4"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                          clip-rule="evenodd" />
                </svg>
                {{ __('labels.pending_changes') }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- ============================================ --}}
        {{-- COLUMNA IZQUIERDA: CONTROLES (ALPINE-FIRST) --}}
        {{-- ============================================ --}}

        <div class="col-span-12 space-y-6 lg:col-span-3">

            {{-- TARJETA 1: LABORATORIO DE ESTRATEGIA (VERSIÓN FINAL) --}}
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                        <i class="fa-solid fa-flask text-indigo-600 dark:text-indigo-400"></i> {{ __('menu.laboratory') }}
                    </h3>

                    <button class="flex items-center gap-1 text-xs text-gray-500 transition hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
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
                        {{ __('labels.reset') }}
                    </button>
                </div>

                {{-- Tabs Navigation (Solo 2) --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button class="flex-1 border-b-2 px-4 py-3 text-xs font-bold uppercase transition"
                            @click="activeTab = 'mechanical'"
                            :class="activeTab === 'mechanical' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                        <i class="fa-solid fa-gears mr-1"></i> {{ __('labels.mechanic') }}
                    </button>
                    <button class="flex-1 border-b-2 px-4 py-3 text-xs font-bold uppercase transition"
                            @click="activeTab = 'discipline'"
                            :class="activeTab === 'discipline' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                        <i class="fa-solid fa-brain mr-1"></i> {{ __('labels.discipline') }}
                    </button>
                </div>

                {{-- Tab Content --}}
                <div class="p-6">
                    {{-- TAB 1: SIMULADOR MECÁNICO --}}
                    <div class="space-y-4"
                         x-show="activeTab === 'mechanical'"
                         x-transition>
                        <p class="text-[10px] leading-tight text-gray-400 dark:text-gray-500">
                            {{ __('labels.recalculating_results') }}
                        </p>

                        <div class="grid grid-cols-2 gap-3">
                            {{-- Fixed SL --}}
                            <div>
                                <label class="mb-1 block text-[10px] font-bold text-gray-500 dark:text-gray-400">{{ __('labels.fixed_sl') }}</label>
                                <div class="relative">
                                    <input class="w-full rounded-lg border-gray-200 bg-white py-2 pl-2 pr-12 text-xs font-bold text-rose-600 placeholder-gray-300 focus:border-rose-500 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-700 dark:text-rose-400"
                                           type="number"
                                           step="0.1"
                                           x-model="scenarios.fixed_sl"
                                           @input="onScenarioChange()"
                                           placeholder="Ej: 15">
                                    <span class="absolute right-2 top-2 text-[10px] font-bold text-gray-400 dark:text-gray-500">{{ __('labels.pts') }}</span>
                                </div>
                            </div>

                            {{-- Fixed TP --}}
                            <div>
                                <label class="mb-1 block text-[10px] font-bold text-gray-500 dark:text-gray-400">{{ __('labels.fixed_tp') }}</label>
                                <div class="relative">
                                    <input class="w-full rounded-lg border-gray-200 bg-white py-2 pl-2 pr-12 text-xs font-bold text-emerald-600 placeholder-gray-300 focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-emerald-400"
                                           type="number"
                                           step="0.1"
                                           x-model="scenarios.fixed_tp"
                                           @input="onScenarioChange()"
                                           placeholder="Ej: 30">
                                    <span class="absolute right-2 top-2 text-[10px] font-bold text-gray-400 dark:text-gray-500">{{ __('labels.pts') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-blue-50 p-3 text-[10px] leading-relaxed text-blue-800 dark:bg-blue-500/10 dark:text-blue-300">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            {!! __('labels.simulator_explanation') !!}
                        </div>
                    </div>

                    {{-- TAB 2: FILTROS DE DISCIPLINA --}}
                    <div class="space-y-4"
                         x-show="activeTab === 'discipline'"
                         x-transition>
                        {{-- Fatiga --}}
                        <div>
                            <label class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                {{ __('labels.fatigue_control') }}
                            </label>
                            <select class="w-full rounded-lg border-gray-200 text-xs font-bold text-gray-600 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    x-model="scenarios.max_daily_trades"
                                    @change="onScenarioChange()">
                                <option value="">{{ __('labels.all_trades') }}</option>
                                <option value="1">{{ __('labels.only_first_trade') }}</option>
                                <option value="2">{{ __('labels.two_trades') }}</option>
                                <option value="3">{{ __('labels.three_trades') }}</option>
                                <option value="4">{{ __('labels.four_trades') }}</option>
                            </select>
                        </div>

                        {{-- Días de la semana --}}
                        <div>
                            <label class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                {{ __('labels.exclude_days') }}
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                                       :class="isDayExcluded(1) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(1)"
                                           @change="toggleDay(1)">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.monday') }}</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                                       :class="isDayExcluded(2) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(2)"
                                           @change="toggleDay(2)">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.tuesday') }}</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                                       :class="isDayExcluded(3) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(3)"
                                           @change="toggleDay(3)">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.wednesday') }}</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                                       :class="isDayExcluded(4) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(4)"
                                           @change="toggleDay(4)">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.thursday') }}</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                                       :class="isDayExcluded(5) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/20' : ''">
                                    <input class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                                           type="checkbox"
                                           :checked="isDayExcluded(5)"
                                           @change="toggleDay(5)">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.friday') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Toggles compactos --}}
                        <div class="space-y-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.only_longs') }}</span>
                                <div class="relative inline-block h-5 w-9 select-none align-middle">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="scenarios.only_longs"
                                           @change="onScenarioChange()" />
                                    <div
                                         class="peer h-5 w-9 rounded-full bg-gray-200 dark:bg-gray-600 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white dark:after:bg-gray-800 after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-4">
                                    </div>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.only_shorts') }}</span>
                                <div class="relative inline-block h-5 w-9 select-none align-middle">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="scenarios.only_shorts"
                                           @change="onScenarioChange()" />
                                    <div
                                         class="peer h-5 w-9 rounded-full bg-gray-200 dark:bg-gray-600 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white dark:after:bg-gray-800 after:transition-all peer-checked:bg-rose-500 peer-checked:after:translate-x-4">
                                    </div>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('labels.delete_five_worst') }}</span>
                                <div class="relative inline-block h-5 w-9 select-none align-middle">
                                    <input class="peer sr-only"
                                           type="checkbox"
                                           x-model="scenarios.remove_worst"
                                           @change="onScenarioChange()" />
                                    <div
                                         class="peer h-5 w-9 rounded-full bg-gray-200 dark:bg-gray-600 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white dark:after:bg-gray-800 after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-4">
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Footer: Botón Aplicar --}}
                <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                    <button class="flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-bold transition"
                            @click="applyScenarios()"
                            :disabled="!hasUnsavedChanges || isApplying"
                            :class="{
                                'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md': hasUnsavedChanges && !isApplying,
                                'bg-gray-200 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500': !hasUnsavedChanges || isApplying
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

                        <span x-text="isApplying ? '{{ __('labels.applying') }}' : '{{ __('labels.apply_sim') }}'"></span>
                    </button>
                </div>
            </div>



            {{-- TARJETA 2: CALIDAD DEL SISTEMA (SQN) --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-1 font-bold text-gray-900 dark:text-gray-100">{{ __('labels.system_quality') }}</h3>

                <div class="mb-2 mt-4 flex items-end gap-3">
                    {{-- Valor Real --}}
                    <div>
                        <span class="text-4xl font-black text-gray-900 dark:text-gray-100">{{ $this->realStats['sqn'] ?? '0.0' }}</span>
                        <span class="block text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.actual') }}</span>
                    </div>

                    {{-- Valor Simulado (si existe) --}}
                    @if ($this->simulatedData['stats'])
                        <div class="mb-1 text-gray-300 dark:text-gray-600"><i class="fa-solid fa-arrow-right"></i></div>
                        <div>
                            <span class="text-2xl font-black text-indigo-500">{{ $this->simulatedData['stats']['sqn'] }}</span>
                            <span class="block text-[10px] font-bold uppercase text-indigo-300">{{ __('labels.simulated') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Barra de Progreso --}}
                <div class="mb-2 mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-2 rounded-full bg-gray-800 transition-all duration-500 dark:bg-gray-300"
                         style="width: {{ min(100, (($this->realStats['sqn'] ?? 0) / 5) * 100) }}%"></div>
                </div>

                <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">
                    <span>{{ __('labels.sqn_poor') }}</span>
                    <span>{{ __('labels.sqn_good') }}</span>
                </div>

                {{-- Diagnóstico --}}
                <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-xs leading-relaxed text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                    @if (($this->realStats['sqn'] ?? 0) < 1.6)
                        <div class="flex gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                            <span>{{ __('labels.hard_to_operate') }}</span>
                        </div>
                    @elseif(($this->realStats['sqn'] ?? 0) < 3.0)
                        <div class="flex gap-2">
                            <i class="fa-solid fa-check mt-0.5"></i>
                            <span>{{ __('labels.good_system') }}</span>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <i class="fa-solid fa-trophy mt-0.5"></i>
                            <span>{{ __('labels.excellent_system') }}</span>
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
            <div class="relative flex flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                 x-data="equityChart(@js($this->realCurve), @js($this->simulatedData['curve']))"
                 x-effect="updateData(@js($this->realCurve), @js($this->simulatedData['curve']))">

                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100">{{ __('labels.equitiy_curve') }}</h3>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                            <span class="h-3 w-3 rounded-full bg-slate-300"></span> {{ __('labels.reality') }}
                        </div>
                        @if (!empty($this->simulatedData['curve']))
                            <div class="flex items-center gap-2 text-xs font-bold text-indigo-600">
                                <span class="h-3 w-3 rounded-full bg-indigo-500"></span> {{ __('labels.simulation') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div id="equityChart"
                     class="h-[400px] w-full"></div>

                {{-- Loading Overlay --}}
                <div class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/50 backdrop-blur-[1px] dark:bg-gray-900/50"
                     x-show="isApplying"
                     x-transition>
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                </div>
            </div>

            {{-- 2. REPORTES TEMPORALES --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data="barChart(@js($this->hourlyReportData), 'hour', '{{ __('labels.pl_by_hour') }}')">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                        <i class="fa-regular fa-clock text-blue-600"></i> {{ __('labels.performance_by_hour') }}
                    </h3>
                    <div id="hourlyChart"
                         class="h-[250px] w-full"></div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data="barChart(@js($this->sessionReportData), 'session', '{{ __('labels.pl_by_sesion') }}')">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                        <i class="fa-solid fa-globe-americas text-green-600"></i> {{ __('labels.performance_by_session') }}
                    </h3>
                    <div id="sessionChart"
                         class="h-[250px] w-full"></div>
                </div>
            </div>

            {{-- 3. PSICOLOGÍA Y GESTIÓN --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- Efficiency Chart --}}
                <div class="col-span-1 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:col-span-2"
                     x-data="efficiencyChart(@js($this->efficiencyData))">

                    <div class="mb-6 flex flex-col justify-between sm:flex-row sm:items-end">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                                <i class="fa-solid fa-crosshairs text-indigo-600"></i> {{ __('labels.efficiency_of_trader') }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('labels.comparative_last_15_trades') }}
                                <span class="font-bold text-rose-500">{{ __('labels.risk_suffered') }}</span> {{ __('labels.vs') }}
                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ __('labels.result_pnl') }}</span> {{ __('labels.vs') }}
                                <span class="font-bold text-emerald-500">{{ __('labels.max_potencial_mfe') }}</span>.
                            </p>
                        </div>
                    </div>

                    <div id="efficiencyChart"
                         class="h-[350px] w-full"></div>

                    <div class="mt-4 flex flex-wrap justify-center gap-4 text-[10px] text-gray-400 dark:text-gray-500">
                        <div class="flex items-center gap-1">
                            <span class="block h-2 w-2 rounded-full bg-rose-400"></span> {{ __('labels.mae_explain') }}
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="block h-2 w-2 rounded-full bg-emerald-400"></span> {{ __('labels.mfe_explain') }}
                        </div>
                    </div>
                </div>

                {{-- Distribution Chart --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data="distributionChart(@js($this->distributionData))">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                                <i class="fa-solid fa-chart-column text-purple-500"></i> {{ __('labels.pnl_distribution') }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('labels.results_frequency') }}</p>
                        </div>
                    </div>
                    <div id="distChart"
                         class="h-[250px] w-full"></div>
                </div>

                {{-- Radar Chart --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data="radarChart(@js($this->radarData))">

                    <div class="mb-2 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                                <i class="fa-solid fa-fingerprint text-purple-600"></i> {{ __('labels.trader_profile') }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('labels.analyze_360') }}</p>
                        </div>
                    </div>

                    <div id="radarChart"
                         class="flex h-[250px] w-full items-center justify-center"></div>
                </div>

                {{-- Risk Analysis --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data>
                    <div class="mb-4">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                            <i class="fa-solid fa-skull-crossbones text-gray-800 dark:text-gray-300"></i> {{ __('labels.analysis_risk') }}
                        </h3>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500"> {{ __('labels.maths_probs') }} ({{ $this->riskData['win_rate'] ?? 0 }}%) {{ __('labels.and_rate') }} (1:{{ $this->riskData['payoff'] ?? 0 }}).</p>
                    </div>

                    @if (empty($this->riskData))
                        <div class="flex h-32 items-center justify-center text-xs text-gray-400 dark:text-gray-500">
                            {{ __('labels.need_ten_trades') }}
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            {{-- Gauge de Ruina --}}
                            <div class="flex flex-col items-center justify-center border-r border-gray-100 pr-0 md:pr-6 dark:border-gray-700">

                                <div class="relative flex h-32 w-48 items-end justify-center overflow-hidden">
                                    <div class="absolute top-0 h-full w-full rounded-t-full bg-gray-100 dark:bg-gray-700"></div>

                                    @php
                                        $deg = ($this->riskData['risk_of_ruin'] / 100) * 180;
                                        $colorClass = $this->riskData['risk_of_ruin'] < 1 ? 'bg-emerald-500' : ($this->riskData['risk_of_ruin'] < 20 ? 'bg-amber-400' : 'bg-rose-600');
                                    @endphp
                                    <div class="{{ $colorClass }} absolute top-0 h-full w-full origin-bottom rounded-t-full opacity-80 transition-transform duration-1000 ease-out"
                                         style="transform: rotate({{ $deg - 180 }}deg);"></div>

                                    <div class="absolute bottom-0 z-10 flex h-20 w-32 items-end justify-center rounded-t-full bg-white pb-2 dark:bg-gray-800">
                                        <div class="text-center">
                                            <span class="block text-3xl font-black text-gray-900 dark:text-gray-100">{{ $this->riskData['risk_of_ruin'] }}%</span>
                                            <span class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.prob_ruin') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 text-center text-xs">
                                    @if ($this->riskData['risk_of_ruin'] < 1)
                                        <span class="font-bold text-emerald-600">{{ __('labels.safe_zone') }}</span>
                                    @elseif($this->riskData['risk_of_ruin'] < 10)
                                        <span class="font-bold text-amber-500">{{ __('labels.precaution') }}</span>
                                    @else
                                        <span class="font-bold text-rose-600">{{ __('labels.critic_danger') }}</span>
                                    @endif
                                    <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                        @if ($this->riskData['edge'] > 0)
                                            {{ __('labels.statistical_edge') }} ({{ __('labels.edge') }} {{ $this->riskData['edge'] }}).
                                        @else
                                            {{ __('labels.negative_math_expect') }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Tabla de Rachas --}}
                            <div>
                                <h4 class="mb-3 text-xs font-bold text-gray-500 dark:text-gray-400">{{ __('labels.probability_streak') }}</h4>
                                <div class="space-y-3">

                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600 dark:text-gray-300">
                                            <span>{{ __('labels.3_streak_losses') }}</span>
                                            <span>{{ $this->riskData['streak_prob']['3'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-1.5 rounded-full bg-gray-400 dark:bg-gray-600"
                                                 style="width: {{ min(100, $this->riskData['streak_prob']['3']) }}%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600 dark:text-gray-300">
                                            <span>{{ __('labels.5_streak_losses') }}</span>
                                            <span class="{{ $this->riskData['streak_prob']['5'] > 50 ? 'text-rose-500 font-bold' : '' }}">{{ $this->riskData['streak_prob']['5'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="{{ $this->riskData['streak_prob']['5'] > 20 ? 'bg-amber-400' : 'bg-gray-400 dark:bg-gray-600' }} h-1.5 rounded-full"
                                                 style="width: {{ min(100, $this->riskData['streak_prob']['5']) }}%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600 dark:text-gray-300">
                                            <span>{{ __('labels.8_streak_losses') }}</span>
                                            <span>{{ $this->riskData['streak_prob']['8'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-1.5 rounded-full bg-rose-400"
                                                 style="width: {{ min(100, $this->riskData['streak_prob']['8']) }}%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-3 rounded-md bg-indigo-50 p-2 text-[10px] leading-tight text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-300">
                                        <i class="fa-solid fa-circle-info mr-1"></i>
                                        {{ __('labels.if_you_have') }} <strong>{{ $this->riskData['streak_prob']['5'] }}%</strong> {{ __('labels.dont_burn_your_account') }}
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Mistakes Chart --}}
                <div class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data="mistakesChart(@js($this->mistakesData))">

                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                                <i class="fa-solid fa-bug text-rose-500"></i>{{ __('labels.ranking_errors') }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('labels.price_total_frecuency') }}</p>
                        </div>
                    </div>

                    <div class="relative min-h-[250px] w-full flex-1">
                        <template x-if="!hasData">
                            <div class="absolute inset-0 flex h-full flex-col items-center justify-center text-center text-gray-400 dark:text-gray-500">
                                <div class="mb-3 rounded-full bg-emerald-50 p-4 dark:bg-emerald-500/10">
                                    <i class="fa-solid fa-shield-halved text-2xl text-emerald-400"></i>
                                </div>
                                <p class="text-xs font-medium">{{ __('labels.clean_trade') }}</p>
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
