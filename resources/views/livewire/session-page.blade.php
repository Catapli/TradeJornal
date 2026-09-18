{{-- Vive dentro de app-layout con navbar fijo (h-16): restamos esa altura para ocupar
     el viewport exacto y evitar el scroll global de la página. --}}
<div class="h-[calc(100vh-4rem)] w-full overflow-hidden bg-gray-50 font-sans text-gray-900 dark:bg-gray-900 dark:text-gray-100"
     x-data="sessionPage(@js($accounts), @js($strategies), @js($restoredSessionData), @js($moodConfig))"
     x-on:resize.window="width = window.innerWidth">


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
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-white dark:bg-gray-900"
             x-show="initialLoad"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Aquí tu componente loader --}}
            <div class="flex flex-col items-center">
                <x-loader />
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400 dark:text-gray-500">{{ __('labels.loading_dashboard') }}</span>
            </div>
        </div>
    </div>

    {{-- ? Loading --}}
    <div wire:loading
         wire:target=''>
        <x-loader></x-loader>
    </div>

    {{-- ========================================= --}}
    {{-- STEP 1: SETUP (CONFIGURACIÓN)             --}}
    {{-- ========================================= --}}
    <div class="flex h-full flex-col items-center justify-center overflow-y-auto p-6"
         x-show="step === 1"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="w-full max-w-lg space-y-8">
            <div class="text-center">
                <h1 class="flex items-center justify-center gap-3 text-4xl font-black tracking-tight text-gray-900 dark:text-gray-100">
                    <i class="fa-solid fa-bolt text-indigo-600 dark:text-indigo-400"></i> {{ __('labels.focus_mode') }}
                </h1>
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('labels.configure_session_delete_noise') }}</p>

                {{-- El histórico es el pasado de esta misma pantalla, así que se
                     entra desde aquí en vez de desde un icono aparte. --}}
                <div class="mt-4">
                    <x-page-link :href="route('session-history')"
                                 icon="fa-solid fa-note-sticky"
                                 :label="__('menu.go_session_history')" />
                </div>
            </div>

            <div class="space-y-6 rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-200/50 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
                <!-- Selector de Cuenta -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('labels.trading_account') }}
                    </label>
                    <select class="w-full rounded-lg border border-gray-300 px-4 py-3 transition focus:border-transparent focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            x-model="selectedAccountId">
                        <template x-for="acc in accounts"
                                  :key="acc.id">
                            <option :value="acc.id"
                                    x-text="acc.name + ' (' + acc.currency + ' ' + acc.balance.toFixed(2) + ')'"></option>
                        </template>
                    </select>

                    {{-- ✅ Preview de límites (100% Alpine) --}}
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm"
                         x-show="currentAccount.limits">
                        <div class="rounded bg-rose-50 p-2 dark:bg-rose-500/10">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('labels.max_loss') }}</span>
                            <span class="font-semibold text-rose-600 dark:text-rose-400"
                                  x-text="currentAccount.limits?.max_loss_pct + '%'"></span>
                        </div>
                        <div class="rounded bg-emerald-50 p-2 dark:bg-emerald-500/10">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('labels.target') }}</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400"
                                  x-text="currentAccount.limits?.target_pct + '%'"></span>
                        </div>
                    </div>
                </div>

                <!-- Selector de Estrategia -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('labels.strategy') }}
                    </label>
                    {{-- Con estrategias: selector + preview de reglas --}}
                    <template x-if="strategies.length > 0">
                        <div>
                            <select class="w-full rounded-lg border border-gray-300 px-4 py-3 transition focus:border-transparent focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    x-model="selectedStrategyId">
                                <template x-for="strat in strategies"
                                          :key="strat.id">
                                    <option :value="strat.id"
                                            x-text="strat.name"></option>
                                </template>
                            </select>

                            {{-- ✅ Preview de reglas (100% Alpine) --}}
                            <div class="mt-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50"
                                 x-show="currentStrategy.rules?.length > 0">
                                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('labels.strategy_rules') }}</p>
                                <ul class="space-y-1">
                                    <template x-for="(rule, index) in currentStrategy.rules"
                                              :key="index">
                                        <li class="flex items-start text-xs text-gray-700 dark:text-gray-300">
                                            <svg class="mr-1 mt-0.5 h-3 w-3 flex-shrink-0 text-indigo-500"
                                                 fill="currentColor"
                                                 viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                      clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="rule"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </template>

                    {{-- Sin estrategias: estado vacío con CTA al playbook --}}
                    <template x-if="strategies.length === 0">
                        <div class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-5 text-center dark:border-gray-600 dark:bg-gray-700/40">
                            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-500/20">
                                <i class="fa-solid fa-chess-knight text-indigo-400"></i>
                            </div>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ __('labels.without_strategies') }}</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-gray-400 dark:text-gray-500">
                                {{ __('labels.create_strategy_hint') }}
                            </p>
                            <a class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-indigo-700"
                               href="{{ route('playbook') }}">
                                <i class="fa-solid fa-plus text-[9px]"></i>
                                {{ __('labels.create_strategy') }}
                            </a>
                        </div>
                    </template>
                </div>

                <!-- Estado Emocional Inicial -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('labels.how_do_you_feel') }}
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="mood in sessionMoodOptions"
                                  :key="mood.key">
                            <button class="rounded-lg border-2 px-4 py-3 font-medium capitalize transition"
                                    type="button"
                                    @click="startMood = mood.key"
                                    :class="{
                                        'bg-indigo-100 border-indigo-500 text-indigo-700 dark:bg-indigo-500/20 dark:border-indigo-500 dark:text-indigo-300': startMood === mood.key,
                                        'bg-white border-gray-300 text-gray-700 hover:border-indigo-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:border-indigo-400': startMood !== mood.key
                                    }"
                                    x-text="mood.label"></button>
                        </template>
                    </div>
                </div>

                <!-- Botón Iniciar Sesión -->
                <button class="w-full rounded-lg py-4 font-semibold text-white shadow-lg transition"
                        @click="initSession()"
                        :disabled="!selectedAccountId || !selectedStrategyId"
                        :class="{
                            'bg-indigo-600 hover:bg-indigo-700 cursor-pointer': selectedAccountId && selectedStrategyId,
                            'bg-gray-300 cursor-not-allowed dark:bg-gray-600': !selectedAccountId || !selectedStrategyId
                        }">
                    <span x-show="strategies.length === 0">{{ __('labels.need_strategy_to_start') }}</span>
                    <span x-show="strategies.length > 0 && (!selectedAccountId || !selectedStrategyId)">{{ __('labels.select_account_strategy') }}</span>
                    <span x-show="selectedAccountId && selectedStrategyId">{{ __('labels.login') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================= --}}
    {{-- STEP 2: DASHBOARD (SESIÓN ACTIVA)         --}}
    {{-- ========================================= --}}
    <div class="flex h-full flex-col bg-gray-50 dark:bg-gray-900"
         x-show="step === 2"
         x-cloak>

        {{-- 1. HEADER --}}
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm lg:px-6 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="hidden text-xs font-bold uppercase tracking-wide text-gray-500 sm:block dark:text-gray-400">{{ __('labels.live_session') }}</span>
                </div>
                <div class="hidden h-4 w-px bg-gray-300 sm:block dark:bg-gray-600"></div>
                <span class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-0.5 text-xs font-bold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300"
                      x-text="currentAccount.name"></span>
            </div>

            <div class="font-mono text-2xl font-black tracking-widest text-gray-800 dark:text-gray-100"
                 x-text="timer"></div>

            {{-- Botón GHOST MODE --}}
            <button class="group mr-2 flex h-8 w-8 items-center justify-center rounded-lg border transition-all"
                    @click="ghostMode = !ghostMode"
                    :class="ghostMode ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-400 dark:text-gray-500 hover:border-indigo-300 hover:text-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:hover:border-indigo-400 dark:hover:text-indigo-400'"
                    title="{{ __('labels.blind_mode') }}">
                <i class="fa-solid"
                   :class="ghostMode ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>

            {{-- Añadir en el header de la sesión activa --}}
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                {{-- Indicador de sync --}}
                <div class="flex items-center gap-1"
                     x-show="isSyncing">
                    <svg class="h-3 w-3 animate-spin text-indigo-500"
                         xmlns="http://www.w3.org/2000/svg"
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
                    <span>{{ __('labels.syncronizing') }}</span>
                </div>

                {{-- Última sincronización --}}
                <div class="flex items-center gap-1"
                     x-show="lastSyncTime && !isSyncing">
                    <i class="fa-solid fa-check text-emerald-500"></i>
                    <span x-text="'{{ __('labels.sync_s') }} ' + (lastSyncTime ? lastSyncTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : '-')"></span>
                </div>
            </div>


            <button class="group flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-600 shadow-sm transition-all hover:border-rose-300 hover:bg-rose-50 dark:border-rose-500/30 dark:bg-gray-700 dark:text-rose-400 dark:hover:border-rose-500/50 dark:hover:bg-rose-500/10"
                    @click="step = 3">
                <i class="fa-solid fa-power-off transition-transform duration-300"></i>
                <span class="hidden sm:inline">{{ __('labels.finalize') }}</span>
            </button>
        </div>

        {{-- 2. KPI BAR --}}
        <div class="z-30 flex h-24 shrink-0 border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <!-- PnL -->
            <div class="relative flex w-1/3 flex-col items-center justify-center overflow-hidden border-r border-gray-100 dark:border-gray-700">
                <div class="z-10 mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ __('labels.pnl_session') }}</div>

                <div class="z-10 flex items-baseline gap-1">
                    {{-- APLICAMOS EL FILTRO AQUÍ --}}
                    <span class="text-4xl font-black tracking-tighter transition-all duration-300 lg:text-5xl"
                          :class="[statusColor.text, ghostMode ? 'blur-md opacity-50 select-none' : '']"
                          x-text="(metrics.pnl > 0 ? '+' : '') + metrics.pnl"></span>

                    {{-- Ocultamos el símbolo de moneda también --}}
                    <span class="text-2xl font-bold text-gray-300 transition-all duration-300 dark:text-gray-600"
                          :class="ghostMode ? 'blur-md opacity-50' : ''">$</span>
                </div>

                {{-- El porcentaje SIEMPRE queda visible (es dato técnico) --}}
                <div class="z-10 mt-1">
                    <span class="rounded px-2 py-0.5 text-xs font-bold transition-colors"
                          :class="statusColor.bg">
                        <span x-text="(metrics.pnl_percent > 0 ? '+' : '') + metrics.pnl_percent + '%'"></span>
                        <span class="ml-1"
                              x-show="isLimitBreached"><i class="fa-solid fa-circle-exclamation"></i></span>
                    </span>
                </div>
            </div>


            <!-- Trades -->
            <div class="flex w-1/3 flex-col items-center justify-center border-r border-gray-100 bg-gray-50/30 dark:border-gray-700 dark:bg-gray-800/30">
                <div class="mb-1 text-[9px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.trades') }}</div>
                <div class="text-3xl font-black text-gray-800 dark:text-gray-100"
                     x-text="metrics.count"></div>
            </div>

            <!-- Winrate -->
            <div class="flex w-1/3 flex-col items-center justify-center bg-gray-50/30 dark:bg-gray-800/30">
                <div class="mb-1 text-[9px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.winrate') }}</div>
                <div class="text-3xl font-black"
                     :class="metrics.winrate >= 50 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'"
                     x-text="metrics.winrate + '%'"></div>
            </div>
        </div>

        {{-- 3. TABS MÓVIL (Solo visible en < lg) --}}
        <div class="flex border-b border-gray-200 bg-white lg:hidden dark:border-gray-700 dark:bg-gray-800">
            <template x-for="tab in ['checklist', 'journal', 'trades']">
                <button class="flex-1 py-3 text-xs font-bold uppercase tracking-wide transition-all"
                        :class="mobileTab === tab ? 'text-indigo-600 border-b-2 border-indigo-600 bg-indigo-50/50 dark:text-indigo-400 dark:bg-indigo-500/10' : 'text-gray-500 dark:text-gray-400'"
                        @click="mobileTab = tab"
                        x-text="tab"></button>
            </template>
        </div>

        {{-- 4. CONTENIDO PRINCIPAL (FLEXBOX LAYOUT) --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- COL 1: PLAN & CHECKLIST (30%) --}}
            <div class="flex w-full flex-col border-r border-gray-200 bg-white lg:w-[30%] dark:border-gray-700 dark:bg-gray-800"
                 x-show="mobileTab === 'checklist' || width >= 1024">

                {{-- A) PLAN DE TRADING (Reglas Duras) --}}
                <template x-if="currentAccount.limits">
                    <div class="border-b border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="flex items-center gap-2 text-xs font-bold uppercase text-gray-700 dark:text-gray-300">
                                <i class="fa-solid fa-scale-balanced text-indigo-600 dark:text-indigo-400"></i> {{ __('labels.operative_plan') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">

                            {{-- 1. Max Daily Loss (Riesgo) --}}
                            <div class="col-span-1 rounded-lg border bg-white p-2 text-center shadow-sm transition-colors dark:bg-gray-700"
                                 x-show="currentAccount.limits?.max_loss_pct"
                                 :class="isLimitBreached ? 'border-rose-200 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : (metrics.pnl_percent <= -(currentAccount.limits.max_loss_pct * 0.8) ? 'border-orange-200 bg-orange-50 dark:border-orange-500/40 dark:bg-orange-500/10' : 'border-gray-200 dark:border-gray-600')">
                                <span class="block text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.max_loss') }}</span>
                                <div class="font-mono text-sm font-bold"
                                     :class="isLimitBreached ? 'text-rose-600 dark:text-rose-400' : 'text-gray-800 dark:text-gray-100'">
                                    <span x-text="'-' + currentAccount.limits.max_loss_pct + '%'"></span>
                                </div>
                                {{-- Barra de progreso inversa (Visualización del peligro) --}}
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-600">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="isLimitBreached ? 'bg-rose-500' : 'bg-rose-400'"
                                         :style="'width: ' + Math.min(Math.abs(metrics.pnl_percent / currentAccount.limits.max_loss_pct) * 100, 100) + '%'"></div>
                                </div>
                            </div>

                            {{-- 2. Profit Target (Objetivo) --}}
                            <div class="col-span-1 rounded-lg border bg-white p-2 text-center shadow-sm transition-colors dark:bg-gray-700"
                                 x-show="currentAccount.limits?.target_pct"
                                 :class="metrics.pnl_percent >= currentAccount.limits.target_pct ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10' : 'border-gray-200 dark:border-gray-600'">
                                <span class="block text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.target') }}</span>
                                <div class="font-mono text-sm font-bold"
                                     :class="metrics.pnl_percent >= currentAccount.limits.target_pct ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-800 dark:text-gray-100'">
                                    <span x-text="'+' + currentAccount.limits.target_pct + '%'"></span>
                                </div>
                                {{-- Barra de progreso objetivo --}}
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-600">
                                    <div class="h-full rounded-full bg-emerald-400 transition-all duration-500"
                                         :style="'width: ' + Math.min(Math.max(metrics.pnl_percent / currentAccount.limits.target_pct, 0) * 100, 100) + '%'"></div>
                                </div>
                            </div>

                            {{-- 3. Regla de Trades (AMMO TRACKER + OVERTRADING LOCK) --}}
                            <div class="relative col-span-1 mt-2 overflow-hidden rounded-lg border bg-white p-3 shadow-sm transition-all duration-500 dark:bg-gray-700"
                                 :class="isOvertrading ? 'border-rose-500 ring-2 ring-rose-500 ring-offset-2 dark:ring-offset-gray-800' : (isMaxTradesReached ? 'border-orange-300 bg-orange-50 dark:border-orange-500/40 dark:bg-orange-500/10' : 'border-gray-200 dark:border-gray-600')">

                                {{-- Header --}}
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase"
                                          :class="isOvertrading ? 'text-rose-600 animate-pulse dark:text-rose-400' : 'text-gray-400'">
                                        <i class="fa-solid fa-ban mr-1"
                                           x-show="isOvertrading"></i>
                                        <span x-text="isOvertrading ? @js(__('labels.overtrading_detected')) : @js(__('labels.ammo_daily'))"></span>
                                    </span>
                                    <div class="font-mono text-xs font-bold"
                                         :class="isOvertrading ? 'text-rose-600 dark:text-rose-400' : 'text-gray-800 dark:text-gray-100'">
                                        <span x-text="Math.max(metrics.count, manualTradeCount)"></span>
                                        <span class="text-gray-400 dark:text-gray-500"
                                              x-show="currentAccount.limits?.max_trades">
                                            / <span x-text="currentAccount.limits?.max_trades"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- ✅ BALAS DENTRO DEL LÍMITE (Clicables) --}}
                                <div class="flex flex-wrap justify-center gap-2 transition-opacity duration-300"
                                     :class="isOvertrading ? 'opacity-25 blur-[1px]' : 'opacity-100'">
                                    <template x-for="i in (currentAccount.limits?.max_trades || 5)"
                                              :key="i">
                                        <button class="flex h-6 w-6 items-center justify-center rounded-full border-2 transition-all hover:scale-110 focus:outline-none active:scale-95"
                                                @click="toggleManualTrade(i)"
                                                type="button"
                                                :class="{
                                                    'bg-indigo-600 border-indigo-600': i <= Math.max(metrics.count, manualTradeCount),
                                                    'border-gray-300 hover:border-indigo-400 dark:border-gray-500 dark:hover:border-indigo-400': i > Math.max(metrics.count, manualTradeCount)
                                                }"
                                                :title="'Trade #' + i">
                                            <i class="fa-solid fa-check text-[10px] text-white"
                                               x-show="i <= Math.max(metrics.count, manualTradeCount)"></i>
                                        </button>
                                    </template>
                                </div>

                                {{-- ✅ BALAS ROJAS (Overtrading detectado por sync - NO clicables) --}}
                                <div class="mt-2 flex flex-wrap justify-center gap-2"
                                     x-show="metrics.count > (currentAccount.limits?.max_trades || 5)">
                                    <template x-for="j in (metrics.count - (currentAccount.limits?.max_trades || 5))"
                                              :key="'over-' + j">
                                        <div class="flex h-6 w-6 animate-pulse items-center justify-center rounded-full border-2 border-rose-500 bg-rose-500 shadow-lg">
                                            <i class="fa-solid fa-exclamation text-[10px] font-bold text-white"></i>
                                        </div>
                                    </template>
                                </div>



                                {{-- OVERLAY DE BLOQUEO (Mensaje Crítico) --}}
                                <div class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm transition-all dark:bg-gray-800/80"
                                     x-show="isOvertrading"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 scale-90"
                                     x-transition:enter-end="opacity-100 scale-100">
                                    <div class="rounded-lg bg-rose-100 px-3 py-2 text-center shadow-sm dark:bg-rose-500/20">
                                        <div class="text-xs font-black text-rose-700 dark:text-rose-300">{{ __('labels.breaked_plan') }}</div>
                                        <div class="text-[9px] font-medium text-rose-600 dark:text-rose-400">{{ __('labels.limit_reached_ammo') }}</div>
                                    </div>
                                </div>
                            </div>



                            {{-- 4. Regla de Horario --}}
                            <div class="col-span-1 rounded-lg border bg-white p-2 text-center shadow-sm transition-colors dark:bg-gray-700"
                                 x-show="currentAccount.limits?.start_time"
                                 :class="!isTimeValid ? 'border-rose-200 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-gray-200 dark:border-gray-600'">
                                <span class="block text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('labels.schedule') }}</span>
                                <div class="font-mono text-xs font-bold text-gray-800 dark:text-gray-100">
                                    <span x-text="currentAccount.limits?.start_time"></span> -
                                    <span x-text="currentAccount.limits?.end_time"></span>
                                </div>
                                <div class="mt-0.5 text-[9px] font-bold uppercase"
                                     :class="isTimeValid ? 'text-emerald-500' : 'text-rose-500'">
                                    <span x-text="isTimeValid ? @js(__('labels.market_open')) : @js(__('labels.closed'))"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                {{-- Estado vacío si no hay plan de trading --}}

                <template x-if="!currentAccount.limits">
                    <div class="border-b border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-5 text-center dark:border-gray-600 dark:bg-gray-700">

                            {{-- Icono --}}
                            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-500/20">
                                <i class="fa-solid fa-scale-balanced text-indigo-400"></i>
                            </div>

                            {{-- Texto --}}
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ __('labels.without_trading_plan') }}</p>
                            <p class="mt-1 text-[10px] leading-relaxed text-gray-400 dark:text-gray-500">
                                {{ __('labels.configure_trading_plan') }}
                            </p>

                            {{-- CTA --}}
                            <a class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-[10px] font-bold text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-500/20 dark:text-indigo-300 dark:hover:bg-indigo-500/30"
                               href="{{ route('cuentas') }}"
                               target="_blank">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                {{ __('labels.configure_plan') }}
                            </a>
                        </div>
                    </div>
                </template>

                {{-- B) CHECKLIST ESTRATEGIA (Reglas de Entrada) --}}
                <div class="flex h-10 shrink-0 items-center justify-between border-b border-gray-100 bg-white px-4 dark:border-gray-700 dark:bg-gray-800">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase text-gray-700 dark:text-gray-300">
                        <i class="fa-solid fa-list-check text-indigo-600 dark:text-indigo-400"></i> {{ __('labels.setup') }}
                    </span>
                    <span class="rounded border border-gray-200 bg-white px-2 py-0.5 text-[10px] font-bold text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <span x-text="activeRules.filter(r => r.checked).length"></span>/<span x-text="activeRules.length"></span>
                    </span>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto p-4 [&::-webkit-scrollbar]:hidden">
                    <template x-for="(rule, idx) in activeRules"
                              :key="idx">
                        <label class="group flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"
                               :class="rule.checked ? 'bg-indigo-50 border-indigo-200 dark:bg-indigo-500/10 dark:border-indigo-500/30' : 'bg-white border-gray-200 dark:bg-gray-700 dark:border-gray-600'">
                            <input class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-500 dark:bg-gray-600"
                                   type="checkbox"
                                   x-model="rule.checked"
                                   @change="syncChecklist()">
                            <span class="select-none text-xs font-medium leading-relaxed transition-all duration-200"
                                  :class="rule.checked ? 'text-indigo-800 line-through opacity-70 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300'"
                                  x-text="rule.text"></span>

                            {{-- ✅ NUEVO: Checkmark animado cuando se marca --}}
                            <svg class="ml-auto h-4 w-4 flex-shrink-0 text-emerald-500"
                                 x-show="rule.checked"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-50 rotate-12"
                                 x-transition:enter-end="opacity-100 scale-100 rotate-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-50"
                                 fill="currentColor"
                                 viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                      clip-rule="evenodd" />
                            </svg>
                        </label>
                    </template>

                    <div class="py-6 text-center text-xs text-gray-400 dark:text-gray-500"
                         x-show="activeRules.length === 0"
                         x-transition>
                        {{ __('labels.without_setup_rules') }}
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-100 bg-gray-50/30 p-4 dark:border-gray-700 dark:bg-gray-800/30">
                    <div class="flex w-full items-center justify-center gap-2 rounded-lg border py-3 text-xs font-bold shadow-sm transition-all duration-300"
                         :class="canTakeTrade ? 'bg-emerald-50 text-emerald-700 border-emerald-200 scale-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30' : 'bg-gray-100 text-gray-400 border-gray-200 opacity-75 cursor-not-allowed scale-95 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-500'">
                        <i class="fa-solid transition-transform duration-300"
                           :class="canTakeTrade ? 'fa-check scale-110' : 'fa-lock scale-100'"></i>
                        <span x-text="tradeButtonText"></span>
                    </div>
                </div>

            </div>


            {{-- COL 2: DIARIO (35%) --}}
            <div class="flex w-full flex-col border-r border-gray-200 bg-gray-50 lg:w-[35%] dark:border-gray-700 dark:bg-gray-900"
                 x-show="mobileTab === 'journal' || width >= 1024">

                <div class="flex h-10 shrink-0 items-center border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase text-gray-700 dark:text-gray-300">
                        <i class="fa-solid fa-feather text-indigo-500 dark:text-indigo-400"></i> {{ __('labels.logbook') }}
                    </span>
                    {{-- ✅ NUEVO: Contador de notas --}}
                    <span class="ml-auto text-[10px] font-bold text-gray-400 dark:text-gray-500"
                          x-show="sessionNotes.length > 0">
                        <span x-text="sessionNotes.length"></span> {{ __('labels.note') }}<span x-show="sessionNotes.length > 1">s</span>
                    </span>
                </div>

                <div id="notes-container"
                     class="flex-1 space-y-4 overflow-y-auto p-4 [&::-webkit-scrollbar]:hidden"
                     x-ref="notesContainer">
                    <template x-for="note in sessionNotes"
                              :key="note.id">
                        <div class="flex gap-3"
                             {{-- ✅ OPTIMISTIC UI: Transición de entrada --}}
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform -translate-y-2 scale-95"
                             x-transition:enter-end="opacity-100 transform translate-y-0 scale-100">
                            <div class="flex flex-col items-center pt-1.5">
                                <div class="h-2 w-2 rounded-full transition-all duration-300"
                                     :class="{
                                         'bg-emerald-400 shadow-emerald-200 shadow-lg': note.mood === 'confident',
                                         'bg-rose-400 shadow-rose-200 shadow-lg': note.mood === 'fear' || note.mood === 'anxious',
                                         'bg-amber-400 shadow-amber-200 shadow-lg': note.mood === 'fomo',
                                         'bg-gray-300 dark:bg-gray-600': note.mood === 'neutral' || note.mood === 'calm'
                                     }">
                                </div>
                                <div class="my-1 w-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div class="flex-1 pb-2">
                                <div class="mb-1 flex items-center gap-2">
                                    <span class="font-mono text-[10px] font-bold text-gray-400 dark:text-gray-500"
                                          x-text="note.time"></span>
                                    <span class="text-[10px] font-bold uppercase tracking-wide transition-colors"
                                          :class="{
                                              'text-emerald-600': note.mood === 'confident',
                                              'text-rose-600': note.mood === 'fear' || note.mood === 'anxious',
                                              'text-amber-600': note.mood === 'fomo',
                                              'text-gray-500 dark:text-gray-400': note.mood === 'neutral' || note.mood === 'calm'
                                          }"
                                          x-text="note.mood"></span>
                                </div>
                                <div class="rounded-lg border bg-white p-3 text-xs text-gray-700 shadow-sm transition-all duration-200 hover:shadow-md dark:bg-gray-800 dark:text-gray-300"
                                     :class="{
                                         'border-emerald-200 dark:border-emerald-500/30': note.mood === 'confident',
                                         'border-rose-200 dark:border-rose-500/30': note.mood === 'fear' || note.mood === 'anxious',
                                         'border-amber-200 dark:border-amber-500/30': note.mood === 'fomo',
                                         'border-gray-200 dark:border-gray-700': note.mood === 'neutral' || note.mood === 'calm'
                                     }"
                                     x-text="note.note"></div>
                            </div>
                        </div>
                    </template>

                    <div class="py-10 text-center text-xs text-gray-400 dark:text-gray-500"
                         x-show="sessionNotes.length === 0"
                         x-transition>
                        {{ __('labels.session_calm') }}
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                    {{-- Selector de Mood --}}
                    <div class="mb-2 flex gap-2 overflow-x-auto pb-1"
                         style="scrollbar-width: none;">
                        <template x-for="mood in noteMoodOptions"
                                  :key="mood.key">
                            <button class="shrink-0 rounded-full border px-3 py-1 text-[10px] font-bold uppercase transition-all duration-200"
                                    @click="newNoteMood = mood.key"
                                    :class="{
                                        'bg-indigo-100 text-indigo-700 border-indigo-300 scale-105 shadow-sm dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/40': newNoteMood === mood.key,
                                        'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100 hover:border-gray-300 dark:hover:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-600': newNoteMood !== mood.key
                                    }">
                                <span x-text="mood.label"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Input de Nueva Nota --}}
                    <div class="relative">
                        <input class="w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-4 pr-10 text-xs transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:bg-gray-700"
                               type="text"
                               x-model="newNoteText"
                               @keydown.enter="submitNote"
                               placeholder="{{ __('labels.new_thinking') }}">

                        <button class="absolute right-1.5 top-1.5 rounded-lg p-1.5 transition-all duration-200"
                                :class="newNoteText.trim() ? 'text-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10' : 'text-gray-300 cursor-not-allowed dark:text-gray-600'"
                                @click="submitNote"
                                :disabled="!newNoteText.trim()">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>


            {{-- COL 3: TRADES (35%) --}}
            <div class="flex w-full flex-col bg-white lg:w-[35%] dark:bg-gray-800"
                 x-show="mobileTab === 'trades' || width >= 1024">

                <div class="flex h-10 shrink-0 items-center justify-between border-b border-gray-100 bg-gray-50/30 px-4 dark:border-gray-700 dark:bg-gray-800/30">
                    <span class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> {{ __('labels.recents') }}
                    </span>
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[9px] text-gray-400 dark:bg-gray-700 dark:text-gray-300"
                          x-text="metrics.count"></span>
                </div>

                {{-- SIN SCROLL: Overflow Hidden + Slice --}}
                <div class="relative flex-1 overflow-hidden p-3">
                    <div class="flex flex-col gap-2">
                        {{-- SLICE PARA NO SCROLL: Mostramos max 8 --}}
                        <template x-for="trade in trades.slice(0, 8)"
                                  :key="trade.id">
                            <div class="group flex flex-col gap-1 rounded border border-gray-200 bg-white p-2 shadow-sm transition-all hover:border-indigo-200 dark:border-gray-700 dark:bg-gray-700/40 dark:hover:border-indigo-500/40">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="h-4 w-0.5 rounded-full"
                                             :class="trade.pnl >= 0 ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-900 dark:text-gray-100"
                                                      x-text="trade.symbol"></span>
                                                <span class="rounded px-1 py-px text-[8px] font-bold uppercase"
                                                      :class="trade.direction == 'long' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400'"
                                                      x-text="trade.direction"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        {{-- Precio del Trade --}}
                                        <div class="text-xs font-bold transition-all duration-300"
                                             :class="[
                                                 trade.pnl >= 0 ? 'text-emerald-600' : 'text-rose-600',
                                                 ghostMode ? 'blur-sm select-none opacity-60' : ''
                                             ]"
                                             x-text="(trade.pnl > 0 ? '+' : '') + trade.pnl + '$'"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border-t border-gray-50 pt-1 dark:border-gray-700">
                                    <span class="font-mono text-[9px] text-gray-400 dark:text-gray-500"
                                          x-text="trade.time"></span>
                                    <div class="flex gap-1"
                                         x-show="!trade.mood">
                                        <button class="text-[9px] text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400"
                                                @click="setTradeMood(trade.id, 'neutral')">😐</button>
                                        <button class="text-[9px] text-gray-300 dark:text-gray-600 hover:text-amber-500"
                                                @click="setTradeMood(trade.id, 'fomo')">🔥</button>
                                        <button class="text-[9px] text-gray-300 dark:text-gray-600 hover:text-emerald-500"
                                                @click="setTradeMood(trade.id, 'confident')">💪</button>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase text-gray-400 dark:text-gray-500"
                                          x-show="trade.mood"
                                          x-text="trade.mood"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex h-full flex-col items-center justify-center text-gray-300 dark:text-gray-600 opacity-50"
                         x-show="trades.length === 0">
                        <i class="fa-solid fa-hourglass-half mb-2"></i>
                        <p class="text-[10px] font-bold">{{ __('labels.waiting_closes') }}</p>
                    </div>

                    {{-- Aviso +X trades --}}
                    <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-white via-white to-transparent py-2 text-center dark:from-gray-800 dark:via-gray-800"
                         x-show="trades.length > 8">
                        <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500">+<span x-text="trades.length - 8"></span> {{ __('labels.hide_trades') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STEP 3: SUMMARY --}}
    <div class="flex h-full flex-col items-center justify-center overflow-y-auto bg-gradient-to-br from-gray-50 to-white p-6 dark:from-gray-900 dark:to-gray-900"
         x-show="step === 3"
         {{-- ✅ Transición de entrada suave --}}
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-cloak>
        <div class="w-full max-w-2xl">
            {{-- Header con animación --}}
            <div class="mb-8 text-center">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold uppercase tracking-wide"
                     :class="metrics.pnl >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400'">
                    <i class="fa-solid fa-flag-checkered"></i>
                    <span>{{ __('labels.end_session') }}</span>
                </div>
                <h2 class="text-3xl font-black text-gray-900 dark:text-gray-100">{{ __('labels.resume_sesion') }}</h2>
            </div>

            {{-- Métricas Principales --}}
            <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
                {{-- PnL Total --}}
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-1 text-3xl font-black transition-all duration-300"
                         :class="[
                             metrics.pnl >= 0 ? 'text-emerald-600' : 'text-rose-600',
                             ghostMode ? 'blur-xl select-none opacity-50' : ''
                         ]"
                         x-text="(metrics.pnl > 0 ? '+' : '') + metrics.pnl.toFixed(2) + '$'"></div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('labels.total_pnl') }}</p>
                </div>

                {{-- PnL Porcentaje --}}
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-1 text-3xl font-black"
                         :class="metrics.pnl_percent >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                         x-text="(metrics.pnl_percent > 0 ? '+' : '') + metrics.pnl_percent.toFixed(2) + '%'"></div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('labels.roi') }}</p>
                </div>

                {{-- Trades --}}
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-1 text-3xl font-black text-gray-800 dark:text-gray-100"
                         x-text="metrics.count"></div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('labels.trades') }}</p>
                </div>

                {{-- Winrate --}}
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-1 text-3xl font-black"
                         :class="{
                             'text-emerald-600': metrics.winrate >= 50,
                             'text-amber-600': metrics.winrate >= 40 && metrics.winrate < 50,
                             'text-rose-600': metrics.winrate < 40
                         }"
                         x-text="metrics.winrate + '%'"></div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('labels.winrate') }}</p>
                </div>
            </div>

            {{-- Tiempo de Sesión --}}
            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-center gap-3">
                    <i class="fa-solid fa-clock text-gray-400 dark:text-gray-500"></i>
                    <span class="font-mono text-2xl font-bold text-gray-700 dark:text-gray-100"
                          x-text="timer"></span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('labels.of_trading') }}</span>
                </div>
            </div>

            {{-- Mood Final --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- ✅ NUEVO: Post Session Notes --}}
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        <i class="fa-solid fa-pen-to-square mr-1 text-indigo-500 dark:text-indigo-400"></i>
                        {{ __('labels.reflection_post_sesion') }}
                    </label>
                    <textarea class="w-full resize-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700 transition-all focus:bg-white focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:bg-gray-700"
                              x-model="postSessionNotes"
                              placeholder="{{ __('labels.placeholder_end_session') }}"
                              rows="3"></textarea>
                </div>
                <p class="mb-6 text-center text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('labels.how_do_you_feel_end') }}
                </p>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    {{-- Satisfied --}}
                    <button class="group relative rounded-xl border-2 p-5 transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="'border-gray-200 bg-gray-50 hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-emerald-500/50 dark:hover:bg-emerald-500/10'"
                            @click="finishSession('satisfied')">
                        <div class="mb-2 text-4xl transition-transform group-hover:scale-110">😊</div>
                        <span class="block text-xs font-bold text-gray-700 group-hover:text-emerald-700 dark:text-gray-200 dark:group-hover:text-emerald-400">{{ __('labels.mood_satisfied') }}</span>
                    </button>

                    {{-- Confident --}}
                    <button class="group relative rounded-xl border-2 p-5 transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="'border-gray-200 bg-gray-50 hover:border-indigo-300 hover:bg-indigo-50 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-indigo-500/50 dark:hover:bg-indigo-500/10'"
                            @click="finishSession('confident')">
                        <div class="mb-2 text-4xl transition-transform group-hover:scale-110">💪</div>
                        <span class="block text-xs font-bold text-gray-700 group-hover:text-indigo-700 dark:text-gray-200 dark:group-hover:text-indigo-400">{{ __('labels.mood_confident') }}</span>
                    </button>

                    {{-- Neutral --}}
                    <button class="group relative rounded-xl border-2 p-5 transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-gray-100 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-600'"
                            @click="finishSession('neutral')">
                        <div class="mb-2 text-4xl transition-transform group-hover:scale-110">😐</div>
                        <span class="block text-xs font-bold text-gray-700 group-hover:text-gray-800 dark:text-gray-200 dark:group-hover:text-gray-100">{{ __('labels.mood_neutral') }}</span>
                    </button>

                    {{-- Tired --}}
                    <button class="group relative rounded-xl border-2 p-5 transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="'border-gray-200 bg-gray-50 hover:border-amber-300 hover:bg-amber-50 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-amber-500/50 dark:hover:bg-amber-500/10'"
                            @click="finishSession('tired')">
                        <div class="mb-2 text-4xl transition-transform group-hover:scale-110">😴</div>
                        <span class="block text-xs font-bold text-gray-700 group-hover:text-amber-700 dark:text-gray-200 dark:group-hover:text-amber-400">{{ __('labels.mood_tired') }}</span>
                    </button>

                    {{-- Frustrated --}}
                    <button class="group relative rounded-xl border-2 p-5 transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="'border-gray-200 bg-gray-50 hover:border-rose-300 hover:bg-rose-50 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-rose-500/50 dark:hover:bg-rose-500/10'"
                            @click="finishSession('frustrated')">
                        <div class="mb-2 text-4xl transition-transform group-hover:scale-110">😤</div>
                        <span class="block text-xs font-bold text-gray-700 group-hover:text-rose-700 dark:text-gray-200 dark:group-hover:text-rose-400">{{ __('labels.mood_frustrated') }}</span>
                    </button>

                    {{-- Anxious --}}
                    <button class="group relative rounded-xl border-2 p-5 transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="'border-gray-200 bg-gray-50 hover:border-purple-300 hover:bg-purple-50 hover:shadow-lg dark:border-gray-600 dark:bg-gray-700 dark:hover:border-purple-500/50 dark:hover:bg-purple-500/10'"
                            @click="finishSession('anxious')">
                        <div class="mb-2 text-4xl transition-transform group-hover:scale-110">😰</div>
                        <span class="block text-xs font-bold text-gray-700 group-hover:text-purple-700 dark:text-gray-200 dark:group-hover:text-purple-400">{{ __('labels.mood_anxious') }}</span>
                    </button>
                </div>
            </div>

            {{-- Botón alternativo: Volver sin cerrar (opcional) --}}
            <div class="mt-6 text-center">
                <button class="text-xs text-gray-500 underline transition hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="step = 2">
                    {{ __('labels.back_to_sesion') }}
                </button>
            </div>
        </div>
    </div>

</div>
