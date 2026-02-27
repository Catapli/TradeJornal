<div class="min-h-screen bg-gray-50 p-6"
     x-data="playbook">

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
    <div class="fixed inset-0 z-[9998] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
         wire:loading
         wire:target="deleteStrategy, duplicateStrategy">
        <x-loader></x-loader>
    </div>


    {{-- ? Show Alerta --}}
    <x-modal-template show="showAlert">
    </x-modal-template>

    <x-confirm-modal />


    {{-- MODAL ANÁLISIS (Fullscreen) --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 backdrop-blur-sm"
         x-show="showAnalysisModal"
         x-transition.opacity
         style="display: none;">

        <div class="flex h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-gray-50 shadow-2xl">

            {{-- HEADER --}}
            <div class="flex items-center justify-between border-b border-gray-200 bg-white px-8 py-5">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg shadow-sm"
                         :style="'background-color: ' + (analysisStrategy?.color || '#ccc')"></div>
                    <div>
                        <h2 class="text-2xl font-black text-gray-900"
                            x-text="analysisStrategy?.name"></h2>
                        <p class="text-sm text-gray-500"
                           x-text="analysisStrategy?.timeframe + ' • ' + (analysisStrategy?.description || '')"></p>
                    </div>
                </div>

                <button class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                        @click="closeAnalysis()">
                    <i class="fa-solid fa-times text-2xl"></i>
                </button>
            </div>

            {{-- TABS HEADER --}}
            {{-- TABS HEADER --}}
            <div class="flex gap-8 border-b border-gray-200 bg-white px-8">
                <template x-for="tab in ['overview', 'temporal', 'heatmap', 'trades']">
                    <button class="border-b-4 px-2 py-4 text-sm font-bold capitalize transition-all"
                            {{-- AQUÍ ESTÁ EL TRUCO: al hacer click, esperamos un poco y renderizamos --}}
                            @click="activeTab = tab; setTimeout(() => { renderCharts(); if(tab === 'heatmap') renderHeatmap(); }, 50)"
                            :class="activeTab === tab ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">

                        {{-- Texto bonito para cada tab --}}
                        <span x-text="tab === 'overview' ? '{{ __('labels.resume') }}' : (tab === 'temporal' ? '{{ __('labels.temporal') }}' : tab)"></span>
                    </button>
                </template>
            </div>

            {{-- CONTENT BODY (Scrollable) --}}
            <div class="flex-1 overflow-y-auto p-8">

                {{-- TAB: OVERVIEW --}}
                <div class="space-y-6"
                     x-show="activeTab === 'overview'"
                     x-transition.opacity>
                    <!-- Grid de KPIs -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Profit Factor -->
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-bold uppercase text-gray-400">{{ __('labels.profit_factor') }}</p>
                                <i class="fa-solid fa-scale-balanced text-gray-300"></i>
                            </div>
                            <p class="text-3xl font-black"
                               :class="analysisStrategy?.stats_profit_factor >= 1.5 ? 'text-emerald-500' : 'text-amber-500'"
                               x-text="analysisStrategy?.stats_profit_factor || 'N/A'"></p>
                            <p class="mt-1 text-xs text-gray-400">{{ __('labels.target') }} &gt;1.5</p>
                        </div>

                        <!-- Winrate -->
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-bold uppercase text-gray-400">{{ __('labels.winrate') }}</p>
                                <i class="fa-solid fa-bullseye text-gray-300"></i>
                            </div>
                            <p class="text-3xl font-black"
                               :class="analysisStrategy?.stats_winrate >= 40 ? 'text-indigo-600' : 'text-rose-500'"
                               x-text="(analysisStrategy?.stats_winrate || 0) + '%'"></p>
                            <p class="mt-1 text-xs text-gray-400"
                               x-text="analysisStrategy?.stats_winning_trades + '/' + analysisStrategy?.stats_total_trades + ' trades'"></p>
                        </div>

                        <!-- Expectancy -->
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-bold uppercase text-gray-400">{{ __('labels.expectancy') }}</p>
                                <i class="fa-solid fa-hand-holding-dollar text-gray-300"></i>
                            </div>
                            <p class="text-3xl font-black"
                               :class="analysisStrategy?.stats_expectancy >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                               x-text="Number(analysisStrategy?.stats_expectancy || 0).toFixed(2) + '$'"></p>
                            <p class="mt-1 text-xs text-gray-400">{{ __('labels.by_trade') }}</p>
                        </div>

                        <!-- Max Drawdown -->
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-bold uppercase text-gray-400">{{ __('labels.max_drawdown') }}</p>
                                <i class="fa-solid fa-chart-area text-gray-300"></i>
                            </div>
                            <p class="text-3xl font-black text-rose-500"
                               x-text="Number(analysisStrategy?.stats_max_drawdown_pct || 0).toFixed(2) + '%'"></p>
                            <p class="mt-1 text-xs text-gray-400">{{ __('labels.max_risk') }}</p>
                        </div>
                    </div>

                    <!-- Streaks & Eficiencia -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h4 class="mb-4 flex items-center gap-2 font-bold text-gray-900">
                                <i class="fa-solid fa-bolt text-amber-500"></i>
                                {{ __('labels.streaks') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between rounded-lg bg-emerald-50 p-3">
                                    <span class="text-sm font-medium text-gray-700">{{ __('labels.better_streak') }}</span>
                                    <span class="text-lg font-black text-emerald-600"
                                          x-text="analysisStrategy?.stats_best_win_streak || 0"></span>
                                </div>
                                <div class="flex items-center justify-between rounded-lg bg-rose-50 p-3">
                                    <span class="text-sm font-medium text-gray-700">{{ __('labels.worst_streak') }}</span>
                                    <span class="text-lg font-black text-rose-600"
                                          x-text="analysisStrategy?.stats_worst_loss_streak || 0"></span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h4 class="mb-4 flex items-center gap-2 font-bold text-gray-900">
                                <i class="fa-solid fa-gauge-high text-indigo-500"></i>
                                {{ __('labels.efficiency') }}
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <span class="text-sm text-gray-600">{{ __('labels.actual_average') }}</span>
                                    <span class="font-bold text-indigo-600"
                                          x-text="analysisStrategy?.stats_avg_rr ? Number(analysisStrategy.stats_avg_rr).toFixed(2) : '-'"></span>
                                </div>
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <span class="text-sm text-gray-600">{{ __('labels.average_mfe') }}</span>
                                    <span class="font-bold text-emerald-600"
                                          x-text="(Number(analysisStrategy?.stats_avg_mfe_pct || 0) * 100).toFixed(1) + ' bps'"></span>
                                </div>
                                <div class="flex justify-between pt-1">
                                    <span class="text-sm text-gray-600">{{ __('labels.average_mae') }}</span>
                                    <span class="font-bold text-rose-500"
                                          x-text="(Number(analysisStrategy?.stats_avg_mae_pct || 0) * 100).toFixed(1) + ' bps'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: TEMPORAL --}}
                <div class="space-y-6"
                     x-show="activeTab === 'temporal'"
                     x-transition.opacity>
                    <!-- Días -->
                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="font-bold text-gray-900">{{ __('labels.performance_by_day_week') }}</h4>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600">
                                {{ __('labels.total_pnl') }}: <span x-text="(analysisStrategy?.stats_total_pnl || 0).toFixed(2) + '$'"></span>
                            </span>
                        </div>
                        <div class="w-full"
                             x-ref="daysChart"></div>
                    </div>

                    <!-- Horas -->
                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h4 class="mb-4 font-bold text-gray-900">{{ __('labels.day_activity') }}</h4>
                        <div class="w-full"
                             x-ref="hoursChart"></div>
                    </div>
                </div>

                {{-- TAB: HEATMAP --}}
                <div x-show="activeTab === 'heatmap'"
                     x-transition.opacity>
                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">

                        {{-- Header con Leyenda --}}
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ __('labels.heatmap_performance') }}</h3>
                                <p class="text-xs text-gray-500">{{ __('labels.performance_by_day_hour') }}</p>
                            </div>
                        </div>

                        {{-- Estado de Carga --}}
                        <div class="flex h-[350px] items-center justify-center text-gray-400"
                             x-show="isLoadingTrades">
                            <div class="text-center">
                                <i class="fa-solid fa-circle-notch fa-spin mb-2 text-3xl"></i>
                                <p class="text-sm">{{ __('labels.loading_data_map') }}</p>
                            </div>
                        </div>

                        {{-- Contenedor Gráfico --}}
                        <div class="h-[350px] w-full"
                             x-show="!isLoadingTrades && trades.length > 0"
                             x-ref="heatmapChart"></div>

                        {{-- Sin Datos --}}
                        <div class="flex h-[350px] items-center justify-center"
                             x-show="!isLoadingTrades && trades.length === 0">
                            <div class="text-center text-gray-400">
                                <i class="fa-solid fa-inbox mb-3 text-4xl"></i>
                                <p class="text-sm">{{ __('labels.not_enough_data_heatmap') }}</p>
                            </div>
                        </div>
                    </div>
                </div>



                {{-- TAB: TRADES --}}
                <div x-show="activeTab === 'trades'"
                     x-transition.opacity>
                    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">

                        <div class="p-8 text-center text-gray-400"
                             x-show="isLoadingTrades">
                            <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> {{ __('labels.loading_history') }}
                        </div>

                        <table class="w-full min-w-full divide-y divide-gray-200"
                               x-show="!isLoadingTrades">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('labels.date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('labels.direction') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('labels.duration') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('labels.PnL') }} ($)</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('labels.photo') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="trade in trades"
                                          :key="trade.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"
                                            x-text="trade.exit_time"></td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                                  :class="trade.direction === 'Long' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                  x-text="trade.direction"></span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                            x-text="trade.duration"></td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold"
                                            :class="trade.pnl >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                            x-text="(trade.pnl > 0 ? '+' : '') + trade.pnl + '$'"></td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <template x-if="trade.screenshot_url">
                                                <a class="text-indigo-600 hover:text-indigo-900"
                                                   :href="trade.screenshot_url"
                                                   target="_blank">
                                                    <i class="fa-solid fa-image"></i>
                                                </a>
                                            </template>
                                            <template x-if="!trade.screenshot_url">
                                                <span class="text-gray-300">-</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div class="p-8 text-center text-gray-500"
                             x-show="!isLoadingTrades && trades.length === 0">
                            {{ __('labels.not_trades_strategy') }}
                        </div>
                    </div>
                </div>


            </div>

        </div>
    </div>
    {{-- ============ FIN MODAL ANÁLISIS ============ --}}

    {{-- HEADER --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex gap-4">
                <i class="fa-solid fa-chess text-3xl text-indigo-600"></i>
                <h1 class="text-3xl font-black text-gray-900">{{ __('menu.playbook') }}</h1>
            </div>
            <p class="text-sm text-gray-500">{{ __('menu.resume_playbook') }}</p>
        </div>

        {{-- ✅ ALPINE: Abrir modal sin round-trip --}}
        <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-indigo-700 hover:shadow-lg"
                @click="openCreateModal()">
            <i class="fa-solid fa-plus"></i> {{ __('labels.new_setup') }}
        </button>
    </div>

    {{-- TOOLBAR: Búsqueda y Filtros --}}
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        {{-- Search --}}
        <div class="relative w-full md:w-96">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="fa-solid fa-search text-gray-400"></i>
            </div>
            <input class="block w-full rounded-xl border-gray-200 bg-white pl-10 focus:border-indigo-500 focus:ring-indigo-500"
                   wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="{{ __('labels.search_strategy') }}">
        </div>

        {{-- Sort Dropdown --}}
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-500">{{ __('labels.order_by') }}</span>
            <select class="rounded-xl border-gray-200 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    wire:model.live="sortBy">
                <option value="stats_total_pnl">{{ __('labels.best_profit') }}</option>
                <option value="stats_winrate">{{ __('labels.best_winrate') }}</option>
                <option value="created_at">{{ __('labels.more_recents') }}</option>
                <option value="name">{{ __('labels.alphabetic') }}</option>
            </select>

            <button class="rounded-lg border border-gray-200 bg-white p-2 text-gray-500 shadow-sm hover:text-indigo-600"
                    wire:click="$set('sortDir', '{{ $sortDir === 'asc' ? 'desc' : 'asc' }}')">
                <i class="fa-solid fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
            </button>
        </div>
    </div>


    {{-- GRID DE ESTRATEGIAS --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        @forelse($this->strategies as $strategy)
            @php
                $payload = [
                    'id' => $strategy->id,
                    'name' => $strategy->name,
                    'description' => $strategy->description,
                    'timeframe' => $strategy->timeframe,
                    'color' => $strategy->color,
                    'is_main' => (bool) $strategy->is_main,
                    'rules' => $strategy->rules ?? [],
                    'image_url' => $strategy->image_url,

                    // ✅ AÑADE ESTO (Stats para el modal)
                    'stats_profit_factor' => $strategy->stats_profit_factor,
                    'stats_winrate' => $strategy->stats_winrate, // Calculado en computed
                    'stats_expectancy' => $strategy->stats_expectancy,
                    'stats_max_drawdown_pct' => $strategy->stats_max_drawdown_pct,
                    'stats_best_win_streak' => $strategy->stats_best_win_streak,
                    'stats_worst_loss_streak' => $strategy->stats_worst_loss_streak,
                    'stats_avg_rr' => $strategy->stats_avg_rr,
                    'stats_avg_mfe_pct' => $strategy->stats_avg_mfe_pct,

                    // ✅ AÑADE ESTO (Datos para gráficos)
                    'chart_data' => $strategy->chart_data,
                    '',
                ];
            @endphp

            <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-md">

                {{-- Imagen del Setup --}}
                <div class="relative h-40 w-full overflow-hidden bg-gray-100">
                    @if ($strategy->image_path)
                        <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                             src="{{ Storage::url($strategy->image_path) }}">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gray-50 text-gray-300">
                            <i class="fa-solid fa-image text-4xl"></i>
                        </div>
                    @endif

                    <div class="absolute right-3 top-3 rounded-md bg-black/70 px-2 py-1 text-xs font-bold text-white backdrop-blur-md">
                        {{ $strategy->timeframe }}
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div class="flex flex-1 flex-col p-5">
                    <div class="mb-2 flex items-start justify-between">
                        <h3 class="line-clamp-1 text-lg font-bold text-gray-900">{{ $strategy->name }}</h3>
                        <div class="h-3 w-3 rounded-full"
                             style="background-color: {{ $strategy->color }}"></div>
                    </div>

                    <p class="mb-4 line-clamp-2 text-xs text-gray-500">
                        {{ $strategy->description ?: __('labels.not_description') }}
                    </p>

                    {{-- Stats Rápidas --}}
                    <div class="mt-auto grid grid-cols-2 gap-2 rounded-xl bg-gray-50 p-3">
                        <div class="text-center">
                            <p class="text-[10px] font-bold uppercase text-gray-400">{{ __('labels.winrate') }}</p>
                            <p class="{{ $strategy->stats_winrate >= 50 ? 'text-emerald-600' : 'text-rose-500' }} text-sm font-black">
                                {{ $strategy->stats_winrate }}%
                            </p>
                        </div>
                        <div class="border-l border-gray-200 text-center">
                            <p class="text-[10px] font-bold uppercase text-gray-400">{{ __('labels.total_pnl') }}</p>
                            <p class="{{ $strategy->stats_total_pnl >= 0 ? 'text-emerald-600' : 'text-rose-500' }} text-sm font-black">
                                {{ number_format($strategy->stats_total_pnl, 0) }}$
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Footer Acciones --}}
                <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/50 px-5 py-3">
                    <span class="text-xs font-medium text-gray-500">{{ $strategy->stats_count }} {{ __('labels.trades') }}</span>

                    <div class="flex gap-2">
                        <button class="rounded-md p-1.5 text-gray-400 transition hover:bg-white hover:text-emerald-600 hover:shadow-sm"
                                @click="openAnalysis(@js($payload))"
                                title="{{ __('labels.watch_analyze') }}">
                            <i class="fa-solid fa-chart-line"></i>
                        </button>
                        {{-- ✅ ALPINE: Editar sin round-trip --}}
                        <button class="rounded-md p-1.5 text-gray-400 transition hover:bg-white hover:text-indigo-600 hover:shadow-sm"
                                @click="openEditModal(@js($payload))"
                                title="{{ __('labels.edit_setup') }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>

                        {{-- ✅ ALPINE: Borrar con confirm --}}
                        <button class="rounded-md p-1.5 text-gray-400 transition hover:bg-white hover:text-rose-600 hover:shadow-sm"
                                @click="deleteStrategy({{ $strategy->id }})"
                                title="{{ __('labels.delete_strategy') }}">
                            <i class="fa-solid fa-trash"></i>
                        </button>

                        {{-- Clonar --}}
                        <button class="rounded-md p-1.5 text-gray-400 transition hover:bg-white hover:text-indigo-600 hover:shadow-sm"
                                @click="duplicateStrategy({{ $strategy->id }})"
                                title="{{ __('labels.clone_strategy') }}">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
                <div class="mb-4 rounded-full bg-white p-4 shadow-sm">
                    <i class="fa-solid fa-book-open text-3xl text-indigo-200"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-600">{{ __('labels.strategies_empty') }}</h3>
                <p class="text-sm">{{ __('labels.create_first_strategy') }}</p>
                <button class="mt-4 font-bold text-indigo-600 hover:underline"
                        @click="openCreateModal()">
                    {{ __('labels.create_now') }}
                </button>
            </div>
        @endforelse

    </div>

    {{-- MODAL CREAR/EDITAR --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
         x-show="showModal"
         x-transition.opacity
         @keydown.escape.window="!isSaving && closeModal()"
         @click.self="!isSaving && closeModal()"
         @strategy-saved.window="closeModal()"
         style="display:none">
        {{-- CAPA EXTERIOR: solo posicionamiento (relative). SIN overflow aquí. --}}
        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl"
             @click.stop>
            {{-- ✅ OVERLAY DE LOADING — absolute inset-0 funciona porque el padre tiene 'relative' y NO tiene overflow --}}
            <div class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 rounded-2xl bg-white/80 backdrop-blur-sm"
                 x-show="isSaving"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display:none">
                <svg class="h-8 w-8 animate-spin text-indigo-600"
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
                <p class="text-sm font-bold text-gray-600"
                   x-text="isEditing ? '{{ __('labels.updating_strategy') }}' : '{{ __('labels.creating_strategy') }}'">
                </p>
                <p class="text-xs text-gray-400">{{ __('labels.please_wait') }}</p>
            </div>

            {{-- CAPA INTERIOR: esta es la que hace scroll, no el padre --}}
            <div class="max-h-[95vh] overflow-y-auto rounded-2xl">

                {{-- Header --}}
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                    <h3 class="text-lg font-bold text-gray-900"
                        x-text="isEditing ? '{{ __('labels.edit_setup') }}' : '{{ __('labels.new_setup') }}'">
                    </h3>
                    <button class="text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-30"
                            @click="closeModal()"
                            :disabled="isSaving">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Cuerpo --}}
                <div class="space-y-6 p-6">

                    {{-- 1. IDENTIDAD --}}
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- Nombre --}}
                        <div>
                            <label class="mb-1 block text-xs font-bold text-gray-500">{{ __('labels.name_setup') }}</label>
                            <input class="{{ $errors->has('formName') ? 'border-rose-400 focus:border-rose-400' : 'border-gray-300' }} w-full rounded-lg text-sm focus:ring-indigo-500"
                                   type="text"
                                   wire:model="formName"
                                   placeholder="{{ __('labels.ej_name_strategy') }}">
                            @error('formName')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Timeframe + Color --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-500">{{ __('labels.timeframe') }}</label>
                                <select class="{{ $errors->has('formTimeframe') ? 'border-rose-400' : 'border-gray-300' }} w-full rounded-lg text-sm focus:ring-indigo-500"
                                        wire:model="formTimeframe">
                                    <option value="">{{ __('labels.select') }}</option>
                                    <option value="M1">M1</option>
                                    <option value="M5">M5</option>
                                    <option value="M15">M15</option>
                                    <option value="M30">M30</option>
                                    <option value="H1">H1</option>
                                    <option value="H4">H4</option>
                                    <option value="D1">D1</option>
                                </select>
                                @error('formTimeframe')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-500">{{ __('labels.color') }}</label>
                                <input class="{{ $errors->has('formColor') ? 'border-rose-400' : 'border-gray-300' }} h-[38px] w-full cursor-pointer rounded-lg p-1"
                                       type="color"
                                       wire:model="formColor">
                                @error('formColor')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="mb-1 block text-xs font-bold text-gray-500">{{ __('labels.description') }}</label>
                        <textarea class="{{ $errors->has('formDescription') ? 'border-rose-400' : 'border-gray-300' }} w-full rounded-lg text-sm focus:ring-indigo-500"
                                  wire:model="formDescription"
                                  rows="2"
                                  placeholder="{{ __('labels.what_is_this_setup') }}"></textarea>
                        @error('formDescription')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Toggle Principal --}}
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div>
                            <span class="text-sm font-bold text-gray-800">{{ __('labels.main_strategy') }}</span>
                            <p class="text-xs text-gray-500">{{ __('labels.rules_default') }}</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input class="peer sr-only"
                                   type="checkbox"
                                   wire:model="formIsmain">
                            <div
                                 class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-full">
                            </div>
                        </label>
                    </div>

                    {{-- Reglas --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <label class="mb-3 block text-xs font-bold uppercase text-gray-500">{{ __('labels.entry_rules') }}</label>
                        <div class="mb-3 flex gap-2">
                            <input class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-indigo-500"
                                   type="text"
                                   x-model="newRule"
                                   @keydown.enter.prevent="addRule()"
                                   placeholder="{{ __('labels.write_rule_and_enter') }}">
                            <button class="rounded-lg bg-gray-900 px-3 text-white hover:bg-black"
                                    @click="addRule()">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <ul class="space-y-2">
                            <template x-for="(rule, index) in $wire.formRules"
                                      :key="index">
                                <li class="flex items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm">
                                    <span class="text-gray-700">
                                        <i class="fa-solid fa-check mr-2 text-emerald-500"></i>
                                        <span x-text="rule"></span>
                                    </span>
                                    <button class="text-gray-400 hover:text-rose-500"
                                            @click="removeRule(index)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </li>
                            </template>
                            <li class="py-2 text-center text-xs italic text-gray-400"
                                x-show="$wire.formRules.length === 0">
                                {{ __('labels.not_rules_defined') }}
                            </li>
                        </ul>
                        @error('formRules.*')
                            <p class="mt-2 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Imagen --}}
                    <div>
                        <label class="mb-2 block text-xs font-bold text-gray-500">{{ __('labels.image_ideal_model') }}</label>
                        <div class="flex w-full items-center justify-center">
                            <label class="relative flex h-48 w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-gray-50 hover:bg-gray-100"
                                   :class="isDragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300'"
                                   @dragover.prevent="isDragging = true"
                                   @dragleave.prevent="isDragging = false"
                                   @drop.prevent="handleDrop($event)">
                                {{-- Preview Alpine (nueva foto seleccionada) --}}
                                <template x-if="photoPreview">
                                    <img class="absolute inset-0 h-full w-full object-contain"
                                         :src="photoPreview">
                                </template>
                                {{-- Preview foto existente (edición) --}}
                                <template x-if="!photoPreview && existingPhotoUrl">
                                    <img class="absolute inset-0 h-full w-full object-contain"
                                         :src="existingPhotoUrl">
                                </template>
                                {{-- Placeholder cuando no hay foto --}}
                                <div class="flex flex-col items-center justify-center pb-6 pt-5"
                                     x-show="!photoPreview && !existingPhotoUrl">
                                    <i class="fa-solid fa-cloud-arrow-up mb-3 text-3xl text-gray-400"></i>
                                    <p class="text-sm text-gray-500">
                                        <span class="font-semibold">{{ __('labels.click_image') }}</span> {{ __('labels.dropdown') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ __('labels.limits_img') }}</p>
                                </div>
                                {{-- Overlay upload Livewire (solo mientras sube el archivo) --}}
                                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/80"
                                     wire:loading
                                     wire:target="photo">
                                    <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-2 shadow-lg">
                                        <svg class="h-5 w-5 animate-spin text-indigo-600"
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
                                        <span class="text-sm font-bold text-gray-600">{{ __('labels.uploading_img') }}</span>
                                    </div>
                                </div>
                                <input id="dropzone-file"
                                       class="hidden"
                                       x-ref="photoInput"
                                       type="file"
                                       accept="image/*"
                                       wire:model.defer="photo"
                                       @change="onPhotoSelected($event)">
                            </label>
                        </div>
                        @error('photo')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>{{-- fin space-y-6 --}}

                {{-- Footer --}}
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <button class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-30"
                            @click="closeModal()"
                            :disabled="isSaving">
                        {{ __('labels.cancel') }}
                    </button>
                    <button class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-bold text-white shadow-md hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submit()"
                            :disabled="isSaving"
                            wire:loading.attr="disabled"
                            wire:target="createStrategy,updateStrategy">
                        <span x-text="isEditing ? '{{ __('labels.update_strategy') }}' : '{{ __('labels.create_strategy') }}'"></span>
                    </button>
                </div>

            </div>{{-- fin overflow-y-auto --}}
        </div>{{-- fin relative --}}
    </div>{{-- fin backdrop --}}



</div>
