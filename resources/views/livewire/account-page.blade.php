<div x-data="accounts()">

    {{-- ? Loading --}}
    <div wire:loading
         wire:target='updatedSelectedAccountId'>
        <x-loader></x-loader>
    </div>

    <div class="grid grid-cols-12 p-2">

        <div class="hover:shadow-3xl col-span-12 m-2 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
            <div class="min-w-0 flex-1">
                <div class="mb-3 flex items-center justify-between">
                    <span class="bg-gradient-to-r from-gray-900 to-gray-800 bg-clip-text text-xl font-black text-gray-900 text-transparent">Cuenta</span>

                    {{-- BADGE --}}
                    <div class="flex items-center space-x-2 rounded-xl border border-emerald-200 bg-emerald-100/50 px-3 py-1.5 text-xs font-bold text-emerald-800">
                        <div class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></div>
                        <span>{{ $selectedAccount?->status_formatted ?? '---' }} •
                            {{ $selectedAccount?->initial_balance ? '€' . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0€' }}
                        </span>
                    </div>
                </div>

                {{-- SELECT PREMIUM --}}
                <div class="relative">
                    <select class="group/select w-full appearance-none rounded-3xl border-2 border-gray-200 bg-gradient-to-r from-emerald-50/70 via-white to-gray-50 px-4 py-3 pr-14 text-xl font-bold text-gray-900 shadow-xl transition-all duration-300 hover:border-emerald-300 hover:shadow-xl focus:border-emerald-400 focus:shadow-2xl focus:ring-4 focus:ring-emerald-200/50"
                            wire:model.live="selectedAccountId">
                        @forelse($accounts as $account)
                            <option class="rounded-xl border-b border-gray-100 px-6 py-4 text-lg font-medium italic text-gray-600 hover:text-white"
                                    value="{{ $account->id }}">{{ $account->name }}</option>
                        @empty
                            <option value="">Sin cuentas</option>
                        @endforelse
                    </select>

                    {{-- ICONO FLECHA --}}
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                        <svg class="h-6 w-6 text-gray-500 transition-transform duration-200 group-hover/select:-translate-y-px"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                {{-- SUBINFO --}}
                <p class="mt-3 flex items-center text-sm font-medium text-gray-600">
                    <span class="mr-2 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    {{ count($accounts) }} cuentas • {{ $selectedAccount?->broker ?? 'Sin cuenta seleccionada' }}
                </p>
            </div>
        </div>

        <div class="hover:shadow-3xl col-span-8 m-1 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">

            <div class="col-span-12">
                <!-- Botones Timeframe con Alpine compartido -->
                <div class="mb-6 flex justify-center gap-2 sm:justify-start">

                    <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            {{-- wire:click="setTimeframe('1h')" --}}
                            @click="setTimeframe('1h')"
                            x-bind:class="timeframe === '1h' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="Última hora (minutos)">
                        <span>🕐 1H</span>
                    </button>

                    <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            @click="setTimeframe('24h')"
                            x-bind:class="timeframe === '24h' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="Últimas 24H (horas)">
                        <span>📅 24H</span>
                    </button>

                    <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            @click="setTimeframe('7d')"
                            x-bind:class="timeframe === '7d' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="Última semana (diario)">
                        <span>📊 7D</span>
                    </button>

                    <button class="rounded-2xl border border-slate-200 px-5 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            @click="setTimeframe('all')"
                            x-bind:class="timeframe === 'all' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="Desde inicio cuenta">
                        <span>🏁 Inicio</span>
                    </button>
                </div>
            </div>

            <canvas class="h-max-20 col-span-12 h-full max-h-96 w-full bg-white p-5 shadow-xl sm:rounded-lg"
                    x-show="!showLoadingGrafic"
                    x-ref="canvas"></canvas>

            <div class="col-span-12 h-96 w-full content-center justify-items-center bg-white p-5 shadow-xl sm:rounded-lg"
                 x-show="showLoadingGrafic">
                <!-- From Uiverse.io by TamaniPhiri -->
                <!-- From Uiverse.io by carlosepcc -->
                <div class="loader flex aspect-square w-8 animate-spin items-center justify-center rounded-full border-t-2 border-gray-500 bg-gray-300 text-yellow-700"></div>
            </div>

        </div>

        <div class="hover:shadow-3xl col-span-4 m-1 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
            <div class="grid grid-cols-12">
                <div class="col-span-12">Resumen de La Cuenta</div>
                <div class="col-span-12 flex items-center justify-between">
                    <div class="flex">
                        <div class="p-2">
                            <i class="fa-solid fa-coins text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-base">Balance Inicial</span>
                            <span class="text-sm text-gray-400">Tamaño de Cuenta</span>
                        </div>
                    </div>

                    <div>
                        {{ $selectedAccount?->initial_balance ? '€' . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0€' }}
                    </div>
                </div>
                <div class="col-span-12 flex items-center justify-between">
                    <div class="flex">
                        <div class="p-2">
                            <i class="fa-solid fa-coins text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-base">Balance Actual</span>
                            <span class="text-sm text-gray-400">Balance Activo de Cuenta</span>
                        </div>
                    </div>

                    <div>
                        {{ $selectedAccount?->current_balance ? '€' . number_format($selectedAccount->current_balance, $selectedAccount->current_balance == floor($selectedAccount->current_balance) ? 0 : 2) : '0€' }}
                    </div>

                </div>
            </div>



        </div>
    </div>



</div>
