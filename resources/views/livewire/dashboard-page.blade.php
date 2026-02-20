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
            {{-- Aquí inicializamos 'currentView' para alternar entre 'list' y 'detail' --}}
            <div class="inline-block w-full transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:max-w-6xl sm:align-middle">

                {{-- ======================================================================= --}}
                {{-- VISTA 1: LISTADO DEL DÍA --}}
                <div class="flex h-full flex-col"
                     x-show="currentView === 'list'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-x-5"
                     x-transition:enter-end="opacity-100 translate-x-0"> {{-- Clase h-full añadida para estructura --}}

                    {{-- 1. CABECERA (Se mantiene igual, ancho completo) --}}
                    <div class="flex-shrink-0 border-b border-gray-100 bg-white px-4 pb-4 pt-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold leading-6 text-gray-900"> {{ __('labels.summary_day') }}</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d \d\e F \d\e Y') }}
                                </p>
                            </div>
                            <button class="rounded-full bg-gray-50 p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                                    @click="closeDayModal">
                                <i class="fa-solid fa-times text-lg"></i>
                            </button>
                        </div>
                    </div>

                    {{-- 2. CONTENIDO PRINCIPAL (GRID 2 COLUMNAS) --}}
                    <div class="flex-grow overflow-y-auto bg-gray-50 p-4 sm:p-6">
                        <div class="grid h-full grid-cols-1 gap-6 lg:grid-cols-3">

                            {{-- COLUMNA IZQUIERDA: IA + TABLA (Ocupa 2 espacios) --}}
                            <div class="flex flex-col space-y-6 lg:col-span-2">

                                {{-- A. SECCIÓN COACH IA --}}
                                @if (Auth::user()->subscribed('default'))
                                    <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm">
                                        <div class="flex flex-col gap-4">
                                            <div class="flex items-center justify-between">
                                                <h4 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-indigo-900">
                                                    <i class="fa-solid fa-robot text-indigo-600"></i> {{ __('labels.intelligent_analysis') }}
                                                </h4>
                                                <p class="mt-1 text-[10px] font-medium text-gray-500">
                                                    Usos diarios:
                                                    <span class="{{ $this->getAiCreditsLeft() > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        {{ $this->getAiCreditsLeft() }} / 10
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
                                                    <button class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                                                            wire:click="$set('aiAnalysis', null)"><i class="fa-solid fa-times"></i></button>
                                                    <div class="prose prose-sm max-w-none text-gray-800">{!! Str::markdown($aiAnalysis) !!}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif


                                {{-- B. TABLA DE OPERACIONES (Tu código original) --}}
                                <div class="flex flex-grow flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                                    {{-- Spinner --}}
                                    <div class="w-full justify-center py-12 text-center"
                                         wire:loading.flex
                                         wire:target="openDayDetails">
                                        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent"></div>
                                        <p class="mt-2 text-sm text-gray-500"> {{ __('labels.loading') }}</p>
                                    </div>

                                    {{-- Tabla --}}
                                    <div class="max-h-[55vh] flex-grow overflow-x-auto"
                                         wire:loading.remove
                                         wire:target="openDayDetails">
                                        @if (count($dayTrades) > 0)
                                            <table class="h-full min-w-full divide-y divide-gray-200">
                                                <thead class="sticky top-0 z-10 bg-gray-50 shadow-sm">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500"> {{ __('labels.hour') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500"> {{ __('labels.symbol') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500"> {{ __('labels.type') }}</th>
                                                        <th class="w-32 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500"> {{ __('labels.execution') }}</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500"> {{ __('labels.lots') }}</th>
                                                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500"> {{ __('labels.result') }}</th>
                                                        <th class="px-4 py-3"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 bg-white">
                                                    @foreach ($dayTrades as $trade)
                                                        {{-- @dd($dayTrades) --}}
                                                        {{-- COPIAR AQUÍ TU TR DENTRO DEL FOREACH EXACTAMENTE COMO LO TENÍAS --}}
                                                        <tr class="group cursor-pointer transition hover:bg-indigo-50"
                                                            @click="currentView = 'detail'; $wire.selectTrade({{ $trade->id }})">
                                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                                {{ \Carbon\Carbon::parse($trade->exit_time)->format('H:i') }}
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-gray-900">
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
                                                                    <span class="block text-center text-xs text-gray-300">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-gray-600">{{ $trade->size }}</td>
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
                                                            <td class="px-4 py-3 text-right text-gray-300">
                                                                <i class="fa-solid fa-chevron-right group-hover:text-indigo-600"></i>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="sticky bottom-0 z-10 border-t border-gray-200 bg-gray-50 shadow-sm">
                                                    @php
                                                        $count = $dayTrades->count();
                                                        // Lógica de colores para el contador: <3 verde, <6 naranja, >=6 rojo
                                                        $countColor = $count < 3 ? 'text-emerald-600' : ($count < 6 ? 'text-orange-500' : 'text-rose-600');

                                                        $totalPnl = $dayTrades->sum('pnl');
                                                        $totalPnlPct = $dayTrades->avg('pnl_percentage') ?? 0;
                                                    @endphp
                                                    <tr>
                                                        {{-- 1. Título de Operaciones (Colspan 3 para ocupar Hour, Symbol, Type) --}}
                                                        <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500"
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
                                                        <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500">
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
                                                <i class="fa-solid fa-box-open mb-3 text-4xl text-gray-300"></i>
                                                <h3 class="text-gray-500">{{ __('labels.without_operations') }}</h3>
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

                {{-- ======================================================================= --}}
                {{-- VISTA 2: DETALLE DEL TRADE (Se muestra al hacer click en fila) --}}
                {{-- ======================================================================= --}}
                <div class="min-h-[500px] bg-white"
                     x-show="currentView === 'detail'"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-10"
                     x-transition:enter-end="opacity-100 translate-x-0">

                    {{-- HEADER DETALLE CON BOTÓN VOLVER --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <button class="group flex items-center font-medium text-gray-500 transition hover:text-indigo-600"
                                @click="currentView = 'list'">
                            <div class="mr-3 rounded-full bg-gray-100 p-2 transition group-hover:bg-indigo-100">
                                <i class="fa-solid fa-arrow-left text-sm"></i>
                            </div>
                            {{ __('labels.back_to_list') }}
                        </button>
                        <button class="text-gray-400 hover:text-gray-500"
                                @click="closeDayModal"><i class="fa-solid fa-times"></i></button>
                    </div>

                    <div class="p-6">
                        {{-- SKELETON LOADING (Mientras carga el trade) --}}
                        <div class="w-full animate-pulse space-y-6"
                             wire:loading
                             wire:target="selectTrade">
                            <div class="flex justify-between">
                                <div class="h-8 w-1/3 rounded bg-gray-200"></div>
                                <div class="h-8 w-1/4 rounded bg-gray-200"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-6">
                                <div class="col-span-1 space-y-4">
                                    <div class="h-40 rounded-xl bg-gray-200"></div>
                                    <div class="h-24 rounded-xl bg-gray-200"></div>
                                </div>
                                <div class="col-span-2 h-80 rounded-xl bg-gray-200"></div>
                            </div>
                        </div>

                        {{-- CONTENIDO REAL DEL TRADE --}}
                        <div wire:loading.remove
                             wire:target="selectTrade">
                            @if ($selectedTrade)
                                {{-- Info Cabecera --}}
                                <div class="mb-4 flex items-end justify-between">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400"> {{ __('labels.ticket_') }} . {{ $selectedTrade->ticket }}</span>
                                        <h2 class="mt-1 flex items-center gap-3 text-4xl font-black text-gray-900">
                                            {{ $selectedTrade->tradeAsset->name ?? 'N/A' }}
                                            <span class="{{ $selectedTrade->direction == 'long' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded-full px-3 py-1 text-sm font-bold uppercase tracking-wide">
                                                {{ $selectedTrade->direction }}
                                            </span>
                                        </h2>
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-bold uppercase tracking-wide text-blue-700"> {{ $selectedTrade->account->name }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-sm font-medium text-gray-500"> {{ __('labels.net_result') }}</span>
                                        <span class="{{ $selectedTrade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-4xl font-black">
                                            {{ $selectedTrade->pnl >= 0 ? '+' : '' }}{{ number_format($selectedTrade->pnl, 2) }} $
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

                                    <div class="col-span-3">
                                        <livewire:mistake-selector :trade="$selectedTrade"
                                                                   :wire:key="'mistake-selector-'.$selectedTrade->id" />
                                    </div>


                                    {{-- COLUMNA DATOS (Tu código original intacto) --}}
                                    <div class="space-y-6 lg:col-span-1">
                                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6">
                                            <h4 class="mb-4 text-xs font-bold uppercase text-gray-400">{{ __('labels.execution_data') }}</h4>
                                            <dl class="space-y-4 text-sm">
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">{{ __('labels.entry_exit') }}</dt>
                                                    <dd class="font-mono font-bold text-gray-900">{{ $selectedTrade->entry_price }} <i class="fa-solid fa-arrow-right mx-1 text-xs text-gray-400"></i> {{ $selectedTrade->exit_price }}</dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">{{ __('labels.timetable') }}</dt>
                                                    <dd class="text-right font-mono font-bold text-gray-900">
                                                        {{ \Carbon\Carbon::parse($selectedTrade->entry_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedTrade->exit_time)->format('H:i') }}
                                                        <span class="block text-[10px] font-normal text-gray-400">{{ \Carbon\Carbon::parse($selectedTrade->exit_time)->format('d M Y') }}</span>
                                                    </dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">{{ __('labels.volume') }}</dt>
                                                    <dd class="font-bold text-gray-900">{{ $selectedTrade->size }} {{ __('labels.lots') }}</dd>
                                                </div>

                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">{{ __('labels.pips_moved') }}</dt>
                                                    <dd class="font-bold text-gray-900">{{ $selectedTrade->pips_traveled }} {{ __('labels.pips') }}</dd>
                                                </div>

                                                {{-- BARRA MAE/MFE --}}
                                                <div class="flex justify-between">
                                                    <dt class="text-gray-500">{{ __('labels.execution') }}</dt>
                                                    <dd class="w-32 font-medium text-gray-900">
                                                        @if ($selectedTrade->mae_price && $selectedTrade->mfe_price)
                                                            @php
                                                                // 1. Distancias Absolutas
                                                                $distMae = abs($selectedTrade->entry_price - $selectedTrade->mae_price);
                                                                $distMfe = abs($selectedTrade->entry_price - $selectedTrade->mfe_price);
                                                                $distReal = abs($selectedTrade->entry_price - $selectedTrade->exit_price);

                                                                // 2. Rango Visual
                                                                $totalRange = $distMae + $distMfe;
                                                                $totalRange = $totalRange > 0 ? $totalRange : 0.00001;

                                                                $pctRed = ($distMae / $totalRange) * 100;
                                                                $pctGreen = ($distMfe / $totalRange) * 100;

                                                                // 3. Posición Marcador
                                                                $isBetterThanEntry = $selectedTrade->direction == 'long' ? $selectedTrade->exit_price >= $selectedTrade->entry_price : $selectedTrade->exit_price <= $selectedTrade->entry_price;

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
                                                                    $valuePerPoint = abs($selectedTrade->pnl) / $distReal;
                                                                } else {
                                                                    // FALLBACK: Estimación basada en Lotes (Size)
                                                                    // Asumimos estándar Forex (100k unidades).
                                                                    // Si operas Índices/Crypto esto será una aproximación, pero mucho mejor que 0 o Infinito.
                                                                    $valuePerPoint = $selectedTrade->size * 100000;
                                                                }

                                                                // Aplicamos el valor del punto a las distancias MAE/MFE
                                                                $maeMoney = $distMae * $valuePerPoint;
                                                                $mfeMoney = $distMfe * $valuePerPoint;
                                                            @endphp
                                                            <div class="group/bar relative mx-auto flex h-4 w-32 select-none items-center">
                                                                <div class="pointer-events-none absolute inset-x-0 flex h-1.5 overflow-hidden rounded-full">
                                                                    <div class="h-full bg-rose-400"
                                                                         style="width: {{ $pctRed }}%"></div>
                                                                    <div class="z-10 h-full w-[2px] bg-white opacity-50"></div>
                                                                    <div class="h-full bg-emerald-400"
                                                                         style="width: {{ $pctGreen }}%"></div>
                                                                </div>
                                                                <div class="absolute inset-0 flex h-full w-full items-center">
                                                                    <div class="group/red relative h-4 w-full cursor-help"
                                                                         style="width: {{ $pctRed }}%">
                                                                        <div
                                                                             class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-rose-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/red:block">
                                                                            {{ __('labels.max_risk') }} {{ number_format($maeMoney, 0) }} $
                                                                        </div>
                                                                    </div>
                                                                    <div class="group/green relative h-4 w-full cursor-help"
                                                                         style="width: {{ $pctGreen }}%">
                                                                        <div
                                                                             class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                                            {{ __('labels.max_potencial') }} +{{ number_format($mfeMoney, 0) }} $
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="pointer-events-none absolute z-20 h-2.5 w-1 rounded-full bg-gray-900 shadow-sm ring-1 ring-white"
                                                                     style="left: {{ $markerPos }}%; transform: translateX(-50%);"></div>
                                                            </div>
                                                        @else
                                                            <span class="text-xs text-gray-300">-</span>
                                                        @endif
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>

                                        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm transition-all focus-within:ring-2 focus-within:ring-yellow-400 focus-within:ring-offset-2">
                                            <div class="mb-2 flex items-center justify-between">
                                                <h4 class="flex items-center gap-2 text-xs font-bold uppercase text-yellow-700">
                                                    <i class="fa-regular fa-note-sticky"></i> {{ __('labels.session_notes') }}
                                                </h4>

                                                {{-- Indicador de Guardado --}}
                                                <div class="text-xs font-medium text-yellow-600 transition-opacity duration-500"
                                                     x-data="{ saved: false }"
                                                     x-init="@this.on('trade-updated', () => {
                                                         saved = true;
                                                         setTimeout(() => saved = false, 2000)
                                                     })">
                                                    <span x-show="saved"
                                                          x-transition><i class="fa-solid fa-check"></i> {{ __('labels.saved') }}</span>
                                                    <span wire:loading
                                                          wire:target="saveNotes"><i class="fa-solid fa-circle-notch fa-spin"></i> {{ __('labels.saving') }}</span>
                                                </div>
                                            </div>

                                            {{-- Textarea con Auto-Guardado al perder el foco (blur) --}}
                                            <textarea class="w-full resize-none border-0 bg-transparent p-0 text-sm leading-relaxed text-gray-800 placeholder-yellow-800/50 focus:ring-0"
                                                      wire:model="notes"
                                                      wire:blur="saveNotes"
                                                      rows="4"
                                                      placeholder="{{ __('labels.placeholder_notes') }}"></textarea>
                                        </div>
                                    </div>

                                    {{-- COLUMNA GRÁFICO + IA --}}
                                    <div class="space-y-6 lg:col-span-2">

                                        <template x-if="!isLoading">
                                            {{-- 
        1. Usamos 'data-*' para pasar valores de PHP a JS sin errores de sintaxis.
        2. Usamos '@trade-selected.window' nativo de Alpine (se limpia solo, adiós error $cleanup).
    --}}
                                            <div class="relative aspect-video w-full overflow-hidden rounded-2xl border border-gray-700 bg-gray-900 shadow-lg"
                                                 data-path="{{ $selectedTrade?->chart_data_path }}"
                                                 data-entry="{{ $selectedTrade?->entry_price }}"
                                                 data-exit="{{ $selectedTrade?->exit_price }}"
                                                 data-dir="{{ $selectedTrade?->direction }}"
                                                 x-data="chartViewer('{{ $selectedTrade?->chart_data_path ? 'chart' : 'image' }}')"
                                                 {{-- DATOS SEGUROS (PHP -> HTML) --}}
                                                 {{-- EVENTO NATIVO (Se encarga de escuchar cambios si el modal no se recarga) --}}
                                                 @trade-selected.window="load($event.detail.path, $event.detail.entry, $event.detail.exit, $event.detail.direction)"
                                                 {{-- INICIALIZACIÓN (Solo el retardo y la carga inicial) --}}
                                                 x-init="setTimeout(() => {
                                                     // Leemos los atributos data- definidos arriba
                                                     // Esto ocurre 100ms después de crearse el HTML para asegurar que el gráfico tenga ancho
                                                     if ($el.dataset.path) {
                                                         load(
                                                             $el.dataset.path,
                                                             $el.dataset.entry,
                                                             $el.dataset.exit,
                                                             $el.dataset.dir
                                                         );
                                                     }
                                                 }, 100);">

                                                {{-- BARRA DE HERRAMIENTAS (Sin cambios) --}}
                                                <div class="absolute left-4 top-4 z-30 flex items-center space-x-1 rounded-lg border border-gray-700/50 bg-gray-800/90 p-1 backdrop-blur-sm"
                                                     wire:ignore>
                                                    @if ($selectedTrade?->chart_data_path)
                                                        <template x-for="tf in ['1m', '5m', '15m', '1h', '4h']">
                                                            <button class="rounded px-2 py-1 text-[10px] font-bold text-gray-400 transition-all hover:text-white"
                                                                    @click="changeTimeframe(tf)"
                                                                    :class="currentTimeframe === tf ? 'bg-indigo-600 text-white shadow-md' : ''"
                                                                    x-text="tf.toUpperCase()"></button>
                                                        </template>
                                                        <div class="mx-1 h-3 w-px bg-gray-600"></div>
                                                        {{-- BOTÓN VOLUMEN --}}
                                                        <button class="flex items-center space-x-1 rounded border border-transparent px-2 py-1 text-xs font-bold transition-all"
                                                                @click="toggleVol()"
                                                                :class="showVolume ? 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20' : 'text-gray-500 hover:text-gray-300'"
                                                                title="{{ __('labels.show_hide_volume') }}">

                                                            {{-- Icono de barras (FontAwesome o SVG manual) --}}
                                                            <i class="fa-solid fa-chart-column"></i>
                                                            <span>VOL</span>
                                                        </button>
                                                        {{-- BOTÓN EMA --}}
                                                        <button class="ml-1 flex items-center space-x-1 rounded border border-transparent px-2 py-1 text-xs font-bold transition-all"
                                                                @click="toggleEma()"
                                                                :class="showEma ? 'text-amber-400 bg-amber-400/10 border-amber-400/20' : 'text-gray-500 hover:text-gray-300'"
                                                                title="{{ __('labels.show_hide_ema') }}">

                                                            {{-- Icono de línea --}}
                                                            <i class="fa-solid fa-wave-square"></i>
                                                            <span>EMA 50</span>
                                                        </button>

                                                        {{-- SEPARADOR FLEXIBLE (Empuja el siguiente botón a la derecha) --}}
                                                        <div class="flex-grow"></div>
                                                    @endif
                                                    {{-- BOTÓN PANTALLA COMPLETA --}}
                                                    {{-- LADO DERECHO: TOGGLE VISTA (Siempre visible) --}}
                                                    <div class="flex items-center space-x-1 rounded-lg border border-gray-700/50 bg-gray-800/90 p-1 backdrop-blur-sm">
                                                        {{-- Botón Ver Gráfico --}}
                                                        @if ($selectedTrade?->chart_data_path)
                                                            <button class="flex items-center gap-2 rounded px-3 py-1 text-xs font-bold transition-all"
                                                                    @click="activeTab = 'chart'"
                                                                    :class="activeTab === 'chart' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white'">
                                                                <i class="fa-solid fa-chart-line"></i>
                                                                <span class="hidden sm:inline">Chart</span>
                                                            </button>
                                                        @endif

                                                        {{-- Botón Ver Captura --}}
                                                        <button class="flex items-center gap-2 rounded px-3 py-1 text-xs font-bold transition-all"
                                                                @click="activeTab = 'image'"
                                                                :class="activeTab === 'image' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white'">
                                                            <i class="fa-solid fa-image"></i>
                                                            <span class="hidden sm:inline">Screenshot</span>
                                                        </button>

                                                        <div class="mx-1 h-3 w-px bg-gray-600"></div>

                                                        <button class="ml-2 px-2 text-gray-400 transition-colors hover:text-white"
                                                                @click="toggleFullscreen()"
                                                                :title="isFullscreen ? '{{ __('labels.exit_screen_complete') }}' : '{{ __('labels.screen_complete') }}'">

                                                            {{-- Icono Cambiante --}}
                                                            <template x-if="!isFullscreen">
                                                                <i class="fa-solid fa-expand"></i>
                                                            </template>
                                                            <template x-if="isFullscreen">
                                                                <i class="fa-solid fa-compress"></i>
                                                            </template>
                                                        </button>
                                                    </div>

                                                </div>

                                                {{-- CONTENEDOR GRÁFICO --}}
                                                <div id="firstContainer"
                                                     class="h-full w-full bg-gray-900"
                                                     wire:ignore
                                                     x-show="activeTab === 'chart'"
                                                     x-ref="chartContainer"></div>

                                                {{-- 2. CONTENEDOR IMAGEN / UPLOAD --}}
                                                <div class="absolute inset-0 z-10 flex h-full w-full flex-col items-center justify-center bg-gray-900"
                                                     x-show="activeTab === 'image'"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     style="display: none;">

                                                    {{-- 
       IMPORTANTE: Esta wire:key cambia cuando se sube la foto.
       Esto obliga a Livewire a repintar todo el bloque sí o sí.
    --}}
                                                    <div class="h-full w-full"
                                                         wire:key="media-box-{{ $selectedTrade->id }}-{{ $currentScreenshot ? 'img' : 'drop' }}">

                                                        @if ($currentScreenshot)
                                                            {{-- CASO A: YA HAY IMAGEN --}}
                                                            <div class="group relative h-full w-full">
                                                                {{-- Añadimos un timestamp a la URL para evitar caché del navegador si cambias la imagen --}}
                                                                <img class="h-full w-full object-contain"
                                                                     src="{{ Storage::url($currentScreenshot) }}?t={{ time() }}"
                                                                     alt="Trade Screenshot">

                                                                {{-- Overlay para cambiar imagen --}}
                                                                <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                                                     x-show="!isFullscreen">
                                                                    <label class="cursor-pointer rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur-md transition hover:bg-white/20">
                                                                        <i class="fa-solid fa-cloud-arrow-up mr-2"></i> {{ __('labels.change_image') }}
                                                                        {{-- IMPORTANTE: wire:model.live --}}
                                                                        <input class="hidden"
                                                                               type="file"
                                                                               wire:model.live="uploadedScreenshot"
                                                                               accept="image/*">
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @else
                                                            {{-- CASO B: NO HAY IMAGEN (DROPZONE) --}}
                                                            <div class="flex h-full w-full flex-col items-center justify-center p-8 text-center"
                                                                 x-data="{ isDropping: false }"
                                                                 @dragover.prevent="isDropping = true"
                                                                 @dragleave.prevent="isDropping = false"
                                                                 {{-- Evento JS Manual --}}
                                                                 @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))">

                                                                {{-- IMPORTANTE: wire:model.live --}}
                                                                <input class="hidden"
                                                                       type="file"
                                                                       x-ref="fileInput"
                                                                       wire:model.live="uploadedScreenshot"
                                                                       accept="image/*">

                                                                <label class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-10 transition-all"
                                                                       :class="isDropping ? 'border-indigo-500 bg-indigo-500/10' : 'border-gray-700 hover:border-indigo-500 hover:bg-gray-800'"
                                                                       @click="$refs.fileInput.click()">

                                                                    <div wire:loading.remove
                                                                         wire:target="uploadedScreenshot">
                                                                        <div class="mb-4 rounded-full bg-gray-800 p-4 text-indigo-500 shadow-lg transition-transform group-hover:scale-110">
                                                                            <i class="fa-solid fa-cloud-arrow-up text-3xl"></i>
                                                                        </div>
                                                                        <h3 class="mb-1 text-lg font-bold text-white">{{ __('labels.upload_screenshot') }}</h3>
                                                                        <p class="text-xs text-gray-400">{{ __('labels.drag_drop_or_click') }}</p>
                                                                    </div>

                                                                    <div class="text-center"
                                                                         wire:loading
                                                                         wire:target="uploadedScreenshot">
                                                                        <i class="fa-solid fa-circle-notch fa-spin mb-3 text-3xl text-indigo-500"></i>
                                                                        <p class="text-sm font-bold text-white">{{ __('labels.uploading') }}...</p>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- LOADING OVERLAY --}}
                                                <div class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-gray-900/90"
                                                     x-show="loading"
                                                     x-transition>
                                                    <i class="fa-solid fa-circle-notch fa-spin mb-2 text-2xl text-indigo-500"></i>
                                                </div>
                                            </div>
                                        </template>
                                        {{-- IA --}}
                                        @if (Auth::user()->subscribed('default'))
                                            <div class="relative overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm">
                                                <div class="relative z-10 mb-4 flex items-start justify-between">
                                                    <div>
                                                        <h4 class="flex items-center gap-2 text-sm font-bold text-indigo-900">
                                                            <i class="fa-solid fa-brain text-indigo-600"></i> {{ __('labels.mentor_analyze') }}
                                                        </h4>
                                                        {{-- CONTADOR VISUAL --}}
                                                        <p class="mt-1 text-[10px] font-medium text-gray-500">
                                                            Usos diarios:
                                                            <span class="{{ $this->getAiCreditsLeft() > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                {{ $this->getAiCreditsLeft() }} / 10
                                                            </span>
                                                        </p>
                                                        @if (!$selectedTrade->ai_analysis)
                                                            <p class="mt-1 text-xs text-indigo-600">
                                                                {{ __('labels.explain_analyze_mentor') }}
                                                            </p>
                                                        @endif
                                                    </div>


                                                    @if (!$selectedTrade->ai_analysis)
                                                        <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                                wire:click="analyzeIndividualTrade"
                                                                wire:loading.attr="disabled">
                                                            <span wire:loading.remove
                                                                  wire:target="analyzeIndividualTrade">
                                                                {{ $this->getAiCreditsLeft() > 0 ? __('labels.analyze_trade') : 'Límite alcanzado' }}
                                                            </span>
                                                            <span class="flex items-center gap-2"
                                                                  wire:loading
                                                                  wire:target="analyzeIndividualTrade">
                                                                <svg class="h-3 w-3 animate-spin text-white"
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
                                                                {{ __('labels.thinking') }}
                                                            </span>
                                                        </button>
                                                    @endif



                                                </div>

                                                <div class="w-full animate-pulse space-y-2"
                                                     wire:loading
                                                     wire:target="analyzeIndividualTrade">
                                                    <div class="h-3 w-full rounded bg-indigo-200"></div>
                                                    <div class="h-3 w-5/6 rounded bg-indigo-200"></div>
                                                    <div class="h-3 w-4/6 rounded bg-indigo-200"></div>
                                                </div>


                                                @if ($selectedTrade->ai_analysis)
                                                    <div class="prose prose-sm rounded-lg border border-indigo-50/50 bg-white/50 p-3 text-sm text-gray-800">
                                                        {!! Str::markdown($selectedTrade->ai_analysis) !!}
                                                    </div>
                                                    <div class="mt-2 text-right">
                                                        <button class="text-[10px] text-indigo-400 underline hover:text-indigo-600"
                                                                wire:click="analyzeIndividualTrade">
                                                            {{ __('labels.regenerate_analyze') }}
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                        @endif

                                    </div>
                                </div>
                            @endif
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
         wire:target='calculateStats, updatedSelectedAccounts'>
        <x-loader></x-loader>
    </div>

    {{-- ? Loading JS --}}
    <div x-show="showLoading">
        <x-loader></x-loader>
    </div>

    <header class="relative top-0 z-10 col-span-12 mt-[55px] flex w-auto justify-between bg-white px-6 py-2 shadow">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-2xl text-indigo-600"></i>
                <h1 class="text-3xl font-black text-gray-900">{{ __('menu.dashboard') }}</h1>
            </div>
            <p class="text-sm text-gray-500">{{ __('menu.resume_dashboard') }}</p>
        </div>
        @if (Auth::user()->subscribed('default'))
            <livewire:ai-daily-tip :selected-accounts="$selectedAccounts" />
        @endif

        <x-input-multiselect wire:model="selectedAccounts"
                             :options="$availableAccounts"
                             placeholder="{{ __('labels.select_accounts') }}"
                             icono='<i class="fa-solid fa-users-viewfinder"></i>' />
    </header>


    <div class="col-span-12 grid grid-cols-12 gap-3 sm:px-6 sm:py-4 lg:px-8 lg:py-6">
        {{-- WIDGET: FLASH DE LECCIONES (Notas Recientes) --}}
        @if ($recentNotes)
            <div class="col-span-9 flex h-full flex-col overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center gap-2 pb-2">
                    <div class="rounded-lg bg-yellow-100 p-1.5 text-yellow-600">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">{{ __('labels.last_notes') }}</h3>
                </div>
                {{-- Cuerpo: Scroll Horizontal --}}
                <div class="flex flex-row gap-4 overflow-x-auto"> {{-- pb-6 da espacio para la barra de scroll si aparece --}}
                    @forelse($recentNotes as $noteTrade)
                        {{-- Tarjeta: Ancho fijo y flex-shrink-0 para que no se aplasten --}}
                        <div class="bg--gray-50 group relative flex min-w-[250px] max-w-[250px] flex-shrink-0 cursor-pointer flex-col justify-between rounded-2xl border border-gray-200 p-6 shadow-sm transition-all hover:border-yellow-200 hover:bg-yellow-50 hover:shadow-sm"
                             wire:click="openTradeFromNotes({{ $noteTrade->id }})">

                            <div>
                                <div class="mb-2 flex items-start justify-between">
                                    <span class="flex items-center gap-1 text-xs font-bold text-gray-900">
                                        {{ $noteTrade->tradeAsset->name ?? $noteTrade->symbol }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">{{ $noteTrade->exit_time->diffForHumans(null, true, true) }}</span>
                                </div>

                                {{-- Badge Resultado --}}
                                <div class="mb-2">
                                    <span class="{{ $noteTrade->pnl >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded px-1.5 py-0.5 text-[10px] font-bold">
                                        {{ $noteTrade->pnl >= 0 ? 'WIN' : 'LOSS' }}
                                    </span>
                                </div>

                                <p class="line-clamp-3 text-xs italic leading-relaxed text-gray-600">
                                    "{{ $noteTrade->notes }}"
                                </p>
                            </div>

                            {{-- Decoración opcional al hacer hover --}}
                            <div class="mt-2 text-right opacity-0 transition-opacity group-hover:opacity-100">
                                <i class="fa-solid fa-arrow-right text-xs text-yellow-600"></i>
                            </div>
                        </div>
                    @empty
                        <div class="flex w-full flex-col items-center justify-center py-8 text-center text-gray-400">
                            <i class="fa-regular fa-clipboard mb-2 text-2xl opacity-50"></i>
                            <p class="text-xs">{{ __('labels.not_notes') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- 3. PLAN DIARIO (Cuarto de pantalla - NUEVO) --}}
        <div class="col-span-3 flex flex-col justify-center rounded-3xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3">

            <div class="mb-4 flex items-center gap-2">
                <div class="rounded-lg bg-indigo-100 p-1.5 text-indigo-600"><i class="fa-solid fa-crosshairs"></i></div>
                <h3 class="text-sm font-bold text-gray-800">Objetivo Diario</h3>
            </div>

            @if ($planStatus)
                <div class="space-y-4">
                    {{-- 1. META (PnL Target) --}}
                    @if ($planStatus['pnl']['target'])
                        <div class="space-y-2">
                            <div class="flex items-end justify-between text-[10px] font-bold uppercase tracking-wider">
                                <span class="italic text-gray-500">Profit Target</span>
                                <span class="font-mono text-xs text-emerald-600">{{ number_format($planStatus['pnl']['target'], 0) }} $</span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner">
                                <div class="h-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)] transition-all duration-700 ease-out"
                                     style="width: {{ $planStatus['pnl']['progress'] }}%"></div>
                            </div>

                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase text-gray-400">{{ number_format($planStatus['pnl']['progress'], 0) }}%</span>
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
                                <span class="italic text-gray-500">Max Loss Limit</span>
                                <span class="font-mono text-xs text-rose-500">{{ number_format($planStatus['pnl']['limit'], 0) }} $</span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner">
                                <div class="{{ $isOverLoss ? 'bg-rose-600 animate-pulse' : 'bg-rose-400' }} h-full transition-all duration-500"
                                     style="width: {{ $pctLoss }}%"></div>
                            </div>

                            @if ($isOverLoss)
                                <div class="mt-1 flex animate-bounce items-center justify-center gap-1">
                                    <span class="text-[10px]">⛔</span>
                                    <p class="text-[10px] font-black uppercase tracking-tighter text-rose-600">STOP: Risk Reached</p>
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
                                <div class="text-[10px] font-bold uppercase italic tracking-wider text-gray-500">Daily Plan</div>
                                <div class="{{ $isFull ? 'text-rose-600' : ($isWarning ? 'text-orange-500' : 'text-emerald-500') }} font-mono text-[10px] font-bold">
                                    {{ number_format($pctTrades, 0) }}%
                                </div>
                            </div>

                            <div class="relative h-2 w-full overflow-hidden rounded-full bg-gray-100 shadow-inner">
                                <div class="{{ $isFull ? 'bg-rose-600 animate-pulse' : ($isWarning ? 'bg-orange-400' : 'bg-emerald-400') }} h-full transition-all duration-500"
                                     style="width: {{ $pctTrades }}%">
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="font-mono text-[11px] font-black text-gray-700">
                                    {{ $currentTrades }} <span class="font-normal italic text-gray-400">/ {{ $limitTrades }} ops</span>
                                </p>

                                @if ($isFull)
                                    <p class="flex items-center gap-1 text-[9px] font-black uppercase text-rose-600">
                                        <span class="inline-flex h-1.5 w-1.5 animate-ping rounded-full bg-rose-600 opacity-75"></span>
                                        {{ __('labels.limit_reached') }}
                                    </p>
                                @elseif($isWarning)
                                    <p class="text-[9px] font-bold uppercase italic text-orange-500">⚠️ Last Bullet</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="py-4 text-center">
                    <p class="mb-2 text-xs text-gray-400">Sin reglas configuradas.</p>
                    <a class="text-xs font-bold text-indigo-600 hover:underline"
                       href="{{ route('cuentas') }}">Configurar en Cuentas</a>
                </div>
            @endif
        </div>


        {{-- GRID DE STATS --}}
        <div class="col-span-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">


            {{-- CARD: PNL  --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500">{{ __('labels.win_pnl') }}</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900">
                            <span class="{{ $pnlTotal >= 0 ? 'text-emerald-600' : 'text-rose-600' }}"
                                  x-text="$store.viewMode.format({{ $pnlTotal }}, {{ $pnlTotal_perc ?? 0 }})"></span>
                            {{-- <span class="@if ($pnlTotal > 0) text-green-700   @else  text-red-700 @endif">{{ number_format($pnlTotal, 2) }} $</span> --}}

                        </div>
                    </div>


                </div>
            </div>

            {{-- CARD: WIN RATE --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                 wire:ignore>

                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500">{{ __('labels.trade_winrate') }}</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900"
                             x-text="$wire.winRateChartData?.rate + '%'">
                            0%
                        </div>
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
                            <div class="rounded-full bg-gray-50 px-2 py-0.5 text-xs font-bold text-gray-400">
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
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
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
                            <h3 class="text-sm font-medium text-gray-500">Avg R:R</h3>
                            <i class="fa-regular fa-circle-question text-xs text-gray-400"
                               title="{{ __('labels.title_r_r') }}"></i>
                        </div>
                        <div class="text-3xl font-black text-gray-900"
                             x-text="ratio">
                            0
                        </div>
                    </div>

                    {{-- DERECHA: La Barra Visual --}}
                    <div class="ml-4 flex flex-1 flex-col justify-center">

                        {{-- 1. La Barra (Visual) --}}
                        <div class="flex h-3 w-full overflow-hidden rounded-full bg-gray-100">
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

            <div class="content-center rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                 wire:ignore>

                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500">{{ __('labels.daily_winrate') }}</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900"
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
                            <div class="rounded-full bg-gray-50 px-2 py-0.5 text-xs font-bold text-gray-400">
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


            {{-- Aquí irían más tarjetas... --}}

        </div>

        {{-- CARD: GRÁFICO DE EVOLUCIÓN PNL --}}
        <div class="col-span-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm"
             wire:ignore>
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">{{ __('labels.cumulative_yield_curve') }}</h3>

            </div>

            {{-- Contenedor del Gráfico --}}
            <div class="h-[200px] w-full min-w-0"
                 x-ref="evolutionChart"></div>
        </div>

        {{-- CARD: PNL DIARIO (BARRAS) --}}
        <div class="col-span-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm"
             wire:ignore>
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">{{ __('labels.pnl_day_clean') }}</h3>
                {{-- Leyenda Simple --}}
                <div class="flex gap-3 text-xs font-medium">
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> {{ __('labels.profit') }}</div>
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span>{{ __('labels.loss') }}</div>
                </div>
            </div>

            {{-- Contenedor del Gráfico --}}
            <div class="h-[200px] w-full"
                 x-ref="dailyPnLBarChart"></div>
        </div>

        {{-- CARD: HEATMAP TEMPORAL --}}
        <div class="col-span-12 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
             wire:ignore>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">{{ __('labels.heatmap') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('labels.cumulative_yield_hour') }}</p>
                </div>
                Leyenda visual simple
                <div class="flex items-center gap-2 text-xs">
                    <span class="rounded bg-rose-500 px-2 py-1 text-white">{{ __('labels.loss') }}</span>
                    <span class="rounded bg-gray-100 px-2 py-1 text-gray-500">{{ __('labels.neutral') }}</span>
                    <span class="rounded bg-emerald-500 px-2 py-1 text-white">{{ __('labels.profit') }}</span>
                </div>
            </div>

            {{-- Contenedor Gráfico --}}
            <div class="h-[350px] w-full"
                 x-ref="heatmapChart"></div>
        </div>

        <div class="col-span-5 flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">

            {{-- Cabecera de la Tarjeta --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-800">
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
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                {{ __('labels.date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                {{ __('labels.active') }}
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                {{ __('labels.type') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                {{ __('labels.PnL') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($this->recentTrades as $trade)
                            <tr class="group cursor-pointer transition duration-150 hover:bg-indigo-50/60"
                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-500"
                                {{-- 
                            LÓGICA DEL CLICK:
                            1. showModalDetails = true: Abre el contenedor del modal
                            2. currentView = 'detail': Fuerza la vista de detalle directo
                            3. $wire.selectTrade(...): Carga los datos del trade en el backend
                        --}}
                                wire:click="openTradeFromTable({{ $trade->id }})">


                                {{-- 1. Fecha Cierre --}}
                                <td class="flex whitespace-nowrap px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900">
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
                                    <span class="text-sm font-bold text-gray-900">
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
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="mb-3 rounded-full bg-gray-100 p-4">
                                            <i class="fa-solid fa-chart-simple text-2xl text-gray-300"></i>
                                        </div>
                                        <p class="text-sm font-medium">{{ __('labels.not_recent_operations') }}</p>
                                        <p class="mt-1 text-xs text-gray-400">{{ __('labels.new_operations_appear_here') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer opcional --}}
            <div class="border-t border-gray-100 bg-gray-50 px-6 py-3 text-right">
                <a class="text-xs font-bold text-indigo-600 transition hover:text-indigo-800"
                   href="{{ route('trades') }}">
                    {{ __('labels.view_register_complete') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        {{-- CARD: CALENDARIO DE PNL --}}
        <div class="col-span-7 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">

            {{-- HEADER: Título y Navegación --}}
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">
                    {{ __('labels.performance_schedule') }}
                </h3>

                <div class="flex items-center gap-4">
                    <button class="rounded-full p-2 transition hover:bg-gray-100"
                            wire:click="prevMonth">
                        <i class="fa-solid fa-chevron-left text-gray-500"></i>
                    </button>

                    <span class="w-32 text-center text-base font-bold capitalize text-gray-900">
                        {{ \Carbon\Carbon::parse($calendarDate)->translatedFormat('F Y') }}
                    </span>

                    <button class="rounded-full p-2 transition hover:bg-gray-100"
                            wire:click="nextMonth">
                        <i class="fa-solid fa-chevron-right text-gray-500"></i>
                    </button>
                </div>
            </div>

            {{-- GRID CALENDARIO --}}
            <div class="w-full">

                {{-- Cabecera Días Semana --}}
                <div class="mb-2 grid grid-cols-7 text-center">
                    @foreach ([__('labels.mon'), __('labels.tue'), __('labels.wed'), __('labels.thu'), __('labels.fri'), __('labels.sat'), __('labels.sun')] as $day)
                        <div class="py-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            {{ $day }}
                        </div>
                    @endforeach
                </div>

                {{-- Días --}}
                <div class="grid grid-cols-7 gap-2">
                    @foreach ($calendarGrid as $day)
                        @php
                            // TU LÓGICA EXACTA (Sin cambios)
                            $bgColor = 'bg-gray-50';
                            $textColor = 'text-gray-400';
                            $borderColor = 'border-transparent';

                            if (!is_null($day['pnl'])) {
                                if ($day['pnl'] > 0) {
                                    $bgColor = 'bg-emerald-50';
                                    $textColor = 'text-emerald-700';
                                    $borderColor = 'border-emerald-200';
                                } elseif ($day['pnl'] < 0) {
                                    $bgColor = 'bg-rose-50';
                                    $textColor = 'text-rose-700';
                                    $borderColor = 'border-rose-200';
                                } else {
                                    $bgColor = 'bg-blue-50';
                                    $textColor = 'text-blue-600';
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
                                <span class="{{ $day['is_current_month'] ? 'text-gray-500' : 'text-gray-300' }} text-xs font-semibold">
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
