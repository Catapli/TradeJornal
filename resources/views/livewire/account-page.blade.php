{{-- CONTENEDOR PRINCIPAL: Solo lógica de dashboard --}}
<div class="min-h-screen bg-gray-50 p-6"
     x-data="dashboardLogic()"
     wire:poll.10s="checkSyncStatus">

    {{-- CONTENEDOR PRINCIPAL CON ESTADO ALPINE --}}
    <div x-data="{
        initialLoad: true,
        init() {
            // Cuando Livewire termine de cargar sus scripts y efectos, quitamos el loader
            document.addEventListener('livewire:initialized', () => {
                this.initialLoad = false;
            });
    
            // Fallback de seguridad: por si Livewire ya cargó antes de este script
            setTimeout(() => { this.initialLoad = false }, 200);
        }
    }">

        {{-- 1. LOADER DE CARGA INICIAL (Pantalla completa al refrescar) --}}
        {{-- Se muestra mientras 'initialLoad' sea true. Tiene z-index máximo (z-50) --}}
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Aquí tu componente loader --}}
            <div class="flex flex-col items-center">
                <x-loader />
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400">{{ __('labels.loading_dashboard') }}</span>
            </div>
        </div>
    </div>

    {{-- ? Loading --}}
    <div class="fixed inset-0 z-[9999]"
         wire:loading
         wire:target='updatedSelectedAccountId,openRules, insertAccount, updateAccount, deleteAccount, changeAccount'>
        <x-loader></x-loader>
    </div>

    <x-confirm-modal />

    {{-- Top-Right Snackbar --}}

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

    <x-modals.modal-account show="showModalAccount"
                            labelTitle="labelTitleModal"
                            event="trigger-save-account">

        <div class="space-y-6"
             x-data="accountSelector(@js($propFirmsData))"
             @trigger-save-account.window="checkForm()">

            {{-- 1. NOMBRE DE LA CUENTA --}}
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <i class="fa-solid fa-signature text-gray-600"></i>
                    {{ __('labels.account_name') }}
                    <span class="text-red-500">*</span>
                </label>
                <input class="w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 text-sm transition focus:border-gray-500 focus:bg-white focus:ring-2 focus:ring-gray-200"
                       type="text"
                       x-ref="nameAccount"
                       x-model="nameAccount"
                       placeholder="{{ __('labels.enter_account_name') }}"
                       required>
            </div>

            {{-- 2. CONFIGURACIÓN DE PROPFIRM --}}
            <div class="space-y-4 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5">
                <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
                    <i class="fa-solid fa-building text-gray-600"></i>
                    <h4 class="font-semibold text-gray-900">{{ __('labels.propfirm_configuration') }}</h4>
                </div>

                {{-- PropFirm --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                        <i class="fa-brands fa-sketch text-gray-400"></i>
                        {{ __('labels.prop_firm') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select class="w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                            x-ref="propFirm"
                            x-model="selectedFirmId"
                            required>
                        <option value="">{{ __('labels.select_prop_firm') }}</option>
                        <template x-for="firm in allFirms"
                                  :key="firm.id">
                            <option :value="firm.id"
                                    x-text="firm.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Programa --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                        <i class="fa-solid fa-list text-gray-400"></i>
                        {{ __('labels.program_type') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select class="w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                            x-ref="program"
                            x-model="selectedProgramId"
                            :disabled="!selectedFirmId"
                            required>
                        <option value="">{{ __('labels.select_program') }}</option>
                        <template x-for="program in getPrograms()"
                                  :key="program.id">
                            <option :value="program.id"
                                    x-text="program.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Grid para Tamaño y Divisa --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Tamaño --}}
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                            <i class="fa-solid fa-coins text-gray-400"></i>
                            {{ __('labels.account_size') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <select class="w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                x-ref="size"
                                x-model="selectedSize"
                                :disabled="!selectedProgramId"
                                required>
                            <option value="">{{ __('labels.select_size') }}</option>
                            <template x-for="size in getSizes()"
                                      :key="size">
                                <option :value="size"
                                        x-text="new Intl.NumberFormat().format(size)"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Divisa --}}
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                            <i class="fa-solid fa-dollar-sign text-gray-400"></i>
                            {{ __('labels.currency') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <select class="w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                x-ref="currency"
                                x-model="selectedLevelId"
                                :disabled="!selectedSize"
                                required>
                            <option value="">{{ __('labels.select_currency') }}</option>
                            <template x-for="level in getCurrencies()"
                                      :key="level.id">
                                <option :value="level.id"
                                        x-text="level.currency"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Servidor (Read-only) --}}
                {{-- <div class="space-y-2">
                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                        <i class="fa-solid fa-server text-gray-400"></i>
                        {{ __('labels.server') }}
                    </label>
                    <input class="w-full rounded-lg border-gray-300 bg-gray-100 px-4 py-2.5 text-sm text-gray-500"
                           type="text"
                           x-model="selectedServer"
                           disabled
                           placeholder="{{ __('labels.auto_assigned') }}">
                </div> --}}
            </div>

            {{-- 3. SINCRONIZACIÓN (Colapsable) --}}
            <div class="space-y-4 rounded-xl border border-gray-200 bg-gradient-to-br from-emerald-50 to-white p-5"
                 x-data="{ showSyncOptions: false }">

                {{-- Toggle Header --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100">
                            <i class="fa-solid fa-rotate text-emerald-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ __('labels.auto_sync') }}</h4>
                            <p class="text-xs text-gray-500">{{ __('labels.sync_with_trading_platform') }}</p>
                        </div>
                    </div>

                    {{-- Toggle Switch --}}
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input class="peer sr-only"
                               type="checkbox"
                               x-model="syncronize"
                               @change="showSyncOptions = syncronize">
                        <div
                             class="peer h-6 w-11 rounded-full bg-gray-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-5">
                        </div>
                    </label>
                </div>

                {{-- Sync Options (Collapsed) --}}
                <div class="space-y-4 border-t border-emerald-100 pt-4"
                     x-show="showSyncOptions"
                     x-collapse>

                    {{-- Plataforma --}}
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                            <i class="fa-solid fa-terminal text-gray-400"></i>
                            {{ __('labels.platform') }}
                        </label>
                        <select class="w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                                x-ref="platformBroker"
                                x-model="platformBroker">
                            <option value="">{{ __('labels.select_platform') }}</option>
                            <option value="mt5">MetaTrader 5</option>
                            <option value="cTrader">cTrader</option>
                        </select>
                    </div>

                    {{-- Login ID --}}
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-600">
                            <i class="fa-solid fa-id-card text-gray-400"></i>
                            {{ __('labels.account_id') }}
                        </label>
                        <input class="w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                               type="text"
                               x-ref="loginPlatform"
                               x-model="loginPlatform"
                               placeholder="{{ __('labels.enter_account_id') }}">
                        {{-- <p class="text-xs text-gray-500">
                            <i class="fa-solid fa-info-circle"></i>
                            {{ __('labels.account_id_help') }}
                        </p> --}}
                    </div>
                </div>
            </div>

        </div>

    </x-modals.modal-account>



    {{-- Modal Reglas de Trading Plan --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
         x-show="showRulesModal"
         x-transition.opacity
         x-cloak
         style="display: none;"
         @click.self="closeRulesModal()">

        <div class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">

            {{-- Header --}}
            <div class="border-b border-gray-100 bg-gradient-to-r from-gray-800 to-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                            <i class="fa-solid fa-sliders text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">
                            {{ __('labels.trading_plan_rules') }}
                        </h3>
                    </div>
                    <button class="rounded-lg p-1.5 text-white/80 transition hover:bg-white/10 hover:text-white"
                            type="button"
                            @click="closeRulesModal()">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="space-y-4 p-6">
                {{-- Max Daily Loss --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        {{ __('labels.max_daily_loss_percent') }}
                    </label>
                    <input class="w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                           type="number"
                           step="0.01"
                           x-model="$wire.rules_max_loss_percent"
                           placeholder="Ej: 5.00">
                </div>

                {{-- Daily Profit Target --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        {{ __('labels.daily_profit_target_percent') }}
                    </label>
                    <input class="w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                           type="number"
                           step="0.01"
                           x-model="$wire.rules_profit_target_percent"
                           placeholder="Ej: 2.00">
                </div>

                {{-- Max Daily Trades --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        {{ __('labels.max_daily_trades') }}
                    </label>
                    <input class="w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                           type="number"
                           x-model="$wire.rules_max_trades"
                           placeholder="Ej: 5">
                </div>

                {{-- Trading Hours --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            {{ __('labels.start_time') }}
                        </label>
                        <input class="w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                               type="time"
                               x-model="$wire.rules_start_time">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            {{ __('labels.end_time') }}
                        </label>
                        <input class="w-full rounded-lg border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                               type="time"
                               x-model="$wire.rules_end_time">
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                <button class="flex-1 rounded-xl border border-gray-300 bg-white py-3 font-bold text-gray-700 transition hover:bg-gray-50"
                        type="button"
                        @click="closeRulesModal()">
                    {{ __('labels.cancel') }}
                </button>
                <button class="flex-1 rounded-xl bg-gray-800 py-3 font-bold text-white shadow-lg transition hover:bg-gray-700 focus:ring-4 focus:ring-gray-200"
                        type="button"
                        @click="$wire.saveRules()">
                    {{ __('labels.save') }}
                </button>
            </div>
        </div>
    </div>



    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-gray-800 to-gray-700 shadow-lg">
                    <i class="fa-solid fa-wallet text-xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-gray-900">{{ __('menu.accounts') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('menu.resume_accounts') }}</p>
                </div>
            </div>
        </div>
    </div>




    <div class="grid grid-cols-12 p-2">

        {{-- ? Selector de cuenta --}}
        <div class="col-span-12 mb-4 rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-xl font-black text-gray-900">{{ __('labels.account') }}</span>

                <div class="flex flex-col items-end gap-2">
                    <span class="text-xs text-gray-500">
                        {{ __('labels.last_sync') }}
                        @if ($selectedAccount?->last_sync)
                            {{ Carbon\Carbon::parse($selectedAccount?->last_sync)->format('H:i d/m/Y') }}
                        @else
                            {{ __('labels.never') }}
                        @endif
                    </span>

                    {{-- Badge de Estado --}}
                    <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                        <div class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></div>
                        <span>
                            {{ $selectedAccount?->status_formatted ?? '---' }} •
                            {{ $selectedAccount?->initial_balance ? $currency . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0' . $currency }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- SELECT --}}
            <div class="relative">
                <select class="w-full appearance-none rounded-xl border-2 border-gray-200 bg-gradient-to-r from-white via-gray-50 to-white px-4 py-3 pr-12 text-lg font-bold text-gray-900 shadow-sm transition-all duration-300 hover:border-gray-300 hover:shadow-md focus:border-gray-400 focus:shadow-lg focus:ring-4 focus:ring-gray-100"
                        @change="$wire.changeAccount($event.target.value)">
                    @forelse($accounts as $account)
                        <option value="{{ $account->id }}"
                                {{ $selectedAccount?->id == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}
                        </option>
                    @empty
                        <option value="">{{ __('labels.without_accounts') }}</option>
                    @endforelse
                </select>

                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                    <i class="fa-solid fa-chevron-down text-gray-400"></i>
                </div>
            </div>

            {{-- SUBINFO Y BOTONES --}}
            <div class="mt-4 flex items-center justify-between">
                <p class="flex items-center text-sm text-gray-600">
                    <span class="mr-2 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    {{ count($accounts) }} {{ __('labels.count_brokers') }} • {{ $selectedAccount?->broker_name ?? '---' }} • {{ $selectedAccount?->phase_label ?? '---' }}
                </p>

                <div class="flex items-center gap-2">
                    <button class="flex items-center gap-2 rounded-lg bg-gray-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                            @click="$dispatch('open-modal-create')">
                        <i class="fa-solid fa-plus text-emerald-400"></i>
                        {{ __('labels.create_account') }}
                    </button>

                    @if ($selectedAccount)
                        <button class="flex items-center gap-2 rounded-lg bg-gray-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                                @click="$wire.editAccount({{ $selectedAccount->id }})">
                            <i class="fa-solid fa-pen text-yellow-400"></i>
                            {{ __('labels.edit_account') }}
                        </button>

                        <button class="flex items-center gap-2 rounded-lg bg-gray-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                                @click="confirmDeleteAccount({{ $selectedAccount->id }})">
                            <i class="fa-solid fa-trash text-red-400"></i>
                            {{ __('labels.delete_account') }}
                        </button>

                        <button class="rounded-lg bg-gray-100 p-2 text-gray-600 transition hover:bg-gray-200"
                                wire:click="openRules({{ $selectedAccount->id }})"
                                title="{{ __('labels.configure_rules') }}">
                            <i class="fa-solid fa-sliders"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>


        {{-- ? Grafico y timeframe --}}
        <div class="hover:shadow-3xl col-span-12 mb-2 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300 xl:col-span-8">

            <div class="col-span-12">
                <!-- Botones Timeframe con Alpine compartido -->
                <div class="mb-6 flex justify-center gap-2 sm:justify-start">

                    <button class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-200 hover:shadow-md"
                            @click="setTimeframe('1h')"
                            x-bind:class="timeframe === '1h' ? 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/50 ring-2 ring-emerald-200' : 'bg-white hover:bg-gray-50'">
                        <span>🕐 1H</span>
                    </button>

                    <button class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-200 hover:shadow-md"
                            @click="setTimeframe('24h')"
                            x-bind:class="timeframe === '24h' ? 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/50 ring-2 ring-emerald-200' : 'bg-white hover:bg-gray-50'">
                        <span>📅 24H</span>
                    </button>

                    <button class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-200 hover:shadow-md"
                            @click="setTimeframe('7d')"
                            x-bind:class="timeframe === '7d' ? 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/50 ring-2 ring-emerald-200' : 'bg-white hover:bg-gray-50'">
                        <span>📊 7D</span>
                    </button>

                    <button class="rounded-xl border border-gray-200 px-5 py-2 text-sm font-semibold shadow-sm transition-all duration-200 hover:shadow-md"
                            @click="setTimeframe('all')"
                            x-bind:class="timeframe === 'all' ? 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/50 ring-2 ring-emerald-200' : 'bg-white hover:bg-gray-50'">
                        <span>{{ __('labels.start') }}</span>
                    </button>

                </div>
            </div>

            <div class="col-span-12 flex justify-around">

                <div class="flex flex-col text-center">
                    <span> {{ __('labels.profit_account') }}</span>
                    @if ($totalProfitLoss > 0)
                        <span>{{ number_format($totalProfitLoss, 2) }} {{ $currency }} </span>
                    @else
                        <span>0 {{ $currency }}</span>
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
                 wire:ignore
                 x-show="!showLoadingGrafic">

                <!-- CAMBIO AQUÍ: Usamos un div y cambiamos la referencia a 'chart' -->
                <div class="h-full min-h-[350px] w-full"
                     x-ref="chart">
                </div>
            </div>


            <div class="col-span-12 h-96 w-full content-center justify-items-center bg-white p-5 sm:rounded-lg"
                 x-show="showLoadingGrafic">
                <div class="loader flex aspect-square w-8 animate-spin items-center justify-center rounded-full border-t-2 border-gray-500 bg-gray-300 text-yellow-700"></div>
            </div>
        </div>

        {{-- ? Resumen de la cuenta --}}
        <div class="col-span-12 m-1 xl:col-span-4">

            {{-- ? Datos Balance --}}
            <div class="mb-3 overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 shadow-lg transition-all duration-300 hover:shadow-xl">

                {{-- Header --}}
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-800 to-gray-700 px-5 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                            <i class="fa-solid fa-wallet text-sm text-white"></i>
                        </div>
                        <h3 class="font-bold text-white">{{ __('labels.summary_account') }}</h3>
                    </div>
                </div>

                {{-- Body --}}
                <div class="space-y-4 p-5">

                    {{-- Balance Inicial --}}
                    <div class="flex items-center justify-between rounded-lg bg-gray-50/50 p-3 transition hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100">
                                <i class="fa-solid fa-coins text-lg text-emerald-600"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">{{ __('labels.initial_balance') }}</span>
                                <span class="text-xs text-gray-500">{{ __('labels.size_account') }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-gray-900">
                                {{ $selectedAccount?->initial_balance ? $currency . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0' . $currency }}
                            </span>
                        </div>
                    </div>

                    {{-- Balance Actual --}}
                    <div class="flex items-center justify-between rounded-lg bg-gray-50/50 p-3 transition hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                <i class="fa-solid fa-chart-line text-lg text-blue-600"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">{{ __('labels.current_balance') }}</span>
                                <span class="text-xs text-gray-500">{{ __('labels.current_balance_desc') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-right">
                            {{-- Badge de Porcentaje --}}
                            @if ($profitPercentage == 0)
                                <span class="rounded-lg bg-gray-200 px-2 py-1 text-xs font-bold text-gray-600">
                                    {{ number_format($profitPercentage, 2) }}%
                                </span>
                            @else
                                <span class="@if ($profitPercentage > 0) bg-emerald-100 text-emerald-700 @else bg-rose-100 text-rose-700 @endif flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold">
                                    @if ($profitPercentage >= 0)
                                        <i class="fa-solid fa-arrow-up text-emerald-500"></i>
                                    @else
                                        <i class="fa-solid fa-arrow-down text-rose-500"></i>
                                    @endif
                                    {{ number_format($profitPercentage, 2) }}%
                                </span>
                            @endif

                            {{-- Valor --}}
                            <span class="text-lg font-bold text-gray-900">
                                {{ $selectedAccount?->current_balance ? $currency . number_format($selectedAccount->current_balance, $selectedAccount->current_balance == floor($selectedAccount->current_balance) ? 0 : 2) : '0' . $currency }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ? Información Plataforma --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 shadow-lg transition-all duration-300 hover:shadow-xl">

                {{-- Header --}}
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-800 to-gray-700 px-5 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                            <i class="fa-solid fa-server text-sm text-white"></i>
                        </div>
                        <h3 class="font-bold text-white">{{ __('labels.info_platform') }}</h3>
                    </div>
                </div>

                {{-- Body --}}
                <div class="space-y-4 p-5">

                    {{-- Broker --}}
                    <div class="flex items-center justify-between rounded-lg bg-gray-50/50 p-3 transition hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100">
                                <i class="fa-solid fa-laptop text-lg text-slate-600"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">{{ __('labels.broker') }}</span>
                                <span class="text-xs text-gray-500">{{ __('labels.broker_desc') }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-gray-900">
                                {{ $selectedAccount?->broker_name ?? '---' }}
                            </span>
                        </div>
                    </div>

                    {{-- Primer Trade --}}
                    <div class="flex items-center justify-between rounded-lg bg-gray-50/50 p-3 transition hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100">
                                <i class="fa-regular fa-calendar text-lg text-amber-600"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">{{ __('labels.first_trade') }}</span>
                                <span class="text-xs text-gray-500">{{ __('labels.first_trade_desc') }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-gray-900">
                                {{ $firstTradeDate }}
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </div>


        <div class="col-span-12 m-1 overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 shadow-lg transition-all duration-300 hover:shadow-xl">

            {{-- Header --}}
            <div class="border-b border-gray-100 bg-gradient-to-r from-gray-800 to-gray-700 px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
                        <i class="fa-solid fa-chart-pie text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">{{ __('labels.statsitics_account') }}</h3>
                </div>
            </div>

            {{-- Body - Grid de Estadísticas --}}
            <div class="grid grid-cols-12 gap-4 p-6">

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
                                   key="{{ $profitFactor }}"
                                   align="right"
                                   tooltip="{{ __('labels.tlp_profit_factor') }}" />

                <x-card-statistics class="col-span-6 xl:col-span-3"
                                   label="{{ __('labels.bigger_profit_trade') }}"
                                   icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                                   key="{{ $maxWin }} {{ $currency }}"
                                   tooltip="{{ __('labels.tlp_bigger_profit_trade') }}" />

                <x-card-statistics class="col-span-6 xl:col-span-3"
                                   label="{{ __('labels.avg_profit_trade') }}"
                                   icono="<i class='fa-solid fa-arrow-trend-up'></i>"
                                   key="{{ $avgWinTrade }} {{ $currency }}"
                                   tooltip="{{ __('labels.tlp_avg_profit_trade') }}" />

                <x-card-statistics class="col-span-6 xl:col-span-3"
                                   label="{{ __('labels.losser_trade') }}"
                                   icono="<i class='fa-solid fa-arrow-trend-down'></i>"
                                   key="{{ $maxLoss }} {{ $currency }}"
                                   tooltip="{{ __('labels.tlp_losser_trade') }}" />

                <x-card-statistics class="col-span-6 xl:col-span-3"
                                   align="right"
                                   label="{{ __('labels.avg_losser_trade') }}"
                                   icono="<i class='fa-solid fa-arrow-trend-down'></i>"
                                   key="{{ $avgLossTrade }} {{ $currency }}"
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
                                   icono="<i class='fa-solid fa-hourglass-half'></i>"
                                   key="{{ $accountAgeFormatted }}"
                                   tooltip="{{ __('labels.tlp_age_account') }}" />
            </div>
        </div>


        <div class="col-span-12 m-1 mt-4 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-6 shadow-2xl transition-all duration-300">

            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center">
                    <h3 class="font-google text-lg font-bold text-gray-800">{{ __('labels.phase_objectives') }}</h3>
                    <p class="text-sm text-gray-500">
                        {{ $selectedAccount->program_level->program->name ?? '' }}
                        <span class="mx-1 text-gray-300">|</span>
                        <span class="font-semibold text-blue-600">{{ $selectedAccount->currentObjective->name }}</span>
                    </p>
                </div>

                {{-- Botón opcional para ver reglas --}}
                {{-- <button class="...">Ver Reglas</button> --}}
            </div>

            {{-- GRID DE TARJETAS --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Llamamos al Accessor del Modelo Account --}}
                @foreach ($selectedAccount->objectives_progress as $obj)
                    <x-card-objectives :objective="$obj"
                                       :currency="$currency" />
                @endforeach
            </div>

        </div>

        <div class="col-span-12 m-1 mt-4 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 shadow-2xl transition-all duration-300">

            {{-- CABECERA --}}
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-indigo-100 p-2 text-indigo-600">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('labels.account_historic') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('labels.register_complete') }}</p>
                    </div>
                </div>

                {{-- Spinner de carga --}}
                <div wire:loading
                     wire:target="selectedAccountId, gotoPage, nextPage, previousPage">
                    <i class="fa-solid fa-circle-notch fa-spin text-xl text-indigo-500"></i>
                </div>
            </div>

            {{-- TABLA --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-6 py-4 text-left"
                                scope="col">{{ __('labels.order_active') }}</th>
                            <th class="px-6 py-4 text-left"
                                scope="col">{{ __('labels.time_in_out') }}</th>
                            <th class="px-6 py-4 text-center"
                                scope="col">{{ __('labels.volume') }}</th>
                            <th class="px-6 py-4 text-left"
                                scope="col">{{ __('labels.prices_in_out') }}</th>
                            <th class="px-6 py-4 text-right"
                                scope="col">{{ __('labels.profit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($this->historyTrades as $trade)
                            <tr class="group cursor-pointer transition duration-150 hover:bg-indigo-50/40"
                                wire:key="history-{{ $trade->id }}"
                                @click="$dispatch('open-trade-detail', { tradeId: {{ $trade->id }} })">

                                {{-- COLUMNA 1: BADGE + TICKET + SIMBOLO --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        {{-- Badge Tipo --}}
                                        <div class="flex-shrink-0">
                                            @if (in_array(strtoupper($trade->direction), ['BUY', 'LONG']))
                                                <span class="inline-flex h-8 w-14 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-xs font-black text-emerald-600 shadow-sm">
                                                    {{ __('labels.long') }}
                                                </span>
                                            @else
                                                <span class="inline-flex h-8 w-14 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-xs font-black text-rose-600 shadow-sm">
                                                    {{ __('labels.short') }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Info Ticket --}}
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-900 transition-colors group-hover:text-indigo-600">
                                                #{{ $trade->ticket }}
                                            </span>
                                            <span class="text-xs font-medium text-gray-400">
                                                {{ $trade->tradeAsset->name ?? ($trade->tradeAsset->symbol ?? $trade->symbol) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- COLUMNA 2: TIEMPO (APILADO) --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        {{-- Entrada --}}
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="w-10 font-medium text-gray-400">{{ __('labels.open') }}</span>
                                            <span class="font-mono text-gray-600">
                                                {{ \Carbon\Carbon::parse($trade->entry_time)->format('d M H:i') }}
                                            </span>
                                        </div>
                                        {{-- Salida --}}
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="w-10 font-medium text-gray-400">{{ __('labels.close') }}</span>
                                            <span class="font-mono font-bold text-gray-900">
                                                {{ \Carbon\Carbon::parse($trade->exit_time)->format('d M H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- COLUMNA 3: LOTES --}}
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    <span class="inline-block rounded bg-gray-100 px-2 py-1 font-mono text-xs font-bold text-gray-700">
                                        {{ $trade->size }}
                                    </span>
                                </td>

                                {{-- COLUMNA 4: PRECIOS (APILADO) --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        {{-- Entrada --}}
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="w-10 font-medium text-gray-400">{{ __('labels.in') }}</span>
                                            <span class="font-mono text-gray-600">
                                                {{ number_format($trade->entry_price, 5) }}
                                            </span>
                                        </div>
                                        {{-- Salida --}}
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="w-10 font-medium text-gray-400">{{ __('labels.out') }}</span>
                                            <span class="font-mono font-bold text-gray-900">
                                                {{ number_format($trade->exit_price, 5) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 4. PNL --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right"
                                    x-data>
                                    <span class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-sm font-black"
                                          x-text="$store.viewMode.format({{ $trade->pnl }}, {{ $trade->pnl_percentage ?? 0 }})">

                                        {{-- Fallback visual (lo que se ve antes de que cargue Alpine) --}}
                                        {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                    </span>
                                </td>

                                {{-- COLUMNA 5: PNL --}}
                                {{-- <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-mono text-base font-black">
                                            {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                        </span>
                                        {{~~ Opcional: Mostrar % o pips debajo si lo tuvieras ~~}}
                                    </div>
                                </td> --}}
                            </tr>
                        @empty
                            <tr>
                                <td class="py-12 text-center text-gray-400"
                                    colspan="5">
                                    <div class="flex flex-col items-center">
                                        <div class="mb-3 rounded-full bg-gray-50 p-4">
                                            <i class="fa-solid fa-scroll text-2xl text-gray-300"></i>
                                        </div>
                                        <p>{{ __('labels.not_history') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN CON ESTILO PERSONALIZADO --}}
            <div class="border-t border-gray-100 bg-gray-50 px-6 py-3">
                {{ $this->historyTrades->links('vendor.livewire.tradeforge-pagination', data: ['scrollTo' => false]) }}
            </div>
        </div>



    </div>
