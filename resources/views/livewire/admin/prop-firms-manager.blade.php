<div class="min-h-screen bg-gray-50 p-6 font-sans lg:p-10"
     x-data="propManager(@js($tree))"
     x-cloak>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Scrollbar fina y elegante */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            bg-transparent;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #CBD5E1;
            border-radius: 20px;
        }
    </style>

    <x-confirm-modal />


    <x-modal-template show="showAlert">
    </x-modal-template>

    {{-- HEADER --}}
    <div class="mx-auto mb-10 max-w-7xl">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            {{-- Breadcrumbs / Tabs --}}
            <nav class="flex space-x-1 rounded-xl border border-gray-200/60 bg-white p-1 shadow-sm">
                <button class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold transition-all"
                        @click="view = 'firms'; selectedFirmId = null; selectedProgramId = null"
                        :class="view === 'firms' ? 'bg-gray-100 text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'">
                    <svg class="h-4 w-4"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                    </svg>
                    Empresas
                </button>

                <div class="flex items-center"
                     x-show="selectedFirmId"
                     x-transition>
                    <svg class="mx-1 h-4 w-4 text-gray-300"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 5l7 7-7 7" />
                    </svg>
                    <button class="rounded-lg px-4 py-2 text-sm font-bold transition-all"
                            @click="selectFirm(selectedFirmId)"
                            :class="view === 'programs' ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-900'">
                        <span x-text="activeFirm?.name"></span>
                    </button>
                </div>

                <div class="flex items-center"
                     x-show="selectedProgramId"
                     x-transition>
                    <svg class="mx-1 h-4 w-4 text-gray-300"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm">
                        <span x-text="activeProgram?.name"></span>
                    </span>
                </div>
            </nav>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                        x-show="view === 'firms'"
                        @click="openFirmModal()">
                    <svg class="h-4 w-4"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2.5"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nueva Empresa
                </button>
                <button class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-gray-800"
                        x-show="view === 'programs'"
                        @click="openProgramModal()">
                    <svg class="h-4 w-4"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2.5"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo Programa
                </button>
                <button class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-gray-800"
                        x-show="view === 'levels'"
                        @click="openLevelModal()">
                    <svg class="h-4 w-4"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="2.5"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo Nivel
                </button>
            </div>
        </div>
    </div>

    {{-- CONTENT AREA --}}
    <div class="mx-auto max-w-7xl">

        {{-- VIEW: FIRMS --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
             x-show="view === 'firms'"
             x-transition.opacity.duration.300ms>

            <template x-for="firm in firms"
                      :key="firm.id">
                <div class="group relative cursor-pointer rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-indigo-500/50 hover:shadow-xl"
                     @click="selectFirm(firm.id)">

                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                            <template x-if="firm.logo_path">
                                <img class="h-full w-full object-cover"
                                     :src="'/storage/' + firm.logo_path">
                            </template>
                            <template x-if="!firm.logo_path">
                                <span class="text-2xl font-black text-gray-300"
                                      x-text="firm.name.charAt(0)"></span>
                            </template>
                        </div>
                        <button class="p-2 text-gray-300 transition-colors hover:text-indigo-600"
                                @click.stop="openFirmModal(firm)">
                            <svg class="h-5 w-5"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </div>

                    <h3 class="mb-1 text-lg font-bold text-gray-900"
                        x-text="firm.name"></h3>
                    <p class="mb-6 flex items-center gap-1 text-xs font-medium text-gray-500">
                        <svg class="h-3 w-3"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                        <span x-text="firm.server"></span>
                    </p>

                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Programas</span>
                        <span class="inline-flex items-center justify-center rounded-md bg-gray-900 px-2.5 py-1 text-xs font-bold text-white"
                              x-text="firm.programs.length"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- VIEW: PROGRAMS --}}
        <div x-show="view === 'programs'"
             x-transition.opacity>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-8 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Programa</th>
                            <th class="px-8 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Fases</th>
                            <th class="px-8 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Niveles</th>
                            <th class="relative px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="program in currentPrograms"
                                  :key="program.id">
                            <tr class="group cursor-pointer transition-colors hover:bg-indigo-50/50"
                                @click="selectProgram(program.id)">
                                <td class="px-8 py-5">
                                    <span class="text-sm font-bold text-gray-900 transition-colors group-hover:text-indigo-600"
                                          x-text="program.name"></span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                        <span x-text="program.step_count == 0 ? 'Instant' : program.step_count + ' Pasos'"></span>
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-sm font-medium text-gray-600"
                                          x-text="program.levels.length + ' Balances'"></span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <svg class="h-5 w-5 text-gray-300 group-hover:text-indigo-600"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M9 5l7 7-7 7" />
                                    </svg>
                                </td>
                            </tr>
                        </template>
                        <template x-if="currentPrograms.length === 0">
                            <tr>
                                <td class="p-12 text-center text-gray-400"
                                    colspan="4">No hay programas configurados.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- VIEW: LEVELS --}}
        <div x-show="view === 'levels'"
             x-transition.opacity>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="level in currentLevels"
                          :key="level.id">
                    <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg">

                        <div class="absolute -right-4 -top-4 select-none text-8xl font-black text-gray-50 transition-colors group-hover:text-indigo-50"
                             x-text="level.currency"></div>

                        <div class="relative z-10 mb-6 flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-black tracking-tight text-gray-900"
                                    x-text="level.name"></h3>
                                <div class="mt-2 flex gap-2">
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-bold text-green-700 ring-1 ring-inset ring-green-600/20">
                                        Fee: $<span x-text="level.fee"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <button class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                                        @click="openLevelModal(level)"><svg class="h-4 w-4"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg></button>
                                {{-- DUPLICAR (confirm modal) --}}
                                <button class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                        @click.stop="$dispatch('open-confirm-modal', {
        title: '¿Clonar nivel?',
        text: 'Se creará una copia idéntica de ' + level.name + ' con todas sus reglas.',
        type: 'indigo',
        action: 'duplicateLevel',
        params: level.id
    })"
                                        title="Clonar">
                                    <svg class="h-4 w-4"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>

                                {{-- ELIMINAR (confirm modal) --}}
                                <button class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                        @click.stop="$dispatch('open-confirm-modal', {
        title: '¿Eliminar nivel?',
        text: 'Esta acción no se puede deshacer. Se borrará ' + level.name + ' y sus reglas.',
        type: 'red',
        action: 'deleteLevel',
        params: level.id
    })"
                                        title="Eliminar">
                                    <svg class="h-4 w-4"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="relative z-10 space-y-2">
                            <template x-for="objective in level.objectives"
                                      :key="objective.id">
                                <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-white/80 p-2.5 backdrop-blur-sm"
                                     :class="objective.phase_number == 0 ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-indigo-500'">

                                    <div>
                                        <p class="text-xs font-bold text-gray-900"
                                           x-text="objective.name"></p>
                                        <p class="text-[10px] text-gray-500"
                                           x-text="objective.min_trading_days + ' dias min'"></p>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-[10px] font-bold text-indigo-600"
                                             x-show="objective.phase_number !== 0">
                                            Target: <span x-text="objective.profit_target_percent + '%'"></span>
                                        </div>
                                        <div class="text-[10px] font-bold text-red-500">
                                            DD: <span x-text="objective.max_total_loss_percent + '%'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- MODALS - FIXED STRUCTURE --}}

    {{-- MODAL FIRM --}}
    <div class="relative z-50"
         x-show="modals.firm">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             x-show="modals.firm"
             x-transition.opacity></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md"
                     x-show="modals.firm"
                     x-transition.scale
                     @click.outside="modals.firm = false">

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="mb-5 text-lg font-bold leading-6 text-gray-900">
                            <span x-text="firmForm.id ? 'Editar Empresa' : 'Nueva Empresa'"></span>
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Nombre</label>
                                <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                       type="text"
                                       x-model="firmForm.name">
                                @error('firmForm.name')
                                    <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Website</label>
                                <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                       type="text"
                                       x-model="firmForm.website">
                                @error('firmForm.website')
                                    <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Server MT5</label>
                                <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                       type="text"
                                       x-model="firmForm.server">
                                @error('firmForm.server')
                                    <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Logo</label>
                                <input class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                       type="file"
                                       wire:model="firmForm.logo">
                                @error('firmForm.logo')
                                    <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Footer: Botón Directo (Sin Confirmación) --}}
                    <div class="gap-2 bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:w-auto"
                                type="button"
                                wire:click="saveFirm"
                                wire:loading.attr="disabled">
                            Guardar
                        </button>
                        <button class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                type="button"
                                @click="modals.firm = false">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- MODAL PROGRAM --}}
    <div class="relative z-50"
         x-show="modals.program">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             x-show="modals.program"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md"
                     x-show="modals.program"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="mb-5 text-lg font-bold leading-6 text-gray-900">Nuevo Programa</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Nombre Programa</label>
                                <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                       type="text"
                                       x-model="programForm.name">
                            </div>
                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Tipo de Evaluación</label>
                                <select class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        x-model="programForm.step_count">
                                    <option value="1">1 Step (Una Fase)</option>
                                    <option value="2">2 Steps (Estándar)</option>
                                    <option value="3">3 Steps (Largo Plazo)</option>
                                    <option value="0">Instant Funding</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                                type="button"
                                wire:click="saveProgram">Crear</button>
                        <button class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                type="button"
                                @click="modals.program = false">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL LEVEL (THE BIG ONE) --}}
    {{-- MODAL LEVEL --}}
    <div class="relative z-50"
         x-show="modals.level">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             x-show="modals.level"
             x-transition.opacity></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl border border-gray-100 bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-5xl"
                     x-show="modals.level">

                    {{-- HEADER --}}
                    <div class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                        <div>
                            <h3 class="text-xl font-bold leading-6 text-gray-900"
                                x-text="levelForm.id ? 'Editar Nivel' : 'Crear Nuevo Nivel'"></h3>
                            <p class="mt-1 text-sm text-gray-500">Configura parámetros de riesgo y objetivos.</p>
                        </div>
                        <button class="text-gray-400 hover:text-gray-500"
                                @click="modals.level = false"><i class="fa-solid fa-xmark fa-lg"></i></button>
                    </div>

                    <div class="flex h-[70vh] flex-col lg:flex-row">

                        {{-- SIDEBAR: DATOS GENERALES --}}
                        <div class="w-full space-y-5 overflow-y-auto border-r border-gray-200 bg-gray-50 p-6 lg:w-80">
                            <div class="text-xs font-bold uppercase tracking-wider text-indigo-600">Configuración Base</div>

                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Nombre</label>
                                <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                       type="text"
                                       x-model="levelForm.name">
                                @error('levelForm.name')
                                    <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium leading-6 text-gray-900">Balance</label>
                                    <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                           type="number"
                                           x-model="levelForm.size">
                                    @error('levelForm.size')
                                        <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium leading-6 text-gray-900">Divisa</label>
                                    <select class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            x-model="levelForm.currency">
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium leading-6 text-gray-900">Precio (Fee)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input class="block w-full rounded-md border-0 py-1.5 pl-7 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                           type="number"
                                           x-model="levelForm.fee">
                                </div>
                                @error('levelForm.fee')
                                    <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- MAIN: REGLAS POR FASE --}}
                        <div class="custom-scroll flex-1 overflow-y-auto bg-white p-6">
                            <div class="mb-4 text-xs font-bold uppercase tracking-wider text-emerald-600">Reglas por Fase</div>

                            <div class="space-y-4">
                                @foreach ([0, 1, 2, 3] as $phase)
                                    <template x-if="objectivesForm && objectivesForm[{{ $phase }}]">
                                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">

                                            {{-- Header de la Fase --}}
                                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3"
                                                 :class="{{ $phase }} === 0 ? 'bg-emerald-50' : 'bg-gray-50'">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-900"
                                                          x-text="objectivesForm[{{ $phase }}]?.name"></span>
                                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                                          :class="{{ $phase }} === 0 ? 'bg-white text-emerald-700 ring-emerald-600/20' : 'bg-white text-gray-600 ring-gray-500/10'">
                                                        {{ $phase == 0 ? 'LIVE ACCOUNT' : 'PHASE ' . $phase }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Inputs de Reglas --}}
                                            <div class="grid grid-cols-2 gap-4 p-4 md:grid-cols-4">

                                                {{-- Profit Target --}}
                                                <div x-show="{{ $phase }} !== 0">
                                                    <label class="block text-[10px] font-bold uppercase text-gray-500">Profit Target %</label>
                                                    <input class="mt-1 block w-full rounded-md border-0 py-1.5 font-bold text-gray-900 text-indigo-600 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6"
                                                           type="number"
                                                           step="0.1"
                                                           x-model="objectivesForm[{{ $phase }}].profit_target_percent">
                                                    @error('objectivesForm.' . $phase . '.profit_target_percent')
                                                        <span class="block text-[10px] font-bold text-red-500">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                {{-- Max Daily Loss --}}
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase text-gray-500">Max Daily %</label>
                                                    <input class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 text-red-600 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-xs sm:leading-6"
                                                           type="number"
                                                           step="0.1"
                                                           x-model="objectivesForm[{{ $phase }}].max_daily_loss_percent">
                                                    @error('objectivesForm.' . $phase . '.max_daily_loss_percent')
                                                        <span class="block text-[10px] font-bold text-red-500">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                {{-- Max Total Loss --}}
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase text-gray-500">Max Total %</label>
                                                    <input class="mt-1 block w-full rounded-md border-0 py-1.5 font-bold text-gray-900 text-red-700 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-red-600 sm:text-xs sm:leading-6"
                                                           type="number"
                                                           step="0.1"
                                                           x-model="objectivesForm[{{ $phase }}].max_total_loss_percent">
                                                    @error('objectivesForm.' . $phase . '.max_total_loss_percent')
                                                        <span class="block text-[10px] font-bold text-red-500">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                {{-- Min Trading Days --}}
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase text-gray-500">Min Days</label>
                                                    <input class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6"
                                                           type="number"
                                                           x-model="objectivesForm[{{ $phase }}].min_trading_days">
                                                    @error('objectivesForm.' . $phase . '.min_trading_days')
                                                        <span class="block text-[10px] font-bold text-red-500">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                {{-- Calculation Method --}}
                                                <div class="col-span-2 mt-1 border-t border-gray-100 pt-3 md:col-span-4">
                                                    <label class="mb-1 block text-[10px] font-bold uppercase text-gray-400">Calculation Method</label>
                                                    <select class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-xs sm:leading-6"
                                                            x-model="objectivesForm[{{ $phase }}].loss_type">
                                                        <option value="balance_based">Balance Based (Static)</option>
                                                        <option value="equity_based">Equity Based (Floating)</option>
                                                        <option value="relative">Relative / Trailing</option>
                                                    </select>
                                                </div>



                                                <!-- SECCIÓN: REGLAS AVANZADAS (JSON) -->
                                                <div class="col-span-2 mt-4 md:col-span-4"
                                                     x-data="{ showRules: false }">

                                                    <!-- Botón Toggle -->
                                                    <button class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 transition-colors hover:text-indigo-600"
                                                            type="button"
                                                            @click="showRules = !showRules">
                                                        <i class="fa-solid fa-gavel"></i>
                                                        <span>Restricciones y Reglas</span>
                                                        <i class="fa-solid fa-chevron-down transition-transform duration-200"
                                                           :class="showRules ? 'rotate-180' : ''"></i>
                                                    </button>

                                                    <!-- Panel Desplegable -->
                                                    <div class="mt-2 grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 md:grid-cols-2"
                                                         x-show="showRules"
                                                         x-collapse>

                                                        <!-- 1. Duración Mínima -->
                                                        <div>
                                                            <label class="mb-1 block text-[10px] font-bold uppercase text-gray-500">
                                                                Min. Trade Duration (Segundos)
                                                            </label>
                                                            <input class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 sm:text-xs"
                                                                   type="number"
                                                                   {{-- CORREGIDO AQUÍ: {{ $phase }} en lugar de phase --}}
                                                                   x-model.number="objectivesForm[{{ $phase }}].min_trade_duration"
                                                                   placeholder="Ej: 60">
                                                            <p class="mt-1 text-[9px] text-gray-400">Dejar vacío o 0 para desactivar.</p>
                                                        </div>

                                                        <!-- 2. Weekend Holding (Toggle) -->
                                                        <div class="flex items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2">
                                                            <span class="text-xs font-medium text-gray-700">Permitir Weekend Holding</span>

                                                            <button class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600"
                                                                    type="button"
                                                                    {{-- CORREGIDO AQUÍ: {{ $phase }} --}}
                                                                    @click="objectivesForm[{{ $phase }}].weekend_holding = !objectivesForm[{{ $phase }}].weekend_holding"
                                                                    :class="objectivesForm[{{ $phase }}].weekend_holding ? 'bg-indigo-600' : 'bg-gray-200'">
                                                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                                      :class="objectivesForm[{{ $phase }}].weekend_holding ? 'translate-x-4' : 'translate-x-0'"></span>
                                                            </button>
                                                        </div>

                                                        <!-- 3. News Trading -->
                                                        <div class="col-span-1 border-t border-gray-200 pt-3 md:col-span-2">
                                                            <div class="mb-2 flex items-center gap-2">
                                                                <input class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                                       type="checkbox"
                                                                       {{-- CORREGIDO AQUÍ: {{ $phase }} --}}
                                                                       x-model="objectivesForm[{{ $phase }}].news_trading_enabled">
                                                                <label class="text-xs font-bold text-gray-700">Restringir Operativa en Noticias (News Trading)</label>
                                                            </div>

                                                            <!-- Inputs Condicionales para Noticias -->
                                                            <div class="grid grid-cols-2 gap-4 pl-6"
                                                                 {{-- CORREGIDO AQUÍ: {{ $phase }} --}}
                                                                 x-show="objectivesForm[{{ $phase }}].news_trading_enabled"
                                                                 x-transition>
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-500">Minutos ANTES</label>
                                                                    <input class="block w-full rounded-md border-0 py-1 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-xs"
                                                                           type="number"
                                                                           {{-- CORREGIDO AQUÍ: {{ $phase }} --}}
                                                                           x-model.number="objectivesForm[{{ $phase }}].news_minutes_before">
                                                                </div>
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-500">Minutos DESPUÉS</label>
                                                                    <input class="block w-full rounded-md border-0 py-1 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-xs"
                                                                           type="number"
                                                                           {{-- CORREGIDO AQUÍ: {{ $phase }} --}}
                                                                           x-model.number="objectivesForm[{{ $phase }}].news_minutes_after">
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>


                                            </div>
                                        </div>
                                    </template>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Footer: Botones --}}
                    <div class="flex flex-row-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                                type="button"
                                wire:click="saveLevel"
                                wire:loading.attr="disabled">
                            Guardar Configuración
                        </button>
                        <button class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                type="button"
                                @click="modals.level = false">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
