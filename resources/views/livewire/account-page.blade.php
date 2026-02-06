{{-- CONTENEDOR PRINCIPAL: Solo lógica de dashboard --}}
<div class="min-h-screen bg-gray-50 p-6"
     x-data="dashboardLogic()">

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
    <div wire:loading
         wire:target='updatedSelectedAccountId, insertAccount, updateAccount, deleteAccount'>
        <x-loader></x-loader>
    </div>

    {{-- 👇 EL MODAL DE CONFIRMACIÓN (Al final) --}}
    <x-modal-confirmation show="showDeleteModal"
                          title="{{ __('labels.¿delete_account?') }}"
                          text="{{ __('labels.lost_history_account') }}"
                          confirmText="{{ __('labels.confirm_delete') }}"
                          {{-- Escuchamos el evento que dispara el botón del componente --}}
                          @confirm-action="executeDelete()" />


    {{-- Top-Right Snackbar --}}

    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

    <x-modals.modal-account show="showModal"
                            labelTitle="labelTitleModal"
                            event="trigger-save-account">

        <div class="grid w-full grid-cols-12 gap-3 p-4"
             x-data="accountSelector(@js($propFirmsData))"
             @trigger-save-account.window="checkForm()">
            {{-- ? Nombre Cuenta --}}
            <x-input-group id="name"
                           class="col-span-12"
                           x-ref="nameAccount"
                           x-model="nameAccount"
                           placeholder="{{ __('labels.account_name') }}"
                           icono=" <i class='fa-solid fa-signature'></i>"
                           tooltip="{{ __('labels.account_name') }}" />

            {{-- Apartado Cuenta --}}
            {{-- Pasamos los datos SOLO aquí. Así no pesan en el resto de la app --}}
            <fieldset class="hover:shadow-3xl col-span-6 my-1 grid grid-cols-12 rounded-xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">

                <legend class="font-google font-bold">{{ __('labels.prop_firm_data') }}</legend>

                {{-- 1. PROP FIRM --}}
                <x-select-group id="propFirm"
                                class="col-span-12"
                                x-ref="propFirm"
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
                                x-ref="program"
                                x-model="selectedProgramId"
                                x-bind:disabled="!selectedFirmId"
                                tooltip="{{ __('labels.account_type') }}"
                                icono="<i class='fa-solid fa-list'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_program') }}</option>
                        <template x-for="program in getPrograms()"
                                  :key="program.id">
                            <option :value="program.id"
                                    x-text="program.name"></option>
                        </template>
                    </x-slot>
                </x-select-group>

                {{-- 3. BALANCE --}}
                <x-select-group id="size"
                                class="col-span-12"
                                x-ref="size"
                                x-model="selectedSize"
                                x-bind:disabled="!selectedProgramId"
                                tooltip="{{ __('labels.balance_account') }}"
                                icono="<i class='fa-solid fa-coins'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_balance') }}</option>
                        <template x-for="size in getSizes()"
                                  :key="size">
                            <option :value="size"
                                    x-text="new Intl.NumberFormat().format(size)"></option>
                        </template>
                    </x-slot>
                </x-select-group>

                {{-- 4. DIVISA --}}
                <x-select-group id="currency"
                                class="col-span-12"
                                x-ref="currency"
                                x-model="selectedLevelId"
                                x-bind:disabled="!selectedSize"
                                tooltip="{{ __('labels.currency_account') }}"
                                icono="<i class='fa-solid fa-euro-sign'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_currency') }}</option>
                        <template x-for="level in getCurrencies()"
                                  :key="level.id">
                            <option :value="level.id"
                                    x-text="level.currency"></option>
                        </template>
                    </x-slot>
                </x-select-group>
            </fieldset>
            <fieldset class="hover:shadow-3xl col-span-6 my-1 grid grid-cols-12 rounded-xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
                <legend class="font-google font-bold">{{ __('labels.sync_options') }}</legend>
                <div class="col-span-12 grid grid-cols-12">
                    <div class="col-span-6">
                        <span>{{ __('labels.enable_sync') }}</span>
                    </div>
                    <div class="col-span-6">

                        <label class="relative inline-flex cursor-pointer items-center">
                            <input class="peer sr-only"
                                   type="checkbox"
                                   x-model="syncronize"
                                   value="" />
                            <div
                                 class="group peer h-8 w-16 rounded-full bg-white ring-2 ring-red-500 duration-300 after:absolute after:left-1 after:top-1 after:flex after:h-6 after:w-6 after:items-center after:justify-center after:rounded-full after:bg-red-500 after:duration-300 peer-checked:ring-green-500 peer-checked:after:translate-x-8 peer-checked:after:bg-green-500 peer-hover:after:scale-95">
                            </div>
                        </label>
                    </div>
                </div>
                {{-- 5. Plataforma Broker --}}
                <x-select-group id="level"
                                class="col-span-12"
                                x-ref="platformBroker"
                                x-model="platformBroker"
                                tooltip="{{ __('labels.platform_trading') }}"
                                icono="<i class='fa-solid fa-terminal'></i>">
                    <x-slot name="options">
                        <option value="">{{ __('labels.select_platform') }}</option>
                        <option value="mt5">MetaTrader 5</option>
                        <option value="cTrader">cTrader</option>
                    </x-slot>
                </x-select-group>

                <x-input-group id="login_platform"
                               class="col-span-12"
                               x-ref="loginPlatform"
                               x-model="loginPlatform"
                               placeholder="{{ __('labels.login_platform') }}"
                               icono=" <i class='fa-solid fa-user-astronaut'></i>"
                               tooltip="{{ __('labels.login_platform') }}" />

                <x-input-group id="password_platform"
                               class="col-span-12"
                               type="password"
                               x-ref="passwordPlatform"
                               x-model="passwordPlatform"
                               placeholder="{{ __('labels.password_platform') }}"
                               icono=" <i class='fa-solid fa-key'></i>"
                               tooltip="{{ __('labels.password_platform') }}" />

                {{-- 5. Servidor Broker --}}
                <x-input-group id="server"
                               class="col-span-12"
                               x-model="selectedServer"
                               placeholder="{{ __('labels.server_platform') }}"
                               icono=" <i class='fa-solid fa-server'></i>"
                               disabled="true"
                               tooltip="{{ __('labels.server_platform') }}" />
            </fieldset>

        </div>


    </x-modals.modal-account>

    {{-- MODAL DE REGLAS (TRADING PLAN) --}}
    @if ($showRulesModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
             x-transition.opacity>

            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Reglas Operativas</h3>
                        <p class="text-xs text-gray-500">Define los límites para esta estrategia.</p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600"
                            wire:click="closeRulesModal">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="space-y-5 p-6">

                    {{-- 1. Límites Porcentuales --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-rose-500">Límite Pérdida Diario (%)</label>
                            <div class="relative">
                                <input class="w-full rounded-lg border-rose-200 pr-6 text-sm focus:border-rose-500 focus:ring-rose-500"
                                       type="number"
                                       step="0.1"
                                       max="100"
                                       wire:model="rules_max_loss_percent"
                                       placeholder="Ej: 2.5">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-xs text-gray-500">%</span>
                                </div>
                            </div>
                            <p class="mt-1 text-[10px] text-gray-400">Porcentaje del balance.</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-emerald-600">Meta Ganancia Diaria (%)</label>
                            <div class="relative">
                                <input class="w-full rounded-lg border-emerald-200 pr-6 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                       type="number"
                                       step="0.1"
                                       wire:model="rules_profit_target_percent"
                                       placeholder="Ej: 1.0">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-xs text-gray-500">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- 2. Operativa --}}
                    <div>
                        <label class="mb-1 block text-xs font-bold text-gray-500">Máx. Trades Diarios</label>
                        <input class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500"
                               type="number"
                               wire:model="rules_max_trades"
                               placeholder="Ej: 3">
                    </div>

                    {{-- 3. Horario --}}
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <label class="mb-2 block text-xs font-bold uppercase text-gray-500">Horario Permitido</label>
                        <div class="flex items-center gap-2">
                            <input class="w-full rounded-lg border-gray-300 text-sm"
                                   type="time"
                                   wire:model="rules_start_time">
                            <span class="text-gray-400">-</span>
                            <input class="w-full rounded-lg border-gray-300 text-sm"
                                   type="time"
                                   wire:model="rules_end_time">
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <button class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700"
                            wire:click="closeRulesModal">Cancelar</button>
                    <button class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-bold text-white shadow-md hover:bg-indigo-700"
                            wire:click="saveRules">
                        Guardar Reglas
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 p-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-wallet text-2xl text-indigo-600"></i>
                <h1 class="text-3xl font-black text-gray-900">{{ __('menu.accounts') }}</h1>
            </div>
            <p class="text-sm text-gray-500">{{ __('menu.resume_accounts') }}</p>
        </div>
    </div>



    <div class="grid grid-cols-12 p-2">

        {{-- ? Selector de cuenta --}}
        <div class="hover:shadow-3xl col-span-12 mb-2 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300">
            <div class="min-w-0 flex-1">
                <div class="mb-3 flex items-center justify-between">
                    <span class="bg-gradient-to-r from-gray-900 to-gray-800 bg-clip-text text-xl font-black text-gray-900 text-transparent">Cuenta</span>

                    {{-- BADGE --}}


                    <!-- Añadimos flex-col e items-end para que todo se alinee a la derecha -->
                    <div class="flex flex-col items-end">

                        <!-- Forzamos que el span no se rompa y esté alineado a la derecha -->
                        <span class="mb-1 whitespace-nowrap text-xs">
                            {{ __('labels.last_sync') }} @if ($selectedAccount?->last_sync)
                                {{ Carbon\Carbon::parse($selectedAccount?->last_sync)->format('H:i d/m/Y') }}
                            @else
                                {{ __('labels.never') }}
                            @endif
                        </span>
                        {{-- <livewire:sync-token-modal /> --}}
                        <div class="flex items-center"> <!-- Quitamos clases que puedan estorbar y aseguramos flex -->

                            {{-- En header cuenta seleccionada --}}
                            {{--                            @if ($selectedAccount?->mt5_login)

                                @if ($isSyncing)
                                    <div wire:poll.2s="checkSyncStatus"></div>
                                @endif

                                <button class="@if ($isSyncing) from-amber-500 to-amber-600 cursor-not-allowed @else from-emerald-500 to-emerald-600 @endif mx-2 flex items-center gap-2 rounded-3xl bg-gradient-to-r px-4 py-2 text-sm font-medium text-white shadow-2xl transition-all"
                                        wire:click="syncSelectedAccount"
                                        wire:loading.attr="disabled"
                                        {{ $isSyncing ? 'disabled' : '' }}>

                                    {{~~ Spinner (mientras carga red o Job) ~~}}
                                    <svg class="h-4 w-4 animate-spin"
                                         wire:loading
                                         {{~~ También mostramos si isSyncing es true aunque no haya carga de red activa ~~}}
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
                                        <span wire:loading>{{ __('labels.sending') }}</span>
                                        <span wire:loading.remove>
                                            @if ($isSyncing)
                                                {{ __('labels.syncronizing') }}
                                            @else
                                                {{ __('labels.sync') }}
                                            @endif
                                        </span>
                                    </span>
                                </button>
                            @else
                                <button class="ml-2 rounded-3xl bg-slate-200 px-4 py-2 text-xs text-slate-500 opacity-50"
                                        disabled>
                                    {{ __('labels.configure_platform') }}
                                </button>
                            @endif --}}

                            {{-- Badge de Estado --}}
                            <div class="flex items-center space-x-2 rounded-xl border border-emerald-200 bg-emerald-100/50 px-3 py-1.5 text-xs font-bold text-emerald-800">
                                <div class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></div>
                                <span class="whitespace-nowrap">
                                    {{ $selectedAccount?->status_formatted ?? '---' }} •
                                    {{ $selectedAccount?->initial_balance ? $currency . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0' . $currency }}
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
                                    @click="$dispatch('open-modal-create')">
                                <i class="fa-solid fa-plus text-green-500"></i>
                                {{ __('labels.create_account') }}
                            </button>
                            <button class="ring-offset-background focus-visible:ring-ring relative inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md bg-gray-800 px-3 text-sm font-medium text-white transition-colors hover:bg-gray-700 hover:text-yellow-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                                    @click="$wire.editAccount({{ $selectedAccount->id }})">
                                <i class="fa-solid fa-pen-to-square text-yellow-500"></i>
                                {{ __('labels.edit_account') }}
                            </button>
                            @if (count($accounts) > 0)
                                <button class="ring-offset-background focus-visible:ring-ring relative inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md bg-gray-800 px-3 text-sm font-medium text-white transition-colors hover:bg-gray-700 hover:text-red-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                                        @click="confirmDeleteAccount({{ $selectedAccount->id }})">
                                    <i class="fa-solid fa-xmark text-red-500"></i>
                                    {{ __('labels.delete_account') }}
                                </button>

                                {{-- BOTÓN REGLAS (NUEVO) --}}
                                <button class="rounded-md p-1.5 text-gray-400 transition hover:bg-white hover:text-indigo-600 hover:shadow-sm"
                                        wire:click="openRules({{ $selectedAccount->id }})"
                                        title="Configurar Reglas y Objetivos">
                                    <i class="fa-solid fa-sliders"></i>
                                </button>
                            @endif


                        </div>

                    </div>
                </div>



            </div>
        </div>

        {{-- ? Grafico y timeframe --}}
        <div class="hover:shadow-3xl col-span-12 mb-2 grid grid-cols-12 rounded-3xl border border-gray-400 bg-gradient-to-br from-white to-gray-50 p-5 shadow-2xl transition-all duration-300 xl:col-span-8">

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
                        {{ $selectedAccount?->initial_balance ? $currency . number_format($selectedAccount->initial_balance, $selectedAccount->initial_balance == floor($selectedAccount->initial_balance) ? 0 : 2) : '0' . $currency }}
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
                        <span>{{ $selectedAccount?->current_balance ? $currency . number_format($selectedAccount->current_balance, $selectedAccount->current_balance == floor($selectedAccount->current_balance) ? 0 : 2) : '0' . $currency }}</span>
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
                        {{ $selectedAccount?->broker_name ?? '---' }}
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
            <div class="col-span-12 font-google text-lg font-bold text-gray-800">
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
                               icono="<i class='fa-solid fa-calendar'></i>"
                               key="{{ $accountAgeFormatted }} "
                               tooltip="{{ __('labels.tlp_age_account') }}" />
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
