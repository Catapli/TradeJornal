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
                                <h3 class="text-xl font-bold leading-6 text-gray-900">Resumen del Día</h3>
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
                                <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4">
                                        <div class="flex items-center justify-between">
                                            <h4 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-indigo-900">
                                                <i class="fa-solid fa-robot text-indigo-600"></i> Análisis Inteligente
                                            </h4>
                                            @if (!$aiAnalysis)
                                                <button class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-indigo-700 disabled:opacity-50"
                                                        wire:click="analyzeDayWithAi"
                                                        wire:loading.attr="disabled">
                                                    <span class="flex items-center gap-2"
                                                          wire:loading.remove
                                                          wire:target="analyzeDayWithAi">
                                                        <span>Analizar Día</span> <i class="fa-solid fa-wand-magic-sparkles"></i>
                                                    </span>
                                                    <span class="flex items-center gap-2"
                                                          wire:loading
                                                          wire:target="analyzeDayWithAi">
                                                        <i class="fa-solid fa-circle-notch fa-spin"></i> Analizando...
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

                                {{-- B. TABLA DE OPERACIONES (Tu código original) --}}
                                <div class="flex flex-grow flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                                    {{-- Spinner --}}
                                    <div class="w-full justify-center py-12 text-center"
                                         wire:loading.flex
                                         wire:target="openDayDetails">
                                        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent"></div>
                                        <p class="mt-2 text-sm text-gray-500">Cargando...</p>
                                    </div>

                                    {{-- Tabla --}}
                                    <div class="max-h-[55vh] flex-grow overflow-x-auto"
                                         wire:loading.remove
                                         wire:target="openDayDetails">
                                        @if (count($dayTrades) > 0)
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="sticky top-0 z-10 bg-gray-50 shadow-sm">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Hora</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Símbolo</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Tipo</th>
                                                        <th class="w-32 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Ejecución</th>
                                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Lotes</th>
                                                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Resultado</th>
                                                        <th class="px-4 py-3"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 bg-white">
                                                    @foreach ($dayTrades as $trade)
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
                                                            {{-- BARRA MAE/MFE (Zella Scale) CON TOOLTIPS --}}
                                                            <td class="px-2 py-3 align-middle">
                                                                @if ($trade->mae_price && $trade->mfe_price)
                                                                    @php
                                                                        // 1. Cálculos de Distancia (Puntos)
                                                                        $distMae = abs($trade->entry_price - $trade->mae_price);
                                                                        $distMfe = abs($trade->entry_price - $trade->mfe_price);
                                                                        $totalRange = $distMae + $distMfe;
                                                                        if ($totalRange == 0) {
                                                                            $totalRange = 0.00001;
                                                                        }

                                                                        // 2. Porcentajes Visuales
                                                                        $pctRed = ($distMae / $totalRange) * 100;
                                                                        $pctGreen = ($distMfe / $totalRange) * 100;

                                                                        // 3. Posición Marcador
                                                                        $distExit = abs($trade->entry_price - $trade->exit_price);
                                                                        $isProfit = ($trade->direction == 'long' && $trade->exit_price >= $trade->entry_price) || ($trade->direction == 'short' && $trade->exit_price <= $trade->entry_price);

                                                                        if ($isProfit) {
                                                                            $markerPos = $pctRed + ($distExit / $totalRange) * 100;
                                                                        } else {
                                                                            $markerPos = $pctRed - ($distExit / $totalRange) * 100;
                                                                        }
                                                                        $markerPos = max(0, min(100, $markerPos));

                                                                        // 4. CÁLCULO MONETARIO (Estimación basada en PnL Real)
                                                                        // Calculamos cuánto vale cada punto de movimiento en dinero para este trade concreto
                                                                        $priceDiff = $trade->exit_price - $trade->entry_price;
                                                                        if (abs($priceDiff) < 0.0000001) {
                                                                            $priceDiff = 0.0000001;
                                                                        } // Evitar div por cero

                                                                        // Valor por punto = PnL Total / Distancia Recorrida
                                                                        $valuePerPoint = $trade->pnl / $priceDiff;

                                                                        // Dinero en MAE (Siempre negativo visualmente para el tooltip)
                                                                        // Fórmula: (Precio MAE - Precio Entrada) * Valor Punto
                                                                        $maeMoney = abs(($trade->mae_price - $trade->entry_price) * $valuePerPoint) * -1;

                                                                        // Dinero en MFE (Siempre positivo visualmente)
                                                                        $mfeMoney = abs(($trade->mfe_price - $trade->entry_price) * $valuePerPoint);
                                                                    @endphp

                                                                    {{-- CONTENEDOR PRINCIPAL --}}
                                                                    <div class="relative mx-auto flex h-4 w-32 select-none items-center">

                                                                        {{-- A. CAPA VISUAL (Colores - Con Overflow Hidden) --}}
                                                                        <div class="pointer-events-none absolute inset-x-0 flex h-1.5 overflow-hidden rounded-full">
                                                                            <div class="h-full bg-rose-400"
                                                                                 style="width: {{ $pctRed }}%"></div>
                                                                            <div class="z-10 h-full w-[2px] bg-white opacity-50"></div>
                                                                            <div class="h-full bg-emerald-400"
                                                                                 style="width: {{ $pctGreen }}%"></div>
                                                                        </div>

                                                                        {{-- B. CAPA INTERACTIVA (Invisible - Sin Overflow para permitir Tooltips) --}}
                                                                        <div class="absolute inset-0 flex h-full w-full items-center">

                                                                            {{-- ZONA ROJA (Hover) --}}
                                                                            <div class="group/red relative h-4 cursor-help"
                                                                                 style="width: {{ $pctRed }}%">
                                                                                {{-- Tooltip Rojo --}}
                                                                                <div
                                                                                     class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-rose-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/red:block">
                                                                                    Máx. Riesgo: {{ number_format($maeMoney, 2) }} €
                                                                                    {{-- Triangulito abajo --}}
                                                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-rose-900"></div>
                                                                                </div>
                                                                            </div>

                                                                            {{-- ZONA VERDE (Hover) --}}
                                                                            <div class="group/green relative h-4 cursor-help"
                                                                                 style="width: {{ $pctGreen }}%">
                                                                                {{-- Tooltip Verde --}}
                                                                                <div
                                                                                     class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                                                    Máx. Potencial: +{{ number_format($mfeMoney, 2) }} €
                                                                                    {{-- Triangulito abajo --}}
                                                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-emerald-900"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        {{-- C. MARCADOR DE SALIDA (Encima de todo) --}}
                                                                        <div class="pointer-events-none absolute z-20 h-full w-1 rounded-full bg-gray-900 shadow-sm"
                                                                             style="left: {{ $markerPos }}%; transform: translateX(-50%);">
                                                                        </div>

                                                                    </div>
                                                                @else
                                                                    <span class="block text-center text-xs text-gray-300">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-gray-600">{{ $trade->size }}</td>
                                                            <td class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} whitespace-nowrap px-4 py-3 text-right text-sm font-bold">
                                                                {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                                            </td>
                                                            <td class="px-4 py-3 text-right text-gray-300">
                                                                <i class="fa-solid fa-chevron-right group-hover:text-indigo-600"></i>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="sticky bottom-0 z-10 border-t border-gray-200 bg-gray-50 shadow-sm">
                                                    <tr>
                                                        <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500"
                                                            colspan="5">Total:</td>
                                                        <td class="{{ $dayTrades->sum('pnl') >= 0 ? 'text-emerald-600' : 'text-rose-600' }} px-4 py-3 text-right text-base font-black"
                                                            colspan="2">
                                                            {{ $dayTrades->sum('pnl') >= 0 ? '+' : '' }}{{ number_format($dayTrades->sum('pnl'), 2) }} $
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        @else
                                            <div class="py-12 text-center">
                                                <i class="fa-solid fa-box-open mb-3 text-4xl text-gray-300"></i>
                                                <h3 class="text-gray-500">Sin operaciones</h3>
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
                            Volver al listado
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
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Ticket #{{ $selectedTrade->ticket }}</span>
                                        <h2 class="mt-1 flex items-center gap-3 text-4xl font-black text-gray-900">
                                            {{ $selectedTrade->tradeAsset->name ?? 'N/A' }}
                                            <span class="{{ $selectedTrade->direction == 'long' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded-full px-3 py-1 text-sm font-bold uppercase tracking-wide">
                                                {{ $selectedTrade->direction }}
                                            </span>
                                        </h2>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-sm font-medium text-gray-500">Resultado Neto</span>
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
                                            <h4 class="mb-4 text-xs font-bold uppercase text-gray-400">Datos de Ejecución</h4>
                                            <dl class="space-y-4 text-sm">
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">Precio Entrada</dt>
                                                    <dd class="font-mono font-bold text-gray-900">{{ $selectedTrade->entry_price }}</dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">Precio Salida</dt>
                                                    <dd class="font-mono font-bold text-gray-900">{{ $selectedTrade->exit_price }}</dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">Hora Entrada</dt>
                                                    <dd class="font-mono font-bold text-gray-900"> {{ \Carbon\Carbon::parse($selectedTrade->entry_time)->format('d-m-Y H:i') }}</dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">Hora Salida</dt>
                                                    <dd class="font-mono font-bold text-gray-900">{{ \Carbon\Carbon::parse($selectedTrade->exit_time)->format('d-m-Y H:i') }}</dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">Volumen</dt>
                                                    <dd class="font-bold text-gray-900">{{ $selectedTrade->size }} lotes</dd>
                                                </div>
                                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                                    <dt class="text-gray-500">Duración</dt>
                                                    <dd class="font-medium text-gray-900">{{ $selectedTrade->duration_minutes }} min</dd>
                                                </div>

                                                <div class="flex justify-between">
                                                    <dt class="text-gray-500">Score</dt>
                                                    <dd class="font-medium text-gray-900">
                                                        @if ($selectedTrade->mae_price && $selectedTrade->mfe_price)
                                                            @php
                                                                $distMae = abs($selectedTrade->entry_price - $selectedTrade->mae_price);
                                                                $distMfe = abs($selectedTrade->entry_price - $selectedTrade->mfe_price);
                                                                $totalRange = $distMae + $distMfe ?: 0.00001;
                                                                $pctRed = ($distMae / $totalRange) * 100;
                                                                $pctGreen = ($distMfe / $totalRange) * 100;
                                                                $distExit = abs($selectedTrade->entry_price - $selectedTrade->exit_price);
                                                                $isProfit =
                                                                    ($selectedTrade->direction == 'long' && $selectedTrade->exit_price >= $selectedTrade->entry_price) ||
                                                                    ($selectedTrade->direction == 'short' && $selectedTrade->exit_price <= $selectedTrade->entry_price);
                                                                $markerPos = $isProfit ? $pctRed + ($distExit / $totalRange) * 100 : $pctRed - ($distExit / $totalRange) * 100;
                                                                $markerPos = max(0, min(100, $markerPos));

                                                                $priceDiff = abs($selectedTrade->exit_price - $selectedTrade->entry_price) ?: 0.0000001;
                                                                $valuePerPoint = $selectedTrade->pnl / $priceDiff;
                                                                $maeMoney = abs(($selectedTrade->mae_price - $selectedTrade->entry_price) * $valuePerPoint) * -1;
                                                                $mfeMoney = abs(($selectedTrade->mfe_price - $selectedTrade->entry_price) * $valuePerPoint);
                                                            @endphp
                                                            <div class="relative mx-auto flex h-4 w-32 select-none items-center">
                                                                <div class="pointer-events-none absolute inset-x-0 flex h-1.5 overflow-hidden rounded-full">
                                                                    <div class="h-full bg-rose-400"
                                                                         style="width: {{ $pctRed }}%"></div>
                                                                    <div class="z-10 h-full w-[2px] bg-white opacity-50"></div>
                                                                    <div class="h-full bg-emerald-400"
                                                                         style="width: {{ $pctGreen }}%"></div>
                                                                </div>
                                                                <div class="absolute inset-0 flex h-full w-full items-center">
                                                                    <div class="group/red relative h-4 cursor-help"
                                                                         style="width: {{ $pctRed }}%">
                                                                        <div
                                                                             class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-rose-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/red:block">
                                                                            Máx. Riesgo: {{ number_format($maeMoney, 2) }} €
                                                                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-rose-900"></div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="group/green relative h-4 cursor-help"
                                                                         style="width: {{ $pctGreen }}%">
                                                                        <div
                                                                             class="absolute bottom-full left-1/2 z-50 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-emerald-900 px-2 py-1 text-[10px] font-bold text-white shadow-lg group-hover/green:block">
                                                                            Máx. Potencial: +{{ number_format($mfeMoney, 2) }} €
                                                                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-emerald-900"></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="pointer-events-none absolute z-20 h-full w-1 rounded-full bg-gray-900 shadow-sm"
                                                                     style="left: {{ $markerPos }}%; transform: translateX(-50%);"></div>
                                                            </div>
                                                        @else
                                                            <span class="block text-center text-xs text-gray-300">-</span>
                                                        @endif
                                                    </dd>

                                                </div>

                                            </dl>
                                        </div>

                                        <div class="rounded-2xl border border-yellow-100 bg-yellow-50 p-5">
                                            <h4 class="mb-2 flex items-center gap-2 text-xs font-bold uppercase text-yellow-700">
                                                <i class="fa-regular fa-note-sticky"></i> Notas
                                            </h4>
                                            <p class="text-sm italic leading-relaxed text-yellow-900">
                                                {{ $selectedTrade->notes ?: 'No se registraron notas para esta operación.' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- COLUMNA DERECHA: GRÁFICO + IA --}}
                                    <div class="space-y-6 lg:col-span-2">

                                        {{-- 1. CONTENEDOR DEL GRÁFICO (Híbrido: TradingView + Fallback Imagen) --}}
                                        <div class="relative aspect-video w-full overflow-hidden rounded-2xl border border-gray-700 bg-gray-900 shadow-lg"
                                             x-data="chartViewer()"
                                             :class="isFullscreen
                                                 ?
                                                 'fixed inset-0 z-[60] h-screen w-screen bg-gray-900' :
                                                 'relative aspect-video w-full overflow-hidden rounded-2xl border border-gray-700 bg-gray-900 shadow-lg'"
                                             {{-- ESCUCHADOR DE EVENTO: Espera a que el backend diga "Ya tengo los datos" --}}
                                             x-init="$nextTick(() => {
                                                 // Escuchar eventos DESPUÉS de estar listo
                                                 window.addEventListener('trade-selected', (e) => load(e.detail.path));
                                             
                                                 // Cargar datos iniciales si existen
                                                 @if($selectedTrade?->chart_data_path)
                                                 load(
                                                     '{{ $selectedTrade->chart_data_path }}',
                                                     {{ $selectedTrade->entry_price ?? 0 }},
                                                     {{ $selectedTrade->exit_price ?? 0 }},
                                                     '{{ $selectedTrade->direction ?? '' }}'
                                                 );
                                                 @endif
                                             })"
                                             wire:ignore>

                                            {{-- BARRA DE HERRAMIENTAS --}}
                                            <div class="absolute left-4 top-4 z-30 flex items-center space-x-1 rounded-lg border border-gray-700/50 bg-gray-800/90 p-1 backdrop-blur-sm"
                                                 x-show="hasData"
                                                 x-transition>

                                                {{-- Botones de Timeframe --}}
                                                <template x-for="tf in ['1m', '5m', '15m', '1h', '4h']">
                                                    <button class="rounded px-3 py-1 text-xs font-bold transition-all"
                                                            @click="changeTimeframe(tf)"
                                                            :class="currentTimeframe === tf ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-white hover:bg-gray-700'"
                                                            x-text="tf.toUpperCase()">
                                                    </button>
                                                </template>

                                                {{-- SEPARADOR VERTICAL --}}
                                                <div class="mx-1 h-4 w-px bg-gray-600"></div>

                                                {{-- BOTÓN VOLUMEN --}}
                                                <button class="flex items-center space-x-1 rounded border border-transparent px-2 py-1 text-xs font-bold transition-all"
                                                        @click="toggleVol()"
                                                        :class="showVolume ? 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20' : 'text-gray-500 hover:text-gray-300'"
                                                        title="Mostrar/Ocultar Volumen">

                                                    {{-- Icono de barras (FontAwesome o SVG manual) --}}
                                                    <i class="fa-solid fa-chart-column"></i>
                                                    <span>VOL</span>
                                                </button>

                                                {{-- BOTÓN EMA --}}
                                                <button class="ml-1 flex items-center space-x-1 rounded border border-transparent px-2 py-1 text-xs font-bold transition-all"
                                                        @click="toggleEma()"
                                                        :class="showEma ? 'text-amber-400 bg-amber-400/10 border-amber-400/20' : 'text-gray-500 hover:text-gray-300'"
                                                        title="Mostrar/Ocultar EMA 50">

                                                    {{-- Icono de línea --}}
                                                    <i class="fa-solid fa-wave-square"></i>
                                                    <span>EMA 50</span>
                                                </button>
                                                {{-- SEPARADOR FLEXIBLE (Empuja el siguiente botón a la derecha) --}}
                                                <div class="flex-grow"></div>

                                                {{-- BOTÓN PANTALLA COMPLETA --}}
                                                <button class="ml-2 px-2 text-gray-400 transition-colors hover:text-white"
                                                        @click="toggleFullscreen()"
                                                        :title="isFullscreen ? 'Salir de Pantalla Completa' : 'Pantalla Completa'">

                                                    {{-- Icono Cambiante --}}
                                                    <template x-if="!isFullscreen">
                                                        <i class="fa-solid fa-expand"></i>
                                                    </template>
                                                    <template x-if="isFullscreen">
                                                        <i class="fa-solid fa-compress"></i>
                                                    </template>
                                                </button>

                                            </div>



                                            {{-- El Div donde se pinta el gráfico JS --}}
                                            <div id="firstContainer"
                                                 class="h-full w-full"
                                                 x-ref="chartContainer"></div>

                                            {{-- Overlay de Carga (Controlado por Alpine 'loading') --}}
                                            <div class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-gray-900/90"
                                                 x-show="loading"
                                                 x-transition>
                                                <i class="fa-solid fa-circle-notch fa-spin mb-2 text-2xl text-indigo-500"></i>
                                                <span class="text-xs font-medium text-gray-400">Cargando mercado...</span>
                                            </div>

                                            {{-- Fallback: Se muestra si el JS dice 'hasData = false' --}}
                                            <div class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-gray-900"
                                                 x-show="!loading && !hasData"
                                                 style="display: none;">

                                                @if ($selectedTrade->screenshot_path)
                                                    {{-- Si hay imagen estática, la usamos de fondo --}}
                                                    <img class="absolute inset-0 h-full w-full object-contain opacity-60"
                                                         src="{{ Storage::url($selectedTrade->screenshot_path) }}"
                                                         alt="Chart Snapshot">

                                                    {{-- Botón para abrir la imagen --}}
                                                    <a class="relative z-30 rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur-md transition hover:bg-white/20"
                                                       href="{{ Storage::url($selectedTrade->screenshot_path) }}"
                                                       target="_blank">
                                                        <i class="fa-solid fa-image mr-2"></i> Ver Imagen Original
                                                    </a>
                                                @else
                                                    <i class="fa-solid fa-chart-line mb-3 text-5xl text-gray-700 opacity-50"></i>
                                                    <p class="text-sm font-medium text-gray-500">Gráfico interactivo no disponible</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- 2. ZONA ANÁLISIS IA INDIVIDUAL --}}
                                        <div class="relative overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm">

                                            {{-- Decoración fondo --}}
                                            <div class="absolute right-0 top-0 -mr-2 -mt-2 h-16 w-16 rounded-full bg-indigo-200 opacity-20 blur-xl"></div>

                                            <div class="relative z-10 mb-4 flex items-start justify-between">
                                                <div>
                                                    <h4 class="flex items-center gap-2 text-sm font-bold text-indigo-900">
                                                        <i class="fa-solid fa-brain text-indigo-600"></i> Análisis del Mentor
                                                    </h4>
                                                    @if (!$selectedTrade->ai_analysis)
                                                        <p class="mt-1 text-xs text-indigo-600">
                                                            Analiza estructura, tendencia y ejecución combinando gráfico y datos.
                                                        </p>
                                                    @endif
                                                </div>

                                                {{-- Botón Analizar --}}
                                                @if (!$selectedTrade->ai_analysis)
                                                    <button class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                            wire:click="analyzeIndividualTrade"
                                                            wire:loading.attr="disabled">

                                                        <span wire:loading.remove
                                                              wire:target="analyzeIndividualTrade">
                                                            Analizar Trade
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
                                                            Pensando...
                                                        </span>
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Skeleton Loader IA --}}
                                            <div class="w-full animate-pulse space-y-2"
                                                 wire:loading
                                                 wire:target="analyzeIndividualTrade">
                                                <div class="h-3 w-full rounded bg-indigo-200"></div>
                                                <div class="h-3 w-5/6 rounded bg-indigo-200"></div>
                                                <div class="h-3 w-4/6 rounded bg-indigo-200"></div>
                                            </div>

                                            {{-- Texto Real --}}
                                            @if ($selectedTrade->ai_analysis)
                                                <div class="prose prose-sm rounded-lg border border-indigo-50/50 bg-white/50 p-3 text-sm text-gray-800">
                                                    {!! Str::markdown($selectedTrade->ai_analysis) !!}
                                                </div>

                                                <div class="mt-2 text-right">
                                                    <button class="text-[10px] text-indigo-400 underline hover:text-indigo-600"
                                                            wire:click="analyzeIndividualTrade">
                                                        Regenerar análisis
                                                    </button>
                                                </div>
                                            @endif

                                        </div>
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
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400">Cargando Dashboard...</span>
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

    <header class="relative top-0 z-10 col-span-12 mt-[50px] flex w-auto justify-between bg-white pb-2 pr-3 shadow">
        <div class="flex min-h-11 max-w-7xl items-center space-x-1.5 px-4 py-1 sm:px-6 lg:px-8">
            <i class="fas fa-chart-bar text-xl text-black"></i>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Dashboard') }}
            </h2>
        </div>
        <livewire:ai-daily-tip :selected-accounts="$selectedAccounts" />

        <x-input-multiselect wire:model="selectedAccounts"
                             :options="$availableAccounts"
                             placeholder="Selecciona las cuentas..."
                             icono='<i class="fa-solid fa-users-viewfinder"></i>' />
    </header>


    <div class="col-span-12 grid grid-cols-12 gap-3 sm:px-6 sm:py-4 lg:px-8 lg:py-6">

        {{-- GRID DE STATS --}}
        <div class="col-span-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">


            {{-- CARD: PNL  --}}
            <div class="content-center rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">

                    {{-- IZQUIERDA: Textos y Porcentaje --}}
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1">
                            <h3 class="text-sm font-medium text-gray-500">Ganancia PNL</h3>
                        </div>
                        {{-- Usamos Alpine para mostrar el % dinámicamente --}}
                        <div class="text-3xl font-black text-gray-900">
                            <span class="@if ($pnlTotal > 0) text-green-700   @else  text-red-700 @endif">{{ number_format($pnlTotal, 2) }} $</span>

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
                            <h3 class="text-sm font-medium text-gray-500">Trade Win %</h3>
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
                               title="Ratio Riesgo Beneficio"></i>
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
                                0 €
                            </div>

                            {{-- Texto Rojo (Alineado a la der) --}}
                            <div class="text-rose-500"
                                 x-text="'-' + new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(loss)">
                                0 €
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
                            <h3 class="text-sm font-medium text-gray-500">Daily Win %</h3>
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
                <h3 class="text-lg font-bold text-gray-800">Curva de Rendimiento Acumulado</h3>

            </div>

            {{-- Contenedor del Gráfico --}}
            <div class="h-[200px] w-full min-w-0"
                 x-ref="evolutionChart"></div>
        </div>

        {{-- CARD: PNL DIARIO (BARRAS) --}}
        <div class="col-span-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm"
             wire:ignore>
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">PnL Diario Neto</h3>
                {{-- Leyenda Simple --}}
                <div class="flex gap-3 text-xs font-medium">
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Profit</div>
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Loss</div>
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
                    <h3 class="text-lg font-bold text-gray-800">Mapa de Calor Operativo</h3>
                    <p class="text-xs text-gray-500">Rendimiento acumulado por Hora y Día</p>
                </div>
                {{-- Leyenda visual simple --}}
                <div class="flex items-center gap-2 text-xs">
                    <span class="rounded bg-rose-500 px-2 py-1 text-white">Pérdida</span>
                    <span class="rounded bg-gray-100 px-2 py-1 text-gray-500">Neutro</span>
                    <span class="rounded bg-emerald-500 px-2 py-1 text-white">Ganancia</span>
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
                    Operaciones Recientes
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
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                Activo
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                Tipo
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-400"
                                scope="col">
                                P&L
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
                                wire:click="$dispatch('open-trade-detail', { tradeId: {{ $trade->id }} })">


                                {{-- 1. Fecha Cierre --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900">
                                        {{ \Carbon\Carbon::parse($trade->exit_time)->format('d-m-Y H:i') }}
                                    </span>
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
                                            LONG <i class="fa-solid fa-arrow-trend-up ml-1"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            SHORT <i class="fa-solid fa-arrow-trend-down ml-1"></i>
                                        </span>
                                    @endif
                                </td>

                                {{-- 4. PNL --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <span class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-sm font-black">
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
                                        <p class="text-sm font-medium">No hay operaciones recientes</p>
                                        <p class="mt-1 text-xs text-gray-400">Tus nuevos trades aparecerán aquí</p>
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
                   href="{{ route('journal') }}">
                    Ver diario completo <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        {{-- CARD: CALENDARIO DE PNL --}}
        <div class="col-span-7 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">

            {{-- HEADER: Título y Navegación --}}
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">
                    Calendario de Rendimiento
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
                    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $day)
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

                            {{-- Tu PnL original --}}
                            @if (!is_null($day['pnl']))
                                <div class="flex flex-col items-end">
                                    <span class="{{ $textColor }} text-sm font-black">
                                        {{ $day['pnl'] > 0 ? '+' : '' }}{{ number_format($day['pnl'], 2) }}€
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
