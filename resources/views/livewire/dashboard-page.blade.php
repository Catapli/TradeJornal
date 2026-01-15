<div class="max-w-fullxl mx-auto grid grid-cols-12"
     x-data="dashboard">



    {{-- ? Loading --}}
    <div wire:loading
         wire:target='calculateStats, updatedSelectedAccounts'>
        <x-loader></x-loader>
    </div>

    <header class="relative top-0 z-10 col-span-12 mt-[50px] flex w-auto justify-between bg-white pb-2 pr-3 shadow">
        <div class="flex min-h-11 max-w-7xl items-center space-x-1.5 px-4 py-1 sm:px-6 lg:px-8">
            <i class="fas fa-chart-bar text-xl text-black"></i>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Dashboard') }}
            </h2>
        </div>
        <x-input-multiselect wire:model.live="selectedAccounts"
                             :options="$availableAccounts"
                             placeholder="Selecciona las cuentas..."
                             icono='<i class="fa-solid fa-users-viewfinder"></i>' />
    </header>


    <div class="col-span-12 space-y-8 sm:px-6 sm:py-4 lg:px-8 lg:py-6">

        {{-- GRID DE STATS --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">


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

        {{-- CARD: CALENDARIO DE PNL --}}
        <div class="col-span-12 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">

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
                        @endphp

                        <div class="{{ $bgColor }} {{ $borderColor }} {{ $opacity }} {{ $todayClass }} relative flex h-24 flex-col justify-between rounded-xl border p-2 transition-all hover:shadow-md">

                            {{-- Número del día --}}
                            <span class="{{ $day['is_current_month'] ? 'text-gray-500' : 'text-gray-300' }} text-xs font-semibold">
                                {{ $day['day'] }}
                            </span>

                            {{-- PnL (Solo si hay trades) --}}
                            @if (!is_null($day['pnl']))
                                <div class="flex flex-col items-end">
                                    <span class="{{ $textColor }} text-sm font-black">
                                        {{ $day['pnl'] > 0 ? '+' : '' }}{{ number_format($day['pnl'], 0) }}€
                                    </span>
                                    {{-- Opcional: Badge pequeño --}}
                                    {{-- <span class="text-[10px] font-bold opacity-70">3 trades</span> --}}
                                </div>
                            @endif

                        </div>
                    @endforeach
                </div>
            </div>
        </div>


    </div>

</div>
