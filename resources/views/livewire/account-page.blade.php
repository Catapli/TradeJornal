{{-- CONTENEDOR PRINCIPAL: Solo lógica de dashboard --}}
<div x-data="dashboardLogic()">

    {{-- ? Loading --}}
    <div wire:loading
         wire:target='updatedSelectedAccountId'>
        <x-loader></x-loader>
    </div>

    {{-- Top-Right Snackbar --}}

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

    <x-modals.modal-account show="showModal"
                            labelTitle="labelTitleModal">

        <div class="grid w-full grid-cols-12 gap-3 p-4">
            {{-- ? Nombre Cuenta --}}
            <x-input-group id="name"
                           class="col-span-12"
                           placeholder="{{ __('labels.account_name') }}"
                           icono=" <i class='fa-solid fa-signature'></i>"
                           tooltip="{{ __('labels.account_name') }}" />

            {{-- Apartado Cuenta --}}
            {{-- Pasamos los datos SOLO aquí. Así no pesan en el resto de la app --}}
            <fieldset class="hover:shadow-3xl col-span-6 my-1 grid grid-cols-12 rounded-xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300"
                      x-data="accountSelector(@js($propFirmsData))">

                <legend class="font-google font-bold">{{ __('labels.prop_firm_data') }}</legend>

                {{-- 1. PROP FIRM --}}
                <x-select-group id="propFirm"
                                class="col-span-12"
                                x-model="selectedFirmId"
                                tooltip="{{ __('labels.prop_firm') }}"
                                icono="<i class='fa-brands fa-sketch'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_prop_firm') }}</option>
                        <template x-for="firm in allFirms"
                                  :key="firm.id">
                            <option :value="firm.id"
                                    x-text="firm.name"></option>
                        </template>
                    </x-slot>
                </x-select-group>

                {{-- 2. PROGRAMA --}}
                <x-select-group id="program"
                                class="col-span-12"
                                x-model="selectedProgramId"
                                x-bind:disabled="!selectedFirmId"
                                tooltip="{{ __('labels.account_type') }}"
                                icono="<i class='fa-solid fa-list'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_program') }}</option>
                        <template x-for="program in programs"
                                  :key="program.id">
                            <option :value="program.id"
                                    x-text="program.name"></option>
                        </template>
                    </x-slot>
                </x-select-group>

                {{-- 3. BALANCE --}}
                <x-select-group id="size"
                                class="col-span-12"
                                x-model="selectedSize"
                                x-bind:disabled="!selectedProgramId"
                                tooltip="{{ __('labels.balance_account') }}"
                                icono="<i class='fa-solid fa-coins'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_balance') }}</option>
                        <template x-for="size in sizes"
                                  :key="size">
                            <option :value="size"
                                    x-text="new Intl.NumberFormat().format(size)"></option>
                        </template>
                    </x-slot>
                </x-select-group>

                {{-- 4. DIVISA --}}
                <x-select-group id="level"
                                class="col-span-12"
                                x-model="selectedLevelId"
                                x-bind:disabled="!selectedSize"
                                tooltip="{{ __('labels.currency_account') }}"
                                icono="<i class='fa-solid fa-euro-sign'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_currency') }}</option>
                        <template x-for="level in currencies"
                                  :key="level.id">
                            <option :value="level.id"
                                    x-text="level.currency"></option>
                        </template>
                    </x-slot>
                </x-select-group>

            </fieldset>
            <fieldset class="hover:shadow-3xl col-span-6 my-1 grid grid-cols-12 rounded-xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
                <legend class="font-google font-bold">{{ __('labels.sync_options') }}</legend>
            </fieldset>

        </div>

    </x-modals.modal-account>


    <div class="grid grid-cols-12 p-2">

        {{-- ? Selector de cuenta --}}
        <div class="hover:shadow-3xl col-span-12 m-2 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
            <div class="min-w-0 flex-1">
                <div class="mb-3 flex items-center justify-between">
                    <span class="bg-gradient-to-r from-gray-900 to-gray-800 bg-clip-text text-xl font-black text-gray-900 text-transparent">Cuenta</span>

                    {{-- BADGE --}}


                    <!-- Añadimos flex-col e items-end para que todo se alinee a la derecha -->
                    <div class="flex flex-col items-end">

                        <!-- Forzamos que el span no se rompa y esté alineado a la derecha -->
                        <span class="mb-1 whitespace-nowrap text-xs">
                            Ultima Sincronizacion @if ($selectedAccount->last_sync)
                                {{ Carbon\Carbon::parse($selectedAccount->last_sync)->format('H:i d/m/Y') }}
                            @else
                                Nunca
                            @endif
                        </span>

                        <div class="flex items-center"> <!-- Quitamos clases que puedan estorbar y aseguramos flex -->

                            {{-- En header cuenta seleccionada --}}
                            @if ($selectedAccount?->mt5_login)

                                @if ($isSyncing)
                                    <div wire:poll.2s="checkSyncStatus"></div>
                                @endif

                                <button class="@if ($isSyncing) from-amber-500 to-amber-600 cursor-not-allowed @else from-emerald-500 to-emerald-600 @endif mx-2 flex items-center gap-2 rounded-3xl bg-gradient-to-r px-4 py-2 text-sm font-medium text-white shadow-2xl transition-all"
                                        wire:click="syncSelectedAccount"
                                        wire:loading.attr="disabled"
                                        {{ $isSyncing ? 'disabled' : '' }}>

                                    {{-- Spinner (mientras carga red o Job) --}}
                                    <svg class="h-4 w-4 animate-spin"
                                         wire:loading
                                         {{-- También mostramos si isSyncing es true aunque no haya carga de red activa --}}
                                         @if ($isSyncing) style="display: block" @endif
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>

                                    <span>
                                        <span wire:loading>Enviando...</span>
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

                            {{-- Badge de Estado --}}
                            <div class="flex items-center space-x-2 rounded-xl border border-emerald-200 bg-emerald-100/50 px-3 py-1.5 text-xs font-bold text-emerald-800">
                                <div class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></div>
                                <span class="whitespace-nowrap">
                                    {{ $selectedAccount?->status_formatted ?? '---' }} •
                                    {{ $selectedAccount?->initial_balance ? '€' . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0€' }}
                                </span>
                            </div>
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
                <div class="flex items-center justify-between text-center">
                    {{-- SUBINFO --}}
                    <p class="mt-3 flex items-center text-sm font-medium text-gray-600">
                        <span class="mr-2 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                        {{ count($accounts) }} {{ __('labels.count_brokers') }} {{ $selectedAccount?->broker_name ?? __('labels.not_selected_account') }} • {{ $selectedAccount?->phase_label ?? '---' }}
                    </p>

                    <div>
                        <div class="flex items-center gap-2 rounded-lg p-1">

                            <button class="ring-offset-background focus-visible:ring-ring relative inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md bg-gray-800 px-3 text-sm font-medium text-white transition-colors hover:bg-gray-700 hover:text-green-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                                    @click="showOpenModalCreate()">
                                <i class="fa-solid fa-plus text-green-500"></i>
                                {{ __('labels.create_account') }}
                            </button>
                            <button
                                    class="ring-offset-background focus-visible:ring-ring relative inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md bg-gray-800 px-3 text-sm font-medium text-white transition-colors hover:bg-gray-700 hover:text-yellow-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                                <i class="fa-solid fa-pen-to-square text-yellow-500"></i>
                                {{ __('labels.edit_account') }}
                            </button>
                            @if (count($accounts) > 0)
                                <button
                                        class="ring-offset-background focus-visible:ring-ring relative inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md bg-gray-800 px-3 text-sm font-medium text-white transition-colors hover:bg-gray-700 hover:text-red-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                                    <i class="fa-solid fa-xmark text-red-500"></i>
                                    {{ __('labels.delete_account') }}
                                </button>
                            @endif
                        </div>

                    </div>
                </div>



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

            <div class="col-span-12 flex justify-around">

                <div class="flex flex-col text-center">
                    <span> {{ __('labels.profit_account') }}</span>
                    @if ($totalProfitLoss > 0)
                        <span>{{ number_format($totalProfitLoss, 2) }} € </span>
                    @else
                        <span>0€</span>
                    @endif
                </div>
                <div class="flex flex-col text-center">
                    <span> {{ __('labels.profit_percentage') }}</span>
                    @if ($profitPercentage > 0)
                        <span>{{ number_format($profitPercentage, 2) }} % </span>
                    @else
                        <span>0%</span>
                    @endif
                </div>
            </div>

            <div class="col-span-12 h-auto max-h-96 min-h-96 w-full bg-white p-5 sm:rounded-lg"
                 wire:ignore>
                <canvas x-show="!showLoadingGrafic"
                        x-ref="canvas"></canvas>
            </div>


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
                        @if ($profitPercentage == 0)
                            <span class="m-1 rounded-3xl bg-gray-300 p-1 pl-2 text-sm font-bold">
                                {{ number_format($profitPercentage, 2) }}%
                            </span>
                        @else
                            <span class="@if ($profitPercentage > 0) bg-[#4cd34079] text-green-700   @else bg-[#ff00003d] text-red-700 @endif mr-1 rounded-3xl p-1 text-sm font-bold">
                                @if ($profitPercentage >= 0)
                                    <i class="fa-solid fa-arrow-up text-green-500"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down text-red-500"></i>
                                @endif
                                {{ number_format($profitPercentage, 2) }}%
                            </span>
                        @endif
                        <span>{{ $selectedAccount?->current_balance ? '€' . number_format($selectedAccount->current_balance, $selectedAccount->current_balance == floor($selectedAccount->current_balance) ? 0 : 2) : '0€' }}</span>
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

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.profit_rate') }}"
                               icono="<i class='fa-solid fa-trophy'></i>"
                               key="{{ $winRate }}%"
                               tooltip="{{ __('labels.tlp_winrate') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.avg_retention_time') }}"
                               icono="<i class='fa-solid fa-stopwatch'></i>"
                               key="{{ $avgDurationFormatted }}"
                               tooltip="{{ __('labels.tlp_avg_retention_time') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.number_trades') }}"
                               icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                               key="{{ $totalTrades }}"
                               tooltip="{{ __('labels.tlp_number_trades') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.profit_factor') }}"
                               icono="<i class='fa-solid fa-wallet'></i>"
                               key="{{ $profitFactor }} "
                               align="right"
                               tooltip="{{ __('labels.tlp_profit_factor') }}" />


            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.bigger_profit_trade') }}"
                               icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                               key="{{ $maxWin }} €"
                               tooltip="{{ __('labels.tlp_bigger_profit_trade') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.avg_profit_trade') }}"
                               icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                               key="{{ $avgWinTrade }} €"
                               tooltip="{{ __('labels.tlp_avg_profit_trade') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.losser_trade') }}"
                               icono="<i class='fa-solid fa-arrow-trend-down'></i>"
                               key="{{ $maxLoss }} €"
                               tooltip="{{ __('labels.tlp_losser_trade') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               align="right"
                               label="{{ __('labels.avg_losser_trade') }}"
                               icono="<i class='fa-solid fa-arrow-trend-down'></i>"
                               key="{{ $avgLossTrade }} €"
                               tooltip="{{ __('labels.tlp_avg_losser_trade') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.arr') }}"
                               icono="<i class='fa-solid fa-chart-bar'></i>"
                               key="{{ $arr }}"
                               tooltip="{{ __('labels.tlp_arr') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.asset_most_traded') }}"
                               icono="<i class='fa-brands fa-bitcoin'></i>"
                               key="{{ $topAsset }}"
                               tooltip="{{ __('labels.tlp_asset_most_traded') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               label="{{ __('labels.days_trading') }}"
                               icono="<i class='fa-solid fa-calendar'></i>"
                               key="{{ $tradingDays }} {{ __('labels.days') }}"
                               tooltip="{{ __('labels.tlp_days_trading') }}" />

            <x-card-statistics class="col-span-6 xl:col-span-3"
                               align="right"
                               label="{{ __('labels.age_account') }}"
                               icono="<i class='fa-solid fa-calendar'></i>"
                               key="{{ $accountAgeFormatted }} "
                               tooltip="{{ __('labels.tlp_age_account') }}" />






        </div>


    </div>



</div>
