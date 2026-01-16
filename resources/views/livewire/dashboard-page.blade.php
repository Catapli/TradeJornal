<div class="max-w-fullxl mx-auto grid grid-cols-12"
     x-data="dashboard">

    {{-- MODAL DE DETALLE DEL DÍA --}}
    <div class="fixed inset-0 z-[150] overflow-y-auto"
         aria-labelledby="modal-title"
         x-show="showModalDetails"
         role="dialog"
         x-cloak
         aria-modal="true">

        {{-- Fondo oscuro (Backdrop) --}}
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">

            {{-- Transición de fondo --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="closeDayModal"
                 aria-hidden="true">
            </div>

            {{-- Truco para centrar verticalmente --}}
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle"
                  aria-hidden="true">&#8203;</span>

            {{-- Contenedor del Modal --}}
            <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:align-middle">

                {{-- Cabecera --}}
                <div class="border-b border-gray-100 bg-white px-4 pb-4 pt-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 id="modal-title"
                                class="text-xl font-bold leading-6 text-gray-900">
                                Resumen del Día
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d \d\e F \d\e Y') }}
                            </p>
                        </div>

                        {{-- Botón Cerrar (X) --}}
                        <button class="rounded-full bg-gray-50 p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                                @click="closeDayModal">
                            <i class="fa-solid fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Cuerpo (Tabla) --}}
                {{-- SECCIÓN COACH IA --}}
                <div class="border-b border-indigo-100 bg-indigo-50 px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-4">

                        <div class="flex items-center justify-between">
                            <h4 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-indigo-900">
                                <i class="fa-solid fa-robot text-indigo-600"></i> Análisis Inteligente
                            </h4>

                            {{-- Botón (Solo visible si no hay análisis aún) --}}
                            @if (!$aiAnalysis)
                                <button class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-all hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                        wire:click="analyzeDayWithAi"
                                        wire:loading.attr="disabled">

                                    {{-- Icono y Texto Normal --}}
                                    <span class="flex items-center gap-2"
                                          wire:loading.remove
                                          wire:target="analyzeDayWithAi">
                                        <span>Analizar Día</span>
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </span>

                                    {{-- Estado Cargando --}}
                                    <span class="flex items-center gap-2"
                                          wire:loading
                                          wire:target="analyzeDayWithAi">
                                        <svg class="h-4 w-4 animate-spin text-white"
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
                                        Analizando...
                                    </span>
                                </button>
                            @endif
                        </div>

                        {{-- RESULTADO DEL ANÁLISIS --}}

                        {{-- 1. Skeleton Loading (Mientras piensa) --}}
                        <div class="animate-pulse space-y-2"
                             wire:loading
                             wire:target="analyzeDayWithAi">
                            <div class="h-4 w-3/4 rounded bg-indigo-200"></div>
                            <div class="h-4 w-1/2 rounded bg-indigo-200"></div>
                        </div>

                        {{-- 2. El Texto de la IA --}}
                        @if ($aiAnalysis)
                            <div class="relative rounded-lg border border-indigo-100 bg-white p-4 shadow-sm">
                                {{-- Botón cerrar análisis --}}
                                <button class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                                        wire:click="$set('aiAnalysis', null)">
                                    <i class="fa-solid fa-times"></i>
                                </button>

                                <div class="prose prose-sm max-w-none text-gray-800">
                                    {{-- Renderizamos el Markdown que manda Gemini --}}
                                    {!! Str::markdown($aiAnalysis) !!}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
                {{-- Cuerpo (Tabla) --}}
                <div class="min-h-[200px] bg-white px-4 sm:p-6"> {{-- min-h para evitar saltos de altura --}}

                    {{-- 1. ESTADO DE CARGA (Spinner) --}}
                    {{-- Se muestra SOLO mientras se ejecuta 'openDayDetails' --}}
                    <div class="w-full flex-col items-center justify-center py-12"
                         wire:loading.flex
                         wire:target="openDayDetails">

                        <svg class="mb-4 h-10 w-10 animate-spin text-blue-600"
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
                        <p class="text-sm font-medium text-gray-500">Cargando operaciones...</p>
                    </div>

                    {{-- 2. ESTADO DE CONTENIDO (Tabla) --}}
                    {{-- Se OCULTA mientras se ejecuta 'openDayDetails' --}}
                    <div wire:loading.remove
                         wire:target="openDayDetails">

                        @if (count($dayTrades) > 0)
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 overflow-hidden">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Hora</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Cuenta</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Símbolo</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Tipo</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Lotes</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($dayTrades as $trade)
                                            <tr class="transition hover:bg-gray-50">
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($trade->exit_time)->format('H:i') }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                                        {{ $trade->account->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm font-bold text-gray-900">
                                                    {{ $trade->asset->name ?? $trade->tradeAsset->symbol }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                    @if ($trade->direction == 'long')
                                                        <span class="rounded bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-600">BUY</span>
                                                    @else
                                                        <span class="rounded bg-rose-50 px-2 py-1 text-xs font-bold text-rose-600">SELL</span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-gray-600">
                                                    {{ $trade->size }}
                                                </td>
                                                <td class="{{ $trade->pnl >= 0 ? 'text-emerald-600' : 'text-rose-600' }} whitespace-nowrap px-4 py-3 text-right text-sm font-bold">
                                                    {{ $trade->pnl >= 0 ? '+' : '' }}{{ number_format($trade->pnl, 2) }} $
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tfoot class="border-t border-gray-200 bg-gray-50">
                                            <tr>
                                                {{-- Total Operaciones (Ocupa 2 columnas) --}}
                                                <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500"
                                                    colspan="2">
                                                    Total Operaciones:
                                                </td>

                                                {{-- Valor Operaciones (Ocupa 1 columna) --}}
                                                <td class="{{ $dayTrades->count() <= 2 ? 'text-emerald-600' : ($dayTrades->count() <= 4 ? 'text-orange-500' : 'text-rose-600') }} px-4 py-3 text-left text-base font-black">
                                                    {{ $dayTrades->count() }}
                                                </td>

                                                {{-- Total Día Label (Ocupa 2 columnas) --}}
                                                <td class="px-4 py-3 text-right text-xs font-bold uppercase text-gray-500"
                                                    colspan="2">
                                                    Total Día:
                                                </td>

                                                {{-- Valor Total Día (Ocupa 1 columna - Alineado con PnL) --}}
                                                <td class="{{ $dayTrades->sum('pnl') >= 0 ? 'text-emerald-600' : 'text-rose-600' }} px-4 py-3 text-right text-base font-black">
                                                    {{ $dayTrades->sum('pnl') >= 0 ? '+' : '' }}{{ number_format($dayTrades->sum('pnl'), 2) }} $
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="py-12 text-center">
                                <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <i class="fa-solid fa-box-open text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Sin operaciones</h3>
                                <p class="text-gray-500">No hay registros para este día en las cuentas seleccionadas.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer botones --}}
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            type="button"
                            @click="closeDayModal">
                        Cerrar
                    </button>
                </div>
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

        <div class="col-span-5 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-800">
                Operaciones Recientes
            </h3>

            <div id="container_table"
                 class="items-center transition-all duration-300"
                 wire:ignore>
                <div>
                    <table id="table_history"
                           class="datatable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Fecha Cierre</th>
                                <th>Simbolo</th>
                                <th>PNL</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>

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
                            // Lógica de colores
                            $bgColor = 'bg-gray-50'; // Default (Sin trades)
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
                                    // Breakeven (0)
                                    $bgColor = 'bg-blue-50';
                                    $textColor = 'text-blue-600';
                                }
                            }

                            // Opacidad para días de otro mes
                            $opacity = $day['is_current_month'] ? 'opacity-100' : 'opacity-40 grayscale';

                            // Borde extra si es HOY
                            $todayClass = $day['is_today'] ? 'ring-2 ring-blue-500 ring-offset-2' : '';

                            $hasTrades = !is_null($day['pnl']);

                            $cursorClass = $hasTrades ? 'cursor-pointer hover:ring-2 hover:ring-blue-300' : 'cursor-default';
                        @endphp

                        <div class="{{ $bgColor }} {{ $borderColor }} {{ $opacity }} {{ $todayClass }} {{ $cursorClass }} relative flex h-24 flex-col justify-between rounded-xl border p-2 transition-all hover:shadow-md"
                             @if ($hasTrades) @click="openDayDetails('{{ $day['date'] }}')" @endif>

                            {{-- ... El resto del contenido de la celda se queda igual ... --}}
                            <span class="{{ $day['is_current_month'] ? 'text-gray-500' : 'text-gray-300' }} text-xs font-semibold">
                                {{ $day['day'] }}
                            </span>

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
