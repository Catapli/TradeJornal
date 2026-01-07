<div x-data="accounts()">

    {{-- ? Loading --}}
    <div wire:loading
         wire:target='updatedSelectedAccountId'>
        <x-loader></x-loader>
    </div>

    {{-- Top-Right Snackbar --}}
    @if (session()->has('message'))
        <div class="{{ session('message') ? 'scale-100 opacity-100' : 'scale-95 opacity-0' }} fixed right-6 top-20 z-50 translate-y-0 transform rounded-3xl border-4 p-6 shadow-2xl transition-all duration-300"
             x-data
             x-init="$nextTick(() => setTimeout(() => $el.remove(), 5000))"
             style="min-width: 320px;">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/20">
                    <svg class="h-7 w-7 text-emerald-500"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ session('message') }}</h3>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="{{ session('error') ? 'scale-100 opacity-100' : 'scale-95 opacity-0' }} fixed right-6 top-20 z-50 translate-y-0 transform rounded-3xl border-4 border-red-400/50 bg-gradient-to-br from-red-50 to-red-100 p-6 shadow-2xl transition-all duration-300"
             x-data
             x-init="$nextTick(() => setTimeout(() => $el.remove(), 7000))"
             style="min-width: 340px;">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 animate-pulse items-center justify-center rounded-2xl bg-red-500/20">
                    <svg class="h-7 w-7 text-red-500"
                         fill="currentColor"
                         viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                              clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="mb-1 text-xl font-bold text-slate-900">Sync Falló</h3>
                    <p class="leading-relaxed text-slate-600">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif


    <div class="grid grid-cols-12 p-2">

        {{-- ? Selector de cuenta --}}
        <div class="hover:shadow-3xl col-span-12 m-2 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
            <div class="min-w-0 flex-1">
                <div class="mb-3 flex items-center justify-between">
                    <span class="bg-gradient-to-r from-gray-900 to-gray-800 bg-clip-text text-xl font-black text-gray-900 text-transparent">Cuenta</span>

                    {{-- BADGE --}}
                    <div class="flex">
                        {{-- En header cuenta seleccionada --}}
                        @if ($selectedAccount?->mt5_login)

                            {{-- 👇 VIGILANTE INVISIBLE: Solo aparece cuando estás sincronizando --}}
                            {{-- Ejecuta 'checkSyncStatus' cada 2 segundos para ver si el Job terminó --}}
                            @if ($isSyncing)
                                <div wire:poll.2s="checkSyncStatus"></div>
                            @endif

                            <button class="@if ($isSyncing) from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 cursor-not-allowed @else from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 @endif mx-2 flex items-center gap-2 rounded-3xl bg-gradient-to-r px-4 py-2 text-sm font-medium text-white shadow-2xl transition-all"
                                    wire:click="syncSelectedAccount"
                                    wire:loading.attr="disabled"
                                    {{-- Deshabilitamos si está cargando por red O si está esperando el Job --}}
                                    {{ $isSyncing ? 'disabled' : '' }}>

                                {{-- Spinner: Se muestra durante la petición de red --}}
                                <svg class="h-4 w-4 animate-spin"
                                     wire:loading
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>

                                {{-- Spinner B: Se muestra cuando ya se envió el Job pero seguimos esperando ($isSyncing true) y ya no hay petición de red --}}
                                @if ($isSyncing)
                                    <svg class="h-4 w-4 animate-spin"
                                         wire:loading.remove
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                @endif

                                {{-- Texto --}}
                                <span>
                                    {{-- Caso 1: Enviando la petición inicial --}}
                                    <span wire:loading>Enviando...</span>

                                    {{-- Caso 2: Petición enviada, esperando al Job --}}
                                    <span wire:loading.remove>
                                        @if ($isSyncing)
                                            ⏳ Sincronizando...
                                        @else
                                            🔄 Sync MT5
                                        @endif
                                    </span>
                                </span>
                            </button>
                        @else
                            <button class="ml-2 rounded-3xl bg-slate-200 px-4 py-2 text-xs text-slate-500 opacity-50"
                                    disabled>
                                ⚙️ Configurar MT5
                            </button>
                        @endif


                        <div class="flex items-center space-x-2 rounded-xl border border-emerald-200 bg-emerald-100/50 px-3 py-1.5 text-xs font-bold text-emerald-800">

                            <div class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></div>
                            <span>{{ $selectedAccount?->status_formatted ?? '---' }} •
                                {{ $selectedAccount?->initial_balance ? '€' . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0€' }}
                            </span>
                        </div>
                    </div>

                </div>

                {{-- SELECT PREMIUM --}}
                <div class="relative">
                    <select class="group/select w-full appearance-none rounded-3xl border-2 border-gray-200 bg-gradient-to-r from-emerald-50/70 via-white to-gray-50 px-4 py-3 pr-14 text-xl font-bold text-gray-900 shadow-xl transition-all duration-300 hover:border-emerald-300 hover:shadow-xl focus:border-emerald-400 focus:shadow-2xl focus:ring-4 focus:ring-emerald-200/50"
                            @change="$wire.changeAccount($event.target.value)"
                            {{-- wire:model.live="selectedAccountId" --}}>
                        @forelse($accounts as $account)
                            <option class="rounded-xl border-b border-gray-100 px-6 py-4 text-lg font-medium italic text-gray-600 hover:text-white"
                                    value="{{ $account->id }}">{{ $account->name }}</option>
                        @empty
                            <option value="">{{ __('labels.without_accounts') }}</option>
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
                    {{ count($accounts) }} {{ __('labels.count_brokers') }} {{ $selectedAccount?->broker ?? __('labels.not_selected_account') }}
                </p>
            </div>
        </div>

        {{-- ? Grafico y timeframe --}}
        <div class="hover:shadow-3xl col-span-12 m-1 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300 xl:col-span-8">

            <div class="col-span-12">
                <!-- Botones Timeframe con Alpine compartido -->
                <div class="mb-6 flex justify-center gap-2 sm:justify-start">

                    <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            {{-- wire:click="setTimeframe('1h')" --}}
                            @click="setTimeframe('1h')"
                            x-bind:class="timeframe === '1h' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="{{ __('labels.last_hour') }}">
                        <span>🕐 1H</span>
                    </button>

                    <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            @click="setTimeframe('24h')"
                            x-bind:class="timeframe === '24h' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="{{ __('labels.last_24_hours') }}">
                        <span>📅 24H</span>
                    </button>

                    <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            @click="setTimeframe('7d')"
                            x-bind:class="timeframe === '7d' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="{{ __('labels.last_week') }}">
                        <span>📊 7D</span>
                    </button>

                    <button class="rounded-2xl border border-slate-200 px-5 py-2 text-sm font-semibold shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
                            @click="setTimeframe('all')"
                            x-bind:class="timeframe === 'all' ? 'bg-emerald-500 text-white shadow-emerald-500/50 !ring-2 !ring-emerald-300/50' : 'bg-white/50 hover:bg-white shadow-slate-200'"
                            title="{{ __('labels.from_start') }}">
                        <span>{{ __('labels.start') }}</span>
                    </button>
                </div>
            </div>

            <canvas class="h-max-20 col-span-12 h-full max-h-96 w-full bg-white p-5 sm:rounded-lg"
                    wire:ignore
                    x-show="!showLoadingGrafic"
                    x-ref="canvas"></canvas>

            <div class="col-span-12 h-96 w-full content-center justify-items-center bg-white p-5 sm:rounded-lg"
                 x-show="showLoadingGrafic">
                <div class="loader flex aspect-square w-8 animate-spin items-center justify-center rounded-full border-t-2 border-gray-500 bg-gray-300 text-yellow-700"></div>
            </div>

        </div>

        {{-- ? Resumen de la cuenta --}}
        <div class="col-span-12 m-1 xl:col-span-4">
            {{-- ? Datos Balance --}}
            <div class="hover:shadow-3xl my-1 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
                <div class="col-span-12 mb-1 font-google">{{ __('labels.summary_account') }}</div>
                <div class="col-span-12 flex items-center justify-between">
                    <div class="flex">
                        <div class="p-2">
                            <i class="fa-solid fa-coins text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-google text-base">{{ __('labels.initial_balance') }}</span>
                            <span class="text-sm text-gray-400">{{ __('labels.size_account') }}</span>
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
                            <span class="font-google text-base">{{ __('labels.current_balance') }}</span>
                            <span class="text-sm text-gray-400">{{ __('labels.current_balance_desc') }}</span>
                        </div>
                    </div>

                    <div>
                        {{ $selectedAccount?->current_balance ? '€' . number_format($selectedAccount->current_balance, $selectedAccount->current_balance == floor($selectedAccount->current_balance) ? 0 : 2) : '0€' }}
                    </div>

                </div>
            </div>

            {{-- ? Informacion Plataforma --}}
            <div class="hover:shadow-3xl my-1 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
                <div class="col-span-12 mb-1 font-google">{{ __('labels.info_platform') }}</div>
                <div class="col-span-12 flex items-center justify-between">
                    <div class="flex">
                        <div class="p-2">
                            <i class="fa-solid fa-laptop text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-google text-base">{{ __('labels.broker') }}</span>
                            <span class="text-sm text-gray-400">{{ __('labels.broker_desc') }}</span>
                        </div>
                    </div>

                    <div>
                        {{ $selectedAccount?->broker ?? '---' }}
                    </div>
                </div>
                <div class="col-span-12 flex items-center justify-between">
                    <div class="flex">
                        <div class="p-2">
                            <i class="fa-regular fa-calendar text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-google text-base">{{ __('labels.first_trade') }}</span>
                            <span class="text-sm text-gray-400">{{ __('labels.first_trade_desc') }}</span>
                        </div>
                    </div>

                    <div>
                        {{ $firstTradeDate ? $firstTradeDate->format('d M Y, H:i') : '---' }}
                    </div>

                </div>
            </div>



        </div>

        <div class="hover:shadow-3xl col-span-12 m-1 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
            <div class="col-span-12 font-google">
                {{ __('labels.statsitics_account') }}
            </div>

            <x-card-statistics class="col-span-3"
                               label="Tasa de Ganancia %"
                               icono="<i class='fa-solid fa-trophy'></i>"
                               key="{{ $winRate }}%" />

            <x-card-statistics class="col-span-3"
                               label="Tiempo Medio de Retención"
                               icono="<i class='fa-solid fa-stopwatch'></i>"
                               key="{{ $avgDurationFormatted }}" />

            <x-card-statistics class="col-span-3"
                               label="Número de Operaciones"
                               icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                               key="{{ $totalTrades }}" />

            <x-card-statistics class="col-span-3"
                               label="Factor de Beneficio"
                               icono="<i class='fa-solid fa-wallet'></i>"
                               key="{{ $profitFactor }} " />


            <x-card-statistics class="col-span-3"
                               label="Ganancia más grande"
                               icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                               key="{{ $maxWin }} €" />

            <x-card-statistics class="col-span-3"
                               label="Ganancia media"
                               icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                               key="{{ $avgWinTrade }} €" />

            <x-card-statistics class="col-span-3"
                               label="Pérdida más grande"
                               icono="<i class='fa-solid fa-arrow-trend-down'></i>"
                               key="{{ $maxLoss }} €" />

            <x-card-statistics class="col-span-3"
                               label="Pérdida media"
                               icono="<i class='fa-solid fa-arrow-trend-down'></i>"
                               key="{{ $avgLossTrade }} €" />

            <x-card-statistics class="col-span-3"
                               label="ARRR"
                               icono="<i class='fa-solid fa-chart-bar'></i>"
                               key="{{ $arr }} " />

            <x-card-statistics class="col-span-3"
                               label="Símbolo más operado"
                               icono="<i class='fa-brands fa-bitcoin'></i>"
                               key="{{ $topAsset }}" />

            <x-card-statistics class="col-span-3"
                               label="Días de Trading"
                               icono="<i class='fa-solid fa-calendar'></i>"
                               key="{{ $tradingDays }} días" />

            <x-card-statistics class="col-span-3"
                               label="Antiguedad de la cuenta"
                               icono="<i class='fa-solid fa-calendar'></i>"
                               key="{{ $accountAgeFormatted }} " />






        </div>


    </div>



</div>
