<div class="max-w-fullxl mx-auto grid grid-cols-12"
     x-data="dashboard">
    {{-- MODAL GLOBAL (Gestiona Apertura/Cierre y Navegación Interna) --}}
    <div class="fixed inset-0 z-[150] overflow-y-auto"
         aria-labelledby="modal-title"
         x-show="showModalDetails"
         role="dialog"
         x-cloak
         aria-modal="true">

        {{-- Fondo oscuro (Backdrop) --}}
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">

            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="closeDayModal"
                 aria-hidden="true">
            </div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle"
                  aria-hidden="true">&#8203;</span>

            {{-- CONTENEDOR PRINCIPAL DEL MODAL --}}
            {{-- El detalle del trade lo abre el componente global <livewire:trade-detail-modal>. --}}
            <div class="inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:max-w-6xl sm:align-middle">

                {{-- ======================================================================= --}}
                {{-- VISTA 1: LISTADO DEL DÍA --}}
                <div class="flex h-full flex-col"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-x-5"
                     x-transition:enter-end="opacity-100 translate-x-0"> {{-- Clase h-full añadida para estructura --}}

                    {{-- 1. CABECERA (Se mantiene igual, ancho completo) --}}
                    <div class="flex-shrink-0 border-b border-gray-100 bg-white px-4 pb-4 pt-5 dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold leading-6 text-gray-900 dark:text-gray-100"> {{ __('labels.summary_day') }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d \d\e F \d\e Y') }}
                                </p>
                            </div>
                            <button class="rounded-full bg-gray-50 dark:bg-gray-900 p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-500 dark:hover:text-gray-400 dark:text-gray-300 focus:outline-none"
                                    @click="closeDayModal">
                                <i class="fa-solid fa-times text-lg"></i>
                            </button>
                        </div>
                    </div>

                    {{-- 2. CONTENIDO PRINCIPAL (GRID 2 COLUMNAS) --}}
                    <div class="flex-grow overflow-y-auto bg-gray-50 p-4 dark:bg-gray-900 sm:p-6">
                        <div class="grid h-full grid-cols-1 gap-6 lg:grid-cols-3">

                            {{-- COLUMNA IZQUIERDA: IA + TABLA (Ocupa 2 espacios) --}}
                            <div class="flex flex-col space-y-6 lg:col-span-2">

                                {{-- A. SECCIÓN COACH IA --}}
                                @if (Auth::user()->hasProAccess())
                                    <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm dark:border-indigo-900/40 dark:bg-gray-800">
                                        <div class="flex flex-col gap-4">
                                            <div class="flex items-center justify-between">
                                                <h4 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-indigo-900">
                                                    <i class="fa-solid fa-robot text-indigo-600"></i> {{ __('labels.intelligent_analysis') }}
                                                </h4>
                                                <p class="mt-1 text-[10px] font-medium text-gray-500 dark:text-gray-300">
                                                    {{ __('labels.daily_uses') }}
                                                    <span class="{{ $this->getAiCreditsLeft() > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        {{ $this->getAiCreditsLeft() }} / {{ $this->aiDailyLimit() }}
                                                    </span>
                                                </p>
                                                @if (!$aiAnalysis)
                                                    <button class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-indigo-700 disabled:opacity-50"
                                                            wire:click="analyzeDayWithAi"
                                                            wire:loading.attr="disabled">
                                                        <span class="flex items-center gap-2"
                                                              wire:loading.remove
                                                              wire:target="analyzeDayWithAi">
                                                            <span>{{ __('labels.analyse_day') }}</span> <i class="fa-solid fa-wand-magic-sparkles"></i>
                                                        </span>
                                                        <span class="flex items-center gap-2"
                                                              wire:loading
                                                              wire:target="analyzeDayWithAi">
                                                            <i class="fa-solid fa-circle-notch fa-spin"></i> {{ __('labels.analyzing') }}
                                                        </span>
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Loading Skeleton --}}
                                            <div class="animate-pulse space-y-2"
                                                 wire:loading
                                                 wire:target="analyzeDayWithAi">
                                                <div class="h-4 w-3/4 rounded bg-indigo-50"></div>
                                                <div class="h-4 w-1/2 rounded bg-indigo-50"></div>
                                            </div>

                                            {{-- Resultado IA --}}
                                            @if ($aiAnalysis)
                                                <div class="relative rounded-lg border border-indigo-100 bg-indigo-50/50 p-3">
                                                    <button class="absolute right-2 top-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 dark:text-gray-300"
                                                            wire:click="$set('aiAnalysis', null)"><i class="fa-solid fa-times"></i></button>
                                                    <div class="prose prose-sm max-w-none text-gray-800 dark:text-gray-100">{!! Str::markdown($aiAnalysis) !!}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif


                                {{-- B. TABLA DE OPERACIONES (Tu código original) --}}
                                <div class="flex flex-grow flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 shadow-sm">

                                    {{-- Spinner --}}
                                    <div class="w-full justify-center py-12 text-center"
                                         wire:loading.flex
                                         wire:target="openDayDetails">
                                        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent"></div>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-300"> {{ __('labels.loading') }}</p>
                                    </div>

                                    {{-- Tabla --}}
                                    <div class="max-h-[55vh] flex-grow overflow-x-auto"
                                         wire:loading.remove
                                         wire:target="openDayDetails">
                                        @if (count($this->dayTrades) > 0)
                                            <table class="h-full min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="sticky top-0 z-10 bg-gray-50 shadow-sm dark:bg-gray-900">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-300"> {{ __('labels.hour') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-300"> {{ __('labels.symbol') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-300"> {{ __('labels.type') }}</th>
                                                        <th class="w-32 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-300"> {{ __('labels.execution') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-300"> {{ __('labels.lots') }}</th>
                                                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-300"> {{ __('labels.result') }}</th>
                                                        <th class="px-4 py-3"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                                    @foreach ($this->dayTrades as $trade)
                                                        {{-- @dd($this->dayTrades) --}}
                                                        {{-- COPIAR AQUÍ TU TR DENTRO DEL FOREACH EXACTAMENTE COMO LO TENÍAS --}}
                                                        <tr class="group cursor-pointer transition hover:bg-indigo-50"
                                                            @click="$wire.selectTrade({{ $trade->id }})">
                                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">
                                                                {{ \Carbon\Carbon::parse($trade->exit_time)->format('H:i') }}
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-gray-900 dark:text-gray-100">
                                                                {{ $trade->tradeAsset->name ?? $trade->tradeAsset->symbol }}
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                                <span class="{{ $trade->direction == 'long' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }} rounded px-2 py-1 text-xs font-bold">
                                                                    {{ strtoupper($trade->direction) }}
                                                                </span>
                                                            </td>
                                                            {{-- BARRA MAE/MFE CON CÁLCULOS CORREGIDOS --}}
                                                            <td class="px-2 py-3 align-middle">
                                                                @if ($trade->mae_price && $trade->mfe_price)
                                                                    @php
                                                                        // 1. Distancias Absolutas
                                                                        $distMae = abs($trade->entry_price - $trade->mae_price);
                                                                        $distMfe = abs($trade->entry_price - $trade->mfe_price);
                                                                        $distReal = abs($trade->entry_price - $trade->exit_price);

                                                                        // 2. Rango Visual
                                                                        $totalRange = $distMae + $distMfe;
                                                                        $totalRange = $totalRange > 0 ? $totalRange : 0.00001;

                                                                        $pctRed = ($distMae / $totalRange) * 100;
                                                                        $pctGreen = ($distMfe / $totalRange) * 100;

                                                                        // 3. Posición Marcador
                                                                        $isBetterThanEntry = $trade->direction == 'long' ? $trade->exit_price >= $trade->entry_price : $trade->exit_price <= $trade->entry_price;

                                                                        if ($isBetterThanEntry) {
                                                                            $markerPos = $pctRed + ($distReal / $totalRange) * 100;
                                                                        } else {
                                                                            $markerPos = $pctRed - ($distReal / $totalRange) * 100;
                                                                        }
                                                                        $markerPos = max(0, min(100, $markerPos));

                                                                        // 4. CÁLCULO MONETARIO INTELIGENTE
                                                                        $maeMoney = 0;
                                                                        $mfeMoney = 0;

                                                                        // Umbral de fiabilidad: 2 pips (0.0002) aprox.
                                                                        // Si el precio se movió MENOS que esto, el PnL es mayormente comisiones/swap
                                                                        // y no sirve para calcular el valor del punto matemáticamente.
                                                                        if ($distReal > 0.0002) {
                                                                            // Cálculo exacto basado en lo que pasó
                                                                            $valuePerPoint = abs($trade->pnl) / $distReal;
                                                                        } else {
                                                                            // FALLBACK: Estimación basada en Lotes (Size)
                                                                            // Asumimos estándar Forex (100k unidades).
                                                                            // Si operas Índices/Crypto esto será una aproximación, pero mucho mejor que 0 o Infinito.
                                                                            $valuePerPoint = $trade->size * 100000;
                                                                        }

                                                                        // Aplicamos el valor del punto a las distancias MAE/MFE
                                                                        $maeMoney = $distMae * $valuePerPoint;
                                                                        $mfeMoney = $distMfe * $valuePerPoint;
                                                                    @endphp

                                                                    {{-- CONTENEDOR PRINCIPAL --}}
                                                                    <div class="group/bar relative mx-auto flex h-4 w-32 select-none items-center">

                                                                        {{-- A. CAPA VISUAL (Colores) --}}
                                                                        <div class="pointer-events-none absolute inset-x-0 flex h-1.5 overflow-hidden rounded-full">
                                                                            <div class="h-full bg-rose-400"
                                                                                 style="width: {{ $pctRed }}%"></div>
                                                                            <div class="z-10 h-full w-[2px] bg-white opacity-50"></div>
                                                                            <div class="h-full bg-emerald-400"
                                                                                 style="width: {{ $pctGreen }}%"></div>
                                                                        </div>

                                                                        {{-- B. TOOLTIPS INTERACTIVOS --}}
                                                                        <div class="absolute inset-0 flex h-full w-full items-center">
                                                                            {{-- ZONA ROJA --}}
                                                                            <div class="group/red relative h-4 cursor-help"
                                                                                 style="width: {{ $pctRed }}%">
                                                                                <div
                                                                                     class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-rose-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/red:block">
                                                                                    {{ __('labels.max_risk') }}: {{ number_format($maeMoney, 0) }} $
                                                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-rose-900"></div>
                                                                                </div>
                                                                            </div>

                                                                            {{-- ZONA VERDE --}}
                                                                            <div class="group/green relative h-4 cursor-help"
                                                                                 style="width: {{ $pctGreen }}%">
                                                                                <div
                                                                                     class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                                                    {{ __('labels.max_potencial') }}: +{{ number_format($mfeMoney, 0) }} $
                                                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-emerald-900"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        {{-- C. MARCADOR DE SALIDA --}}
                                                                        <div class="pointer-events-none absolute z-20 h-2.5 w-1 rounded-full bg-gray-900 shadow-sm ring-1 ring-white"
                                                                             style="left: {{ $markerPos }}%; transform: translateX(-50%);">
                                                                        </div>

                                                                    </div>
                                                                @else
                                                                    <span class="block text-center text-xs text-gray-300 dark:text-gray-600">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-300">{{ $trade->size }}</td>
                                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                                {{-- Contenedor del PnL --}}
                                                                <div class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-mono font-black"
                                                                     {{-- x-data vacío para inicializar el ámbito si no lo hereda --}}
                                                                     x-data>
                                                                    <span x-text="$store.viewMode.format({{ $trade->pnl }}, {{ $trade->pnl_percentage ?? 0 }})">
                                                                        {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                                                    </span>
                                                                </div>


                                                            </td>
                                                            {{-- <td class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} whitespace-nowrap px-4 py-3 text-right text-sm font-bold">
                                                                {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                                            </td> --}}
                                                            <td class="px-4 py-3 text-right text-gray-300 dark:text-gray-600">
                                                                <i class="fa-solid fa-chevron-right group-hover:text-indigo-600"></i>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="sticky bottom-0 z-10 border-t border-gray-200 bg-gray-50 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                                    @php
                                                        $count = $this->dayTrades->count();
                                                        // Lógica de colores para el contador: <3 verde, <6 naranja, >=6 rojo
                                                        $countColor = $count < 3 ? 'text-emerald-600' : ($count < 6 ? 'text-orange-500' : 'text-rose-600');

                                                        $totalPnl = $this->dayTrades->sum('pnl');
                                                        // Suma, no media: la casilla del calendario de ese mismo día
                                                        // suma, y con la media el modal daba otra cifra distinta.
                                                        $totalPnlPct = $this->dayTrades->sum('pnl_percentage');
                                                    @endphp
                                                    <tr>
                                                        {{-- 1. Título de Operaciones (Colspan 3 para ocupar Hour, Symbol, Type) --}}
                                                        <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500 dark:text-gray-300"
                                                            colspan="3">
                                                            {{ __('labels.total_trades') }}
                                                        </td>

                                                        {{-- 2. El Contador (Ocupa la columna de Execution) --}}
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="{{ $countColor }} font-mono font-black">
                                                                {{ $count }}
                                                            </span>
                                                        </td>

                                                        {{-- 3. Título Total PnL (Ocupa la columna de Lots) --}}
                                                        <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500 dark:text-gray-300">
                                                            {{ __('labels.total') }}
                                                        </td>

                                                        {{-- 4. El PnL acumulado (Ocupa la columna de Result) --}}
                                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                                            <div class="{{ $totalPnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-mono font-black"
                                                                 x-data>
                                                                <span x-text="$store.viewMode.format({{ $totalPnl }}, {{ $totalPnlPct }})">
                                                                    {{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl, 2) }} $
                                                                </span>
                                                            </div>
                                                        </td>

                                                        {{-- 5. Columna final vacía (Para alinear con la flecha de la derecha) --}}
                                                        <td class="px-4 py-3"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        @else
                                            <div class="py-12 text-center">
                                                <i class="fa-solid fa-box-open mb-3 text-4xl text-gray-300 dark:text-gray-600"></i>
                                                <h3 class="text-gray-500 dark:text-gray-300">{{ __('labels.without_operations') }}</h3>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- COLUMNA DERECHA: JOURNAL (NUEVO) --}}
                            <div class="flex h-full flex-col lg:col-span-1">
                                @if ($selectedDate)
                                    <livewire:daily-journal :date="$selectedDate"
                                                            :wire:key="'journal-'.$selectedDate" />
                                @endif
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    {{-- CONTENEDOR PRINCIPAL CON ESTADO ALPINE --}}
    <div x-data="{
        initialLoad: true,
        init() {
            // Cuando Livewire termine de cargar sus scripts y efectos, quitamos el loader
            document.addEventListener('livewire:initialized', () => {
                this.initialLoad = false;
            });
    
            // Fallback de seguridad: por si Livewire ya cargó antes de este script
            setTimeout(() => { this.initialLoad = false }, 800);
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
         wire:target='calculateStats, updatedSelectedAccounts'>
        <x-loader></x-loader>
    </div>

    {{-- ? Loading JS --}}
    <div x-show="showLoading">
        <x-loader></x-loader>
    </div>

    {{-- El hueco para el navbar fijo lo pone ahora dashboard.blade.php (pt-16), que
         es lo primero de la página. Tenerlo aquí dejaba las tarjetas de puesta en
         marcha por encima de él, debajo de la barra. --}}
    <header class="relative top-0 z-10 col-span-12 flex w-auto justify-between bg-white px-6 py-2 shadow transition-colors dark:bg-gray-800 dark:shadow-black/30">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-2xl text-indigo-600"></i>
                <h1 class="text-3xl font-black text-gray-900 dark:text-gray-100">{{ __('menu.dashboard') }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-300">{{ __('menu.resume_dashboard') }}</p>
        </div>
        @if (Auth::user()->hasProAccess())
            <livewire:ai-daily-tip :selected-accounts="$selectedAccounts" />
        @endif

        {{-- 👇 Date Range Picker — wire:ignore previene que Livewire destruya el DOM --}}
        <div class="flex items-center gap-2"
             wire:ignore
             x-data="{
                 fp: null,
                 label: '{{ !empty($dateFrom) && !empty($dateTo) ? $dateFrom . ' → ' . $dateTo : '' }}',
                 hasFilter: {{ !empty($dateFrom) && !empty($dateTo) ? 'true' : 'false' }},
             
                 init() {
                     const self = this;
             
                     self.fp = flatpickr(self.$refs.fpInput, {
                         mode: 'range',
                         dateFormat: 'Y-m-d',
                         locale: '{{ app()->getLocale() === 'es' ? 'es' : 'default' }}',
                         disableMobile: true,
                         positionElement: self.$refs.triggerBtn, // ← ancla al botón visible
             
                         @if (!empty($dateFrom) && !empty($dateTo)) defaultDate: ['{{ $dateFrom }}', '{{ $dateTo }}'], @endif
             
                         onChange(selectedDates) {
                             if (selectedDates.length === 2) {
                                 const from = selectedDates[0].toISOString().split('T')[0];
                                 const to = selectedDates[1].toISOString().split('T')[0];
                                 self.label = from + ' → ' + to;
                                 self.hasFilter = true;
                                 $wire.applyDateRange(from, to);
                             }
                         },
                     });
             
                 },
             
                 open() {
                     if (this.fp) this.fp.open();
                 },

                 // Presets rápidos: reutilizan el flujo de flatpickr (setDate dispara onChange → applyDateRange)
                 preset(key) {
                     const to = new Date();
                     let from = new Date();

                     if (key === 'ytd') {
                         from = new Date(to.getFullYear(), 0, 1);
                     } else if (key !== 'today') {
                         from.setDate(to.getDate() - (key - 1));
                     }

                     if (this.fp) this.fp.setDate([from, to], true);
                 },

                 clear() {
                     if (this.fp) {
                         this.fp.clear();
                         this.fp.close();
                     }
                     this.label = '';
                     this.hasFilter = false;
                     $wire.clearDateRange();
                 }
             }">
            {{-- Input oculto que controla Flatpickr --}}
            <input class="absolute h-0 w-0 overflow-hidden border-0 p-0 opacity-0"
                   x-ref="fpInput"
                   type="text"
                   readonly
                   tabindex="-1" />

            {{-- Presets de rango rápido --}}
            <div class="flex h-9 items-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-300 shadow-sm">
                <button class="h-full px-2.5 transition hover:bg-indigo-50 hover:text-indigo-600"
                        type="button"
                        @click="preset('today')">{{ __('labels.preset_today') }}</button>
                <button class="h-full border-l border-gray-200 px-2.5 transition hover:bg-indigo-50 hover:text-indigo-600 dark:border-gray-700 dark:hover:bg-indigo-500/10"
                        type="button"
                        @click="preset(7)">7D</button>
                <button class="h-full border-l border-gray-200 px-2.5 transition hover:bg-indigo-50 hover:text-indigo-600 dark:border-gray-700 dark:hover:bg-indigo-500/10"
                        type="button"
                        @click="preset(30)">30D</button>
                <button class="h-full border-l border-gray-200 px-2.5 transition hover:bg-indigo-50 hover:text-indigo-600 dark:border-gray-700 dark:hover:bg-indigo-500/10"
                        type="button"
                        @click="preset(90)">90D</button>
                <button class="h-full border-l border-gray-200 px-2.5 transition hover:bg-indigo-50 hover:text-indigo-600 dark:border-gray-700 dark:hover:bg-indigo-500/10"
                        type="button"
                        @click="preset('ytd')">{{ __('labels.preset_ytd') }}</button>
            </div>

            {{-- Botón trigger --}}
            <button class="flex h-9 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 px-3 text-sm text-gray-600 dark:text-gray-300 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600"
                    @click="open()"
                    x-ref="triggerBtn"
                    type="button">
                <i class="fa-regular fa-calendar-range text-indigo-500"></i>
                <span x-text="hasFilter ? label : '{{ __('labels.date_range') }}'"></span>
                <i class="fa-solid fa-chevron-down text-xs text-gray-400 dark:text-gray-500"></i>
            </button>

            {{-- Botón limpiar --}}
            <button class="flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 transition hover:bg-red-100"
                    x-show="hasFilter"
                    x-cloak
                    @click="clear()"
                    type="button"
                    title="{{ __('labels.clear_filter') }}">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>


        <x-input-multiselect wire:model="selectedAccounts"
                             :options="$availableAccounts"
                             placeholder="{{ __('labels.select_accounts') }}"
                             icono='<i class="fa-solid fa-users-viewfinder"></i>' />
    </header>


    {{-- RACHAS Y DISCIPLINA (R3). Va aquí dentro, bajo la cabecera del panel,
         porque es contenido permanente: arriba del todo solo se quedan las
         tarjetas que desaparecen solas (ritual, puesta en marcha, cuenta quemada). --}}
    <div class="col-span-12 px-4 pt-4 sm:px-6 lg:px-8">
        <x-streaks-card />
    </div>

    {{-- COSTE DE LOS ERRORES (P2). Debajo de las rachas: las rachas dicen cómo vas,
         esto dice cuánto te está costando lo que ya sabes que haces mal. --}}
    <div class="col-span-12 px-4 pt-3 sm:px-6 lg:px-8">
        <x-mistake-cost-card :data="$this->mistakeCost" />
    </div>


    <div class="col-span-12 grid grid-cols-12 gap-3 sm:px-6 sm:py-4 lg:px-8 lg:py-6">
        {{-- WIDGET: FLASH DE LECCIONES (Notas Recientes) --}}
        @if ($this->recentNotes)
            <div class="col-span-9 flex h-full flex-col overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center gap-2 pb-2">
                    <div class="rounded-lg bg-yellow-100 p-1.5 text-yellow-600">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('labels.last_notes') }}</h3>
                </div>
                {{-- Cuerpo: Scroll Horizontal --}}
                <div class="flex flex-row gap-4 overflow-x-auto"> {{-- pb-6 da espacio para la barra de scroll si aparece --}}
                    @forelse($this->recentNotes as $noteTrade)
                        {{-- Tarjeta: Ancho fijo y flex-shrink-0 para que no se aplasten --}}
                        <div class="bg--gray-50 group relative flex min-w-[250px] max-w-[250px] flex-shrink-0 cursor-pointer flex-col justify-between rounded-2xl border border-gray-200 p-6 shadow-sm transition-all hover:border-yellow-200 hover:bg-yellow-50 hover:shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-yellow-900/20"
                             wire:click="openTradeFromNotes({{ $noteTrade->id }})">

                            <div>
                                <div class="mb-2 flex items-start justify-between">
                                    <span class="flex items-center gap-1 text-xs font-bold text-gray-900 dark:text-gray-100">
                                        {{ $noteTrade->tradeAsset->name ?? $noteTrade->symbol }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $noteTrade->exit_time->diffForHumans(null, true, true) }}</span>
                                </div>

                                {{-- Badge Resultado --}}
                                <div class="mb-2">
                                    <span class="{{ $noteTrade->pnl >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded px-1.5 py-0.5 text-[10px] font-bold">
                                        {{ $noteTrade->pnl >= 0 ? 'WIN' : 'LOSS' }}
                                    </span>
                                </div>

                                <p class="line-clamp-3 text-xs italic leading-relaxed text-gray-600 dark:text-gray-300">
                                    "{{ $noteTrade->notes }}"
                                </p>
                            </div>

                            {{-- Decoración opcional al hacer hover --}}
                            <div class="mt-2 text-right opacity-0 transition-opacity group-hover:opacity-100">
                                <i class="fa-solid fa-arrow-right text-xs text-yellow-600"></i>
                            </div>
                        </div>
                    @empty
                        <div class="flex w-full flex-col items-center justify-center py-8 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-regular fa-clipboard mb-2 text-2xl opacity-50"></i>
                            <p class="text-xs">{{ __('labels.not_notes') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- 3. PLAN DIARIO (Cuarto de pantalla - NUEVO) --}}
        <div class="col-span-3 flex flex-col justify-center rounded-3xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-5 shadow-sm lg:col-span-3">

            <div class="mb-4 flex items-center gap-2">
                <div class="rounded-lg bg-indigo-100 p-1.5 text-indigo-600"><i class="fa-solid fa-crosshairs"></i></div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ __('labels.daily_objective') }}</h3>
            </div>

            @if ($planStatus)
                <div class="space-y-4">
                    {{-- 1. META (PnL Target) --}}
                    @if ($planStatus['pnl']['target'])
                        <div class="space-y-2">
                            <div class="flex items-end justify-between text-[10px] font-bold uppercase tracking-wider">
                                <span class="italic text-gray-500 dark:text-gray-300">{{ __('labels.profit_target') }}</span>
                                <span class="font-mono text-xs text-emerald-600">{{ number_format($planStatus['pnl']['target'], 0) }} $</span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner dark:bg-gray-700">
                                <div class="h-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)] transition-all duration-700 ease-out"
                                     style="width: {{ $planStatus['pnl']['progress'] }}%"></div>
                            </div>

                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ number_format($planStatus['pnl']['progress'], 0) }}%</span>
                                <div class="{{ $planStatus['pnl']['current'] >= 0 ? 'text-emerald-600' : 'text-rose-500' }} font-mono text-xs font-black">
                                    {{ $planStatus['pnl']['current'] >= 0 ? '+' : '' }}{{ number_format($planStatus['pnl']['current'], 2) }} $
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 2. LÍMITE DE PÉRDIDA (Drawdown) --}}
                    @if ($planStatus['pnl']['limit'])
                        @php
                            $limitPnl = abs($planStatus['pnl']['limit']);
                            $currentLoss = $planStatus['pnl']['current'] < 0 ? abs($planStatus['pnl']['current']) : 0;
                            $pctLoss = min(100, ($currentLoss / $limitPnl) * 100);
                            $isOverLoss = $pctLoss >= 100;
                        @endphp
                        <div class="space-y-2">
                            <div class="flex items-end justify-between text-[10px] font-bold uppercase tracking-wider">
                                <span class="italic text-gray-500 dark:text-gray-300">{{ __('labels.max_loss_limit') }}</span>
                                <span class="font-mono text-xs text-rose-500">{{ number_format($planStatus['pnl']['limit'], 0) }} $</span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner dark:bg-gray-700">
                                <div class="{{ $isOverLoss ? 'bg-rose-600 animate-pulse' : 'bg-rose-400' }} h-full transition-all duration-500"
                                     style="width: {{ $pctLoss }}%"></div>
                            </div>

                            @if ($isOverLoss)
                                <div class="mt-1 flex animate-bounce items-center justify-center gap-1">
                                    <span class="text-[10px]">⛔</span>
                                    <p class="text-[10px] font-black uppercase tracking-tighter text-rose-600">{{ __('labels.advice_max_loss') }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- 3. LÍMITE DE OPERACIONES (Daily Plan) --}}
                    @if (isset($planStatus['trades']['limit']) && $planStatus['trades']['limit'] > 0)
                        @php
                            $limitTrades = (int) $planStatus['trades']['limit'];
                            $currentTrades = (int) $planStatus['trades']['current'];
                            $pctTrades = min(100, ($currentTrades / $limitTrades) * 100);
                            $isFull = $currentTrades >= $limitTrades;
                            $isWarning = $currentTrades == $limitTrades - 1;
                        @endphp

                        <div class="space-y-2">
                            <div class="flex items-end justify-between">
                                <div class="text-[10px] font-bold uppercase italic tracking-wider text-gray-500 dark:text-gray-300">{{ __('labels.daily_plan') }}</div>
                                <div class="{{ $isFull ? 'text-rose-600' : ($isWarning ? 'text-orange-500' : 'text-emerald-500') }} font-mono text-[10px] font-bold">
                                    {{ number_format($pctTrades, 0) }}%
                                </div>
                            </div>

                            <div class="relative h-2 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner dark:bg-gray-700">
                                <div class="{{ $isFull ? 'bg-rose-600 animate-pulse' : ($isWarning ? 'bg-orange-400' : 'bg-emerald-400') }} h-full transition-all duration-500"
                                     style="width: {{ $pctTrades }}%">
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="font-mono text-[11px] font-black text-gray-700 dark:text-gray-200">
                                    {{ $currentTrades }} <span class="font-normal italic text-gray-400 dark:text-gray-500">/ {{ $limitTrades }} {{ __('labels.ops') }}</span>
                                </p>

                                @if ($isFull)
                                    <p class="flex items-center gap-1 text-[9px] font-black uppercase text-rose-600">
                                        <span class="inline-flex h-1.5 w-1.5 animate-ping rounded-full bg-rose-600 opacity-75"></span>
                                        {{ __('labels.limit_reached') }}
                                    </p>
                                @elseif($isWarning)
                                    <p class="text-[9px] font-bold uppercase italic text-orange-500">{{ __('labels.last_bullet') }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="py-4 text-center">
                    <p class="mb-2 text-xs text-gray-400 dark:text-gray-500">{{ __('labels.not_rules_configured') }}</p>
                    <a class="text-xs font-bold text-indigo-600 hover:underline"
                       href="{{ route('cuentas') }}">{{ __('labels.configure_in_accounts') }}</a>
                </div>
            @endif
        </div>


        {{-- GRID DE STATS --}}
        <div class="col-span-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">


            {{-- CARD: PNL  --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm">
                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.win_pnl') }}</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900 dark:text-gray-100">
                            <span class="{{ $pnlTotal >= 0 ? 'text-emerald-600' : 'text-rose-600' }}"
                                  x-text="$store.viewMode.format({{ $pnlTotal }}, {{ $pnlTotal_perc ?? 0 }})"></span>

                        </div>

                        {{-- Comparativa vs periodo anterior (solo con rango de fechas activo) --}}
                        @if ($comparison)
                            <span class="{{ $comparison['pnl_diff'] >= 0 ? 'text-emerald-600' : 'text-rose-500' }} mt-1 inline-flex items-center gap-1 text-xs font-bold"
                                  title="{{ __('labels.prev_period') }}: {{ $comparison['prev_label'] }} ({{ number_format($comparison['pnl_prev'], 2) }} $)">
                                <i class="fa-solid {{ $comparison['pnl_diff'] >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                                {{ ($comparison['pnl_diff'] >= 0 ? '+' : '') . number_format($comparison['pnl_diff'], 2) }} $
                                <span class="font-normal text-gray-400 dark:text-gray-500">{{ __('labels.vs_prev_period') }}</span>
                            </span>
                        @endif
                    </div>


                </div>
            </div>

            {{-- CARD: WIN RATE --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm"
                 wire:ignore>

                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.trade_winrate') }}</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900 dark:text-gray-100"
                             x-text="$wire.winRateChartData?.rate + '%'">
                            0%
                        </div>

                        {{-- Comparativa vs periodo anterior (vía $wire: la tarjeta es wire:ignore) --}}
                        <span class="mt-1 inline-flex items-center gap-1 text-xs font-bold"
                              x-show="$wire.comparison"
                              x-cloak
                              :class="($wire.comparison?.wr_diff ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                            <i class="fa-solid"
                               :class="($wire.comparison?.wr_diff ?? 0) >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'"></i>
                            <span x-text="(($wire.comparison?.wr_diff ?? 0) >= 0 ? '+' : '') + ($wire.comparison?.wr_diff ?? 0) + ' pts'"></span>
                            <span class="font-normal text-gray-400 dark:text-gray-500">{{ __('labels.vs_prev_period') }}</span>
                        </span>
                    </div>

                    {{-- DERECHA: Gráfico y Pastillas --}}
                    <div class="flex flex-col items-center">

                        {{-- El Gráfico (Semi Donut) --}}
                        {{-- Importante: altura fija y width fijo para que no se expanda --}}
                        <div class="flex h-[70px] w-[120px] justify-center"
                             x-ref="winRateChart"></div>

                        {{-- Las Pastillas (Contadores) --}}
                        <div class="relative -mt-16 flex gap-3">
                            {{-- Wins --}}
                            <div class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600"
                                 x-text="$wire.winRateChartData?.count_wins">
                                0
                            </div>
                            {{-- (Opcional) Break Even / Ceros --}}
                            <div class="rounded-full bg-gray-50 px-2 py-0.5 text-xs font-bold text-gray-400 dark:text-gray-500 dark:bg-gray-700">
                                0
                            </div>
                            {{-- Losses --}}
                            <div class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600"
                                 x-text="$wire.winRateChartData?.count_losses">
                                0
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- CARD: AVG WIN / LOSS TRADE (Estilo Barra de Progreso) --}}
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm"
                 {{-- Inicializamos datos locales para calcular anchos --}}
                 x-data="{
                     get win() { return $wire.avgPnLChartData?.avg_win || 0; },
                     get loss() { return Math.abs($wire.avgPnLChartData?.avg_loss || 0); },
                     get ratio() { return $wire.avgPnLChartData?.rr_ratio || 0; },
                 
                     // Calculamos el % de ancho de la barra verde
                     get winPct() {
                         let total = this.win + this.loss;
                         if (total === 0) return 50; // 50/50 si está vacío
                         return (this.win / total) * 100;
                     }
                 }">

                <div class="flex h-full items-center justify-between">

                    {{-- IZQUIERDA: Título y Ratio Grande --}}
                    <div class="flex min-w-[100px] flex-col justify-center">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.avg_rr') }}</h3>
                            <i class="fa-regular fa-circle-question text-xs text-gray-400 dark:text-gray-500"
                               title="{{ __('labels.title_r_r') }}"></i>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-gray-100"
                             x-text="ratio">
                            0
                        </div>
                    </div>

                    {{-- DERECHA: La Barra Visual --}}
                    <div class="ml-4 flex flex-1 flex-col justify-center">

                        {{-- 1. La Barra (Visual) --}}
                        <div class="flex h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            {{-- Parte Verde (Ganancia) --}}
                            <div class="h-full bg-emerald-500 transition-all duration-1000 ease-out"
                                 :style="'width: ' + winPct + '%'">
                            </div>
                            {{-- Parte Roja (Pérdida) - Ocupa el resto --}}
                            <div class="h-full flex-1 bg-rose-400 transition-all duration-1000 ease-out"></div>
                        </div>

                        {{-- 2. Las Etiquetas (Debajo) --}}
                        <div class="mt-2 flex justify-between font-mono text-xs font-bold">
                            {{-- Texto Verde (Alineado a la izq) --}}
                            <div class="text-emerald-600"
                                 x-text="new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(win)">
                                0 $
                            </div>

                            {{-- Texto Rojo (Alineado a la der) --}}
                            <div class="text-rose-500"
                                 x-text="'-' + new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(loss)">
                                0 $
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- CARD: DÍAS GANADORES VS PERDEDORES --}}

            <div class="content-center rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm"
                 wire:ignore>

                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.daily_winrate') }}</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900 dark:text-gray-100"
                             x-text="$wire.dailyWinLossData?.rate + '%'">
                            0%
                        </div>
                    </div>

                    {{-- DERECHA: Gráfico y Pastillas --}}
                    <div class="flex flex-col items-center">

                        {{-- El Gráfico (Semi Donut) --}}
                        {{-- Importante: altura fija y width fijo para que no se expanda --}}
                        <div class="flex h-[70px] w-[120px] justify-center"
                             x-ref="dailyWinLossChart"></div>

                        {{-- Las Pastillas (Contadores) --}}
                        <div class="relative -mt-16 flex gap-3">
                            {{-- Wins --}}
                            <div class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600"
                                 x-text="$wire.dailyWinLossData?.count_wins">
                                0
                            </div>
                            {{-- (Opcional) Break Even / Ceros --}}
                            <div class="rounded-full bg-gray-50 px-2 py-0.5 text-xs font-bold text-gray-400 dark:text-gray-500 dark:bg-gray-700">
                                0
                            </div>
                            {{-- Losses --}}
                            <div class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600"
                                 x-text="$wire.dailyWinLossData?.count_losses">
                                0
                            </div>
                        </div>
                    </div>

                </div>
            </div>


            {{-- CARD: RENDIMIENTO (Profit Factor + Expectancy + Racha) --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm">
                <h3 class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.performance') }}</h3>
                <div class="grid grid-cols-2 gap-x-2 gap-y-3">
                    {{-- Profit Factor --}}
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-400 dark:text-gray-500"
                              title="{{ __('labels.profit_factor_help') }}">{{ __('labels.profit_factor') }}</span>
                        @php $pf = $extraKpis['profit_factor'] ?? 0; @endphp
                        <span class="{{ $pf === null || $pf >= 1 ? 'text-emerald-600' : 'text-rose-500' }} text-xl font-black tabular-nums">
                            {{ $pf === null ? '∞' : number_format($pf, 2) }}
                        </span>
                    </div>
                    {{-- Expectancy --}}
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-400 dark:text-gray-500"
                              title="{{ __('labels.expectancy_help') }}">{{ __('labels.expectancy') }}</span>
                        @php $exp = $extraKpis['expectancy'] ?? 0; @endphp
                        <span class="{{ $exp >= 0 ? 'text-emerald-600' : 'text-rose-500' }} text-xl font-black tabular-nums">
                            {{ ($exp >= 0 ? '+' : '') . number_format($exp, 2) }} $
                        </span>
                    </div>
                    {{-- Racha actual --}}
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('labels.current_streak') }}</span>
                        @php $streak = $extraKpis['streak'] ?? ['type' => null, 'count' => 0]; @endphp
                        @if ($streak['type'])
                            <span class="{{ $streak['type'] === 'win' ? 'text-emerald-600' : 'text-rose-500' }} text-xl font-black tabular-nums">
                                {{ $streak['count'] }}{{ $streak['type'] === 'win' ? 'W' : 'L' }}
                                <i class="fa-solid {{ $streak['type'] === 'win' ? 'fa-fire' : 'fa-snowflake' }} text-sm"></i>
                            </span>
                        @else
                            <span class="text-xl font-black text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </div>
                    {{-- Max Drawdown --}}
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-400 dark:text-gray-500"
                              title="{{ __('labels.max_drawdown_help') }}">{{ __('labels.max_drawdown') }}</span>
                        @php $dd = $extraKpis['max_drawdown'] ?? 0; @endphp
                        <span class="{{ $dd > 0 ? 'text-rose-500' : 'text-gray-300 dark:text-gray-600' }} text-xl font-black tabular-nums">
                            {{ $dd > 0 ? '-' . number_format($dd, 2) . ' $' : '0 $' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- CARD: RENDIMIENTO POR ACTIVO (navegable, ordenado de mejor a peor PnL) --}}
            @if (!empty($assetBreakdown['all']))
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm md:col-span-2 lg:col-span-2"
                     wire:key="asset-breakdown-{{ md5(json_encode($assetBreakdown['all'])) }}"
                     x-data="{
                        i: 0,
                        items: @js($assetBreakdown['all']),
                        get item() { return this.items[this.i] },
                        prev() { this.i = (this.i - 1 + this.items.length) % this.items.length },
                        next() { this.i = (this.i + 1) % this.items.length },
                        money(v) { return (v >= 0 ? '+' : '') + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $' },
                     }">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">
                            <i class="fa-solid fa-chart-simple mr-1 text-indigo-400"></i>{{ __('labels.asset_performance') }}
                        </h3>
                        {{-- Navegación: solo aparece si hay más de un activo --}}
                        <div class="flex items-center gap-1" x-show="items.length > 1" x-cloak>
                            <button type="button" @click="prev()" title="{{ __('labels.previous') }}"
                                    class="rounded-md px-1.5 py-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </button>
                            <span class="text-xs tabular-nums text-gray-400 dark:text-gray-500" x-text="(i + 1) + ' / ' + items.length"></span>
                            <button type="button" @click="next()" title="{{ __('labels.next') }}"
                                    class="rounded-md px-1.5 py-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-700">
                        <span class="flex items-center gap-2 truncate font-semibold text-gray-700 dark:text-gray-200">
                            <span x-text="item.asset"></span>
                            <span x-cloak x-show="items.length > 1 && i === 0"
                                  class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                                {{ __('labels.best') }}
                            </span>
                            <span x-cloak x-show="items.length > 1 && i === items.length - 1"
                                  class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-500 dark:bg-rose-900/40 dark:text-rose-400">
                                {{ __('labels.worst') }}
                            </span>
                        </span>
                        <span class="text-lg font-black tabular-nums"
                              :class="item.pnl >= 0 ? 'text-emerald-600' : 'text-rose-500'"
                              x-text="money(item.pnl)"></span>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('labels.trades') }}</p>
                            <p class="font-bold tabular-nums text-gray-700 dark:text-gray-200" x-text="item.trades"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('labels.win_rate') }}</p>
                            <p class="font-bold tabular-nums text-gray-700 dark:text-gray-200">
                                <span x-text="item.win_rate.toFixed(1) + '%'"></span>
                                <span class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                      x-text="'(' + item.wins + '/' + item.losses + ')'"></span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('labels.avg_pnl_trade') }}</p>
                            <p class="font-bold tabular-nums"
                               :class="item.avg_pnl >= 0 ? 'text-emerald-600' : 'text-rose-500'"
                               x-text="money(item.avg_pnl)"></p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- CARD: MEJOR / PEOR OPERACIÓN (rellena el hueco; usa best_trade/worst_trade ya calculados) --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm">
                <h3 class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.best_worst_trade') }}</h3>
                @php
                    $best = $extraKpis['best_trade'] ?? null;
                    $worst = $extraKpis['worst_trade'] ?? null;
                @endphp
                @if ($best !== null || $worst !== null)
                    <div class="space-y-3">
                        {{-- Mejor --}}
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <i class="fa-solid fa-arrow-trend-up text-emerald-500"></i>{{ __('labels.best_trade') }}
                            </span>
                            <span class="font-black tabular-nums text-emerald-600">
                                {{ $best !== null ? ($best >= 0 ? '+' : '') . number_format($best, 2) : '—' }} $
                            </span>
                        </div>
                        {{-- Peor --}}
                        <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
                            <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <i class="fa-solid fa-arrow-trend-down text-rose-500"></i>{{ __('labels.worst_trade') }}
                            </span>
                            <span class="font-black tabular-nums text-rose-500">
                                {{ $worst !== null ? number_format($worst, 2) : '—' }} $
                            </span>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('labels.no_trades_period') }}</p>
                @endif
            </div>

        </div>

        {{-- CARD: GRÁFICO DE EVOLUCIÓN PNL --}}
        <div class="col-span-6 rounded-3xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-4 shadow-sm"
             wire:ignore>
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('labels.cumulative_yield_curve') }}</h3>

            </div>

            {{-- Contenedor del Gráfico --}}
            <div class="h-[200px] w-full min-w-0"
                 x-ref="evolutionChart"></div>
        </div>

        {{-- CARD: PNL DIARIO (BARRAS) --}}
        <div class="col-span-6 rounded-3xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-4 shadow-sm"
             wire:ignore>
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('labels.pnl_day_clean') }}</h3>
                {{-- Leyenda Simple --}}
                <div class="flex gap-3 text-xs font-medium text-gray-600 dark:text-gray-300">
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> {{ __('labels.profit') }}</div>
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span>{{ __('labels.loss') }}</div>
                </div>
            </div>

            {{-- Contenedor del Gráfico --}}
            <div class="h-[200px] w-full"
                 x-ref="dailyPnLBarChart"></div>
        </div>

        {{-- CARD: HEATMAP TEMPORAL --}}
        <div class="col-span-12 rounded-3xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm"
             wire:ignore>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('labels.heatmap') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ __('labels.cumulative_yield_hour') }}</p>
                </div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('labels.legend_simple') }}</span>
                <div class="flex items-center gap-2 text-xs">
                    <span class="rounded bg-rose-500 px-2 py-1 text-white">{{ __('labels.loss') }}</span>
                    <span class="rounded bg-gray-100 px-2 py-1 text-gray-500 dark:bg-gray-700 dark:text-gray-300">{{ __('labels.neutral') }}</span>
                    <span class="rounded bg-emerald-500 px-2 py-1 text-white">{{ __('labels.profit') }}</span>
                </div>
            </div>

            {{-- Contenedor Gráfico --}}
            <div class="h-[350px] w-full"
                 x-ref="heatmapChart"></div>
        </div>

        <div class="col-span-5 flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 shadow-sm">

            {{-- Cabecera de la Tarjeta --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                    {{ __('labels.recent_operations') }}
                </h3>

                {{-- Indicador de carga sutil --}}
                <div wire:loading
                     wire:target="selectedAccounts">
                    <i class="fa-solid fa-circle-notch fa-spin text-indigo-500"></i>
                </div>
            </div>

            {{-- Cuerpo de la Tabla --}}
            <div class="flex-grow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                scope="col">
                                {{ __('labels.date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                scope="col">
                                {{ __('labels.active') }}
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                scope="col">
                                {{ __('labels.type') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                scope="col">
                                {{ __('labels.PnL') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse ($this->recentTrades as $trade)
                            <tr class="group cursor-pointer transition duration-150 hover:bg-indigo-50/60"
                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300"
                                {{-- 
                            openTradeFromTable() valida el id y despacha 'open-trade-detail',
                            que escucha el componente global <livewire:trade-detail-modal>.
                        --}}
                                wire:click="openTradeFromTable({{ $trade->id }})">


                                {{-- 1. Fecha Cierre --}}
                                <td class="flex whitespace-nowrap px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ \Carbon\Carbon::parse($trade->exit_time)->format('d-m-Y H:i') }}
                                    </span>
                                    {{-- NUEVO: ICONO DE NOTA CON TOOLTIP --}}
                                    @if ($trade->notes)
                                        <div class="group relative ml-1"
                                             @click.stop> {{-- click.stop evita abrir el modal si solo quieres ver el tooltip --}}
                                            <i class="fa-solid fa-note-sticky cursor-help text-yellow-400 hover:text-yellow-600"></i>

                                            {{-- Tooltip Flotante --}}
                                            <div class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 hidden w-48 -translate-x-1/2 rounded-lg bg-gray-900 p-2 text-xs text-white shadow-xl group-hover:block">
                                                <p class="line-clamp-3 italic">"{{ $trade->notes }}"</p>
                                                {{-- Flechita del tooltip --}}
                                                <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                {{-- 2. Símbolo (Ej: EURUSD) --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ $trade->tradeAsset->name ?? $trade->tradeAsset->symbol }}
                                    </span>

                                </td>

                                {{-- 3. Tipo (Badge) --}}
                                <td class="whitespace-nowrap px-6 py-4 text-center">
                                    @if ($trade->direction == 'long')
                                        <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            {{ __('labels.long') }} <i class="fa-solid fa-arrow-trend-up ml-1"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            {{ __('labels.short') }} <i class="fa-solid fa-arrow-trend-down ml-1"></i>
                                        </span>
                                    @endif
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
                            </tr>
                        @empty
                            {{-- Estado Vacío --}}
                            <tr>
                                <td class="py-12 text-center"
                                    colspan="4">
                                    <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                        <div class="mb-3 rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                                            <i class="fa-solid fa-chart-simple text-2xl text-gray-300 dark:text-gray-600"></i>
                                        </div>
                                        <p class="text-sm font-medium">{{ __('labels.not_recent_operations') }}</p>
                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('labels.new_operations_appear_here') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer opcional --}}
            <div class="border-t border-gray-100 bg-gray-50 px-6 py-3 text-right dark:border-gray-700 dark:bg-gray-900">
                <a class="text-xs font-bold text-indigo-600 transition hover:text-indigo-800"
                   href="{{ route('trades') }}">
                    {{ __('labels.view_register_complete') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        {{-- CARD: CALENDARIO DE PNL --}}
        <div class="col-span-7 rounded-3xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 shadow-sm">

            {{-- HEADER: Título y Navegación --}}
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                    {{ __('labels.performance_schedule') }}
                </h3>

                <div class="flex items-center gap-4">
                    <button class="rounded-full p-2 transition hover:bg-gray-100 dark:hover:bg-gray-700"
                            wire:click="prevMonth">
                        <i class="fa-solid fa-chevron-left text-gray-500 dark:text-gray-300"></i>
                    </button>

                    <span class="w-32 text-center text-base font-bold capitalize text-gray-900 dark:text-gray-100">
                        {{ \Carbon\Carbon::parse($calendarDate)->translatedFormat('F Y') }}
                    </span>

                    <button class="rounded-full p-2 transition hover:bg-gray-100 dark:hover:bg-gray-700"
                            wire:click="nextMonth">
                        <i class="fa-solid fa-chevron-right text-gray-500 dark:text-gray-300"></i>
                    </button>
                </div>
            </div>

            {{-- GRID CALENDARIO --}}
            <div class="w-full">

                {{-- Cabecera Días Semana --}}
                <div class="mb-2 grid grid-cols-7 text-center">
                    @foreach ([__('labels.mon'), __('labels.tue'), __('labels.wed'), __('labels.thu'), __('labels.fri'), __('labels.sat'), __('labels.sun')] as $day)
                        <div class="py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            {{ $day }}
                        </div>
                    @endforeach
                </div>

                {{-- Días --}}
                <div class="grid grid-cols-7 gap-2">
                    @foreach ($calendarGrid as $day)
                        @php
                            // TU LÓGICA EXACTA (Sin cambios)
                            $bgColor = 'bg-gray-50 dark:bg-gray-800/60';
                            $textColor = 'text-gray-400';
                            $borderColor = 'border-transparent';

                            if (!is_null($day['pnl'])) {
                                if ($day['pnl'] > 0) {
                                    $bgColor = 'bg-emerald-50 dark:bg-emerald-500/10';
                                    $textColor = 'text-emerald-700 dark:text-emerald-400';
                                    $borderColor = 'border-emerald-200 dark:border-emerald-500/30';
                                } elseif ($day['pnl'] < 0) {
                                    $bgColor = 'bg-rose-50 dark:bg-rose-500/10';
                                    $textColor = 'text-rose-700 dark:text-rose-400';
                                    $borderColor = 'border-rose-200 dark:border-rose-500/30';
                                } else {
                                    $bgColor = 'bg-blue-50 dark:bg-blue-500/10';
                                    $textColor = 'text-blue-600 dark:text-blue-400';
                                }
                            }

                            $opacity = $day['is_current_month'] ? 'opacity-100' : 'opacity-40 grayscale';
                            $todayClass = $day['is_today'] ? 'ring-2 ring-blue-500 ring-offset-2' : '';
                            $hasTrades = !is_null($day['pnl']);
                            $cursorClass = $hasTrades ? 'cursor-pointer hover:ring-2 hover:ring-blue-300' : 'cursor-default';

                            // MAPA DE EMOJIS (Solo para visualizar)
                            $emojis = ['fire' => '🔥', 'happy' => '🙂', 'neutral' => '😐', 'sad' => '😡'];

                        @endphp

                        <div class="{{ $bgColor }} {{ $borderColor }} {{ $opacity }} {{ $todayClass }} {{ $cursorClass }} relative flex h-24 flex-col justify-between rounded-xl border p-2 transition-all hover:shadow-md"
                             @if ($hasTrades) @click="openDayDetails('{{ $day['date'] }}')" @endif>

                            {{-- CAMBIO SOLO AQUÍ: Cabecera con Flex para separar Número e Iconos --}}
                            <div class="flex w-full items-start justify-between">

                                {{-- Tu número de día original --}}
                                <span class="{{ $day['is_current_month'] ? 'text-gray-500 dark:text-gray-300' : 'text-gray-300' }} text-xs font-semibold">
                                    {{ $day['day'] }}
                                </span>

                                {{-- NUEVO: Iconos del Journal (Solo visual) --}}
                                <div class="flex gap-1">
                                    {{-- Icono Mood --}}
                                    @if (isset($day['journal_mood']) && isset($emojis[$day['journal_mood']]))
                                        <span class="text-xs leading-none">{{ $emojis[$day['journal_mood']] }}</span>
                                    @endif

                                    {{-- Icono Libro (si hay notas pero no mood) --}}
                                    @if (($day['has_notes'] ?? false) && !isset($day['journal_mood']))
                                        <i class="fa-solid fa-book text-[10px] text-indigo-400"></i>
                                    @endif
                                </div>
                            </div>

                            {{-- Tu PnL (Adaptado a $ / %) --}}
                            @if (!is_null($day['pnl']))
                                <div class="flex flex-col items-end"
                                     x-data> {{-- x-data es vital aquí --}}

                                    {{-- 
            El color ($textColor) sirve igual para $ y %, 
            así que lo dejamos calculado por PHP para ahorrar JS 
        --}}
                                    <span class="{{ $textColor }} text-sm font-black"
                                          x-text="$store.viewMode.format({{ $day['pnl'] }}, {{ $day['pnl_percentage'] ?? 0 }})">

                                        {{-- Fallback PHP (Lo que se ve al cargar) --}}
                                        {{ $day['pnl'] > 0 ? '+' : '' }}{{ number_format($day['pnl'], 2) }} $
                                    </span>

                                </div>
                            @endif

                        </div>
                    @endforeach
                </div>
            </div>
        </div>


    </div>

</div>
