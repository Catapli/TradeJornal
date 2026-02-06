<div class="min-h-screen bg-gray-50 p-6"
     x-data>

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
                <span class="mt-4 animate-pulse text-sm font-bold text-gray-400">Cargando Dashboard...</span>
            </div>
        </div>
    </div>



    {{-- ? Loading --}}
    <div wire:loading
         wire:target='resetFilters'>
        <x-loader></x-loader>
    </div>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-flask-vial text-2xl text-indigo-600"></i>
                <h1 class="text-3xl font-black text-gray-900">Laboratorio</h1>
            </div>
            <p class="text-sm text-gray-500">Analiza el impacto de tu disciplina en el resultado final.</p>
        </div>

        <select class="rounded-lg border-gray-300 text-sm font-bold shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                wire:model.live="accountId">
            <option value="all">Todas las Cuentas</option>
            @foreach ($accounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- COLUMNA IZQUIERDA: CONTROLES --}}
        <div class="col-span-12 space-y-6 lg:col-span-3">

            {{-- TARJETA 1: LABORATORIO DE ESTRATEGIA (SIMULADOR) --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-5 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                    <i class="fa-solid fa-flask text-indigo-600"></i> Laboratorio
                </h3>

                <div class="space-y-6">

                    {{-- SECCIÓN A: RE-INGENIERÍA MECÁNICA (SL/TP) --}}
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <label class="mb-3 block text-[10px] font-bold uppercase tracking-wider text-gray-400">
                            Simulador Mecánico
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Fixed SL --}}
                            <div>
                                <label class="mb-1 block text-[10px] font-bold text-gray-500">Fixed SL</label>
                                <div class="relative">
                                    <input class="w-full rounded-lg border-gray-200 bg-white py-1.5 pl-2 pr-7 text-xs font-bold text-rose-600 placeholder-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                           type="number"
                                           wire:model.live.debounce.500ms="scenarios.fixed_sl"
                                           placeholder="Ej: 15">
                                    <span class="absolute right-2 top-1.5 text-[10px] font-bold text-gray-400">pts</span>
                                </div>
                            </div>
                            {{-- Fixed TP --}}
                            <div>
                                <label class="mb-1 block text-[10px] font-bold text-gray-500">Fixed TP</label>
                                <div class="relative">
                                    <input class="w-full rounded-lg border-gray-200 bg-white py-1.5 pl-2 pr-7 text-xs font-bold text-emerald-600 placeholder-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                           type="number"
                                           wire:model.live.debounce.500ms="scenarios.fixed_tp"
                                           placeholder="Ej: 30">
                                    <span class="absolute right-2 top-1.5 text-[10px] font-bold text-gray-400">pts</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-[10px] leading-tight text-slate-400">
                            Recalcula resultados usando tus datos MAE/MFE reales.
                        </p>
                    </div>

                    {{-- SECCIÓN B: FILTROS DE COMPORTAMIENTO --}}
                    <div>
                        <label class="mb-3 block text-[10px] font-bold uppercase tracking-wider text-gray-400">
                            Filtros de Disciplina
                        </label>

                        {{-- Select Fatiga --}}
                        <div class="mb-4">
                            <select class="w-full rounded-lg border-gray-200 text-xs font-bold text-gray-600 focus:border-indigo-500 focus:ring-indigo-500"
                                    wire:model.live="scenarios.max_daily_trades">
                                <option value="">Fatiga: Todas las operaciones</option>
                                <option value="1">Solo la 1ª del día (Sniper)</option>
                                <option value="2">Max 2 trades/día</option>
                                <option value="3">Max 3 trades/día</option>
                            </select>
                        </div>

                        {{-- Grid de Toggles --}}
                        <div class="grid grid-cols-1 gap-y-3">

                            {{-- Row 1: Viernes / Peores --}}
                            <div class="flex items-center justify-between">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <div class="relative inline-block w-8 select-none align-middle">
                                        <input class="peer sr-only"
                                               type="checkbox"
                                               wire:model.live="scenarios.no_fridays" />
                                        <div
                                             class="peer h-4 w-8 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-full">
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600">Sin Viernes</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2">
                                    <span class="text-xs font-medium text-gray-600">Sin 5 peores</span>
                                    <div class="relative inline-block w-8 select-none align-middle">
                                        <input class="peer sr-only"
                                               type="checkbox"
                                               wire:model.live="scenarios.remove_worst" />
                                        <div
                                             class="peer h-4 w-8 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-full">
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {{-- Row 2: Dirección (Long/Short) --}}
                            <div class="flex items-center justify-between border-t border-gray-50 pt-3">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <div class="relative inline-block w-8 select-none align-middle">
                                        <input class="peer sr-only"
                                               type="checkbox"
                                               wire:model.live="scenarios.only_longs" />
                                        <div
                                             class="peer h-4 w-8 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:bg-white after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-full">
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600">Solo Longs</span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2">
                                    <span class="text-xs font-medium text-gray-600">Solo Shorts</span>
                                    <div class="relative inline-block w-8 select-none align-middle">
                                        <input class="peer sr-only"
                                               type="checkbox"
                                               wire:model.live="scenarios.only_shorts" />
                                        <div
                                             class="peer h-4 w-8 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-3 after:w-3 after:rounded-full after:bg-white after:transition-all peer-checked:bg-rose-500 peer-checked:after:translate-x-full">
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN C: RESULTADO IMPACTO --}}
                    @if ($simulatedStats)
                        <div class="border-t border-gray-100 pt-4">
                            @php
                                // Calculamos la diferencia total en dinero (más tangible que expectancy)
                                $totalReal = end($realCurve)['y'] ?? 0;
                                $totalSim = end($simulatedCurve)['y'] ?? 0;
                                $diffTotal = $totalSim - $totalReal;
                            @endphp

                            <div class="{{ $diffTotal > 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-rose-50 border-rose-100' }} rounded-xl border p-3 text-center">
                                <div class="{{ $diffTotal > 0 ? 'text-emerald-400' : 'text-rose-400' }} text-[10px] font-bold uppercase">
                                    Diferencia Total
                                </div>
                                <div class="{{ $diffTotal > 0 ? 'text-emerald-700' : 'text-rose-700' }} text-lg font-black">
                                    {{ $diffTotal > 0 ? '+' : '' }}{{ number_format($diffTotal, 2) }} €
                                </div>
                                @if ($realStats['expectancy'] != 0)
                                    <div class="mt-1 text-[10px] font-medium opacity-70">
                                        Mejora del sistema: <strong>{{ round((($simulatedStats['expectancy'] - $realStats['expectancy']) / abs($realStats['expectancy'])) * 100) }}%</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>


            {{-- TARJETA 2: CALIDAD DEL SISTEMA (SQN) --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-1 font-bold text-gray-900">System Quality (SQN)</h3>

                <div class="mb-2 mt-4 flex items-end gap-3">
                    {{-- Valor Real --}}
                    <div>
                        <span class="text-4xl font-black text-gray-900">{{ $realStats['sqn'] ?? '0.0' }}</span>
                        <span class="block text-[10px] font-bold uppercase text-gray-400">Actual</span>
                    </div>

                    {{-- Valor Simulado (si existe) --}}
                    @if ($simulatedStats)
                        <div class="mb-1 text-gray-300"><i class="fa-solid fa-arrow-right"></i></div>
                        <div>
                            <span class="text-2xl font-black text-indigo-500">{{ $simulatedStats['sqn'] }}</span>
                            <span class="block text-[10px] font-bold uppercase text-indigo-300">Simulado</span>
                        </div>
                    @endif
                </div>

                {{-- Barra de Progreso --}}
                <div class="mb-2 mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    {{-- Barra Real --}}
                    <div class="h-2 rounded-full bg-gray-800 transition-all duration-500"
                         style="width: {{ min(100, (($realStats['sqn'] ?? 0) / 5) * 100) }}%"></div>
                </div>

                <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                    <span>Pobre (< 1.6)</span>
                            <span>Santo Grial (> 5.0)</span>
                </div>

                {{-- Diagnóstico --}}
                <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-xs leading-relaxed text-indigo-800">
                    @if (($realStats['sqn'] ?? 0) < 1.6)
                        <div class="flex gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                            <span>Difícil de operar. Mucha volatilidad para poco beneficio.</span>
                        </div>
                    @elseif(($realStats['sqn'] ?? 0) < 3.0)
                        <div class="flex gap-2">
                            <i class="fa-solid fa-check mt-0.5"></i>
                            <span>Buen sistema. Tienes ventaja estadística clara.</span>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <i class="fa-solid fa-trophy mt-0.5"></i>
                            <span>Sistema excelente. Considera aumentar el tamaño de posición.</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>


        {{-- COLUMNA DERECHA: DASHBOARD COMPLETO --}}
        <div class="col-span-12 space-y-6 lg:col-span-9">

            {{-- 1. GRÁFICO EQUITY CURVE --}}
            <div class="relative flex flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                 x-data="equityChart(@js($realCurve), @js($simulatedCurve))"
                 x-effect="updateData(@js($realCurve), @js($simulatedCurve))">

                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Curva de Crecimiento (Equity Curve)</h3>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                            <span class="h-3 w-3 rounded-full bg-slate-300"></span> Realidad
                        </div>
                        @if (!empty($simulatedCurve))
                            <div class="flex items-center gap-2 text-xs font-bold text-indigo-600">
                                <span class="h-3 w-3 rounded-full bg-indigo-500"></span> Simulación
                            </div>
                        @endif
                    </div>
                </div>

                {{-- GRÁFICO CON ALTURA FIJA --}}
                <div id="equityChart"
                     class="h-[400px] w-full"></div>

                {{-- Loading --}}
                <div class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/50 backdrop-blur-[1px]"
                     wire:loading.flex
                     wire:target="scenarios, accountId">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                </div>
            </div>

            {{-- 2. REPORTES TEMPORALES --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="barChart(@js($hourlyReportData), 'hour', 'P&L por Hora')">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                        <i class="fa-regular fa-clock text-blue-600"></i> Rendimiento por Hora
                    </h3>
                    <div id="hourlyChart"
                         class="h-[250px] w-full"></div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="barChart(@js($sessionReportData), 'session', 'P&L por Sesión')">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                        <i class="fa-solid fa-globe-americas text-green-600"></i> Rendimiento por Sesión
                    </h3>
                    <div id="sessionChart"
                         class="h-[250px] w-full"></div>
                </div>
            </div>

            {{-- 3. PSICOLOGÍA Y GESTIÓN (SCATTER + DISTRIBUCIÓN) --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- REEMPLAZAR EL DIV DEL "SCATTER PLOT" POR ESTE BLOQUE --}}

                <div class="col-span-1 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm md:col-span-2"
                     x-data="efficiencyChart(@js($efficiencyData))">

                    <div class="mb-6 flex flex-col justify-between sm:flex-row sm:items-end">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-crosshairs text-indigo-600"></i> Eficiencia del Trader
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Comparativa de los últimos 15 trades:
                                <span class="font-bold text-rose-500">Riesgo sufrido (MAE)</span> vs
                                <span class="font-bold text-gray-900">Resultado (PnL)</span> vs
                                <span class="font-bold text-emerald-500">Potencial máximo (MFE)</span>.
                            </p>
                        </div>
                    </div>

                    {{-- Contenedor del Gráfico --}}
                    <div id="efficiencyChart"
                         class="h-[350px] w-full"></div>

                    {{-- Leyenda Explicativa Rápida --}}
                    <div class="mt-4 flex flex-wrap justify-center gap-4 text-[10px] text-gray-400">
                        <div class="flex items-center gap-1">
                            <span class="block h-2 w-2 rounded-full bg-rose-400"></span> MAE: Cuanto se fue en contra antes de cerrar.
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="block h-2 w-2 rounded-full bg-emerald-400"></span> MFE: Cuanto dinero llegó a marcar flotante.
                        </div>
                    </div>
                </div>


                {{-- Histograma Distribución --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="distributionChart(@js($distributionData))">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-chart-column text-purple-500"></i> Distribución de PnL
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">Frecuencia de resultados. ¿Hacia dónde se inclina?</p>
                        </div>
                    </div>
                    <div id="distChart"
                         class="h-[250px] w-full"></div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="radarChart(@js($radarData))">

                    <div class="mb-2 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-fingerprint text-purple-600"></i> Perfil del Trader
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">Análisis 360º de tu estilo actual.</p>
                        </div>
                    </div>

                    {{-- Contenedor Gráfico --}}
                    <div id="radarChart"
                         class="flex h-[250px] w-full items-center justify-center"></div>

                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data>
                    <div class="mb-4">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                            <i class="fa-solid fa-skull-crossbones text-gray-800"></i> Análisis de Riesgo & Ruina
                        </h3>
                        <p class="mt-1 text-xs text-gray-400">Probabilidades matemáticas basadas en tu Winrate ({{ $riskData['win_rate'] ?? 0 }}%) y Ratio (1:{{ $riskData['payoff'] ?? 0 }}).</p>
                    </div>

                    @if (empty($riskData))
                        <div class="flex h-32 items-center justify-center text-xs text-gray-400">
                            Necesitas al menos 10 trades para calcular el riesgo.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            {{-- IZQUIERDA: GAUGE DE RUINA --}}
                            <div class="flex flex-col items-center justify-center border-r border-gray-100 pr-0 md:pr-6">

                                {{-- Gauge Visual CSS Puro (Semicírculo) --}}
                                <div class="relative flex h-32 w-48 items-end justify-center overflow-hidden">
                                    {{-- Fondo Arco --}}
                                    <div class="absolute top-0 h-full w-full rounded-t-full bg-gray-100"></div>

                                    {{-- Arco de Valor (Rotación dinámica) --}}
                                    {{-- 0% Risk = Green, 100% Risk = Red --}}
                                    @php
                                        $deg = ($riskData['risk_of_ruin'] / 100) * 180; // 0 a 180 grados
                                        $colorClass = $riskData['risk_of_ruin'] < 1 ? 'bg-emerald-500' : ($riskData['risk_of_ruin'] < 20 ? 'bg-amber-400' : 'bg-rose-600');
                                    @endphp
                                    <div class="{{ $colorClass }} absolute top-0 h-full w-full origin-bottom rounded-t-full opacity-80 transition-transform duration-1000 ease-out"
                                         style="transform: rotate({{ $deg - 180 }}deg);"></div>

                                    {{-- Centro Blanco (Máscara) --}}
                                    <div class="absolute bottom-0 z-10 flex h-20 w-32 items-end justify-center rounded-t-full bg-white pb-2">
                                        <div class="text-center">
                                            <span class="block text-3xl font-black text-gray-900">{{ $riskData['risk_of_ruin'] }}%</span>
                                            <span class="text-[10px] font-bold uppercase text-gray-400">Prob. Ruina</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mensaje de Diagnóstico --}}
                                <div class="mt-2 text-center text-xs">
                                    @if ($riskData['risk_of_ruin'] < 1)
                                        <span class="font-bold text-emerald-600">✅ Zona Segura</span>
                                    @elseif($riskData['risk_of_ruin'] < 10)
                                        <span class="font-bold text-amber-500">⚠️ Precaución</span>
                                    @else
                                        <span class="font-bold text-rose-600">🚨 Peligro Crítico</span>
                                    @endif
                                    <p class="mt-1 text-[10px] text-gray-400">
                                        @if ($riskData['edge'] > 0)
                                            Tienes ventaja estadística (Edge: {{ $riskData['edge'] }}).
                                        @else
                                            Tu esperanza matemática es negativa.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- DERECHA: TABLA DE RACHAS --}}
                            <div>
                                <h4 class="mb-3 text-xs font-bold text-gray-500">Probabilidad de Racha (Losing Streak)</h4>
                                <div class="space-y-3">

                                    {{-- Item Racha 3 --}}
                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600">
                                            <span>3 Pérdidas seguidas</span>
                                            <span>{{ $riskData['streak_prob']['3'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100">
                                            <div class="h-1.5 rounded-full bg-gray-400"
                                                 style="width: {{ min(100, $riskData['streak_prob']['3']) }}%"></div>
                                        </div>
                                    </div>

                                    {{-- Item Racha 5 --}}
                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600">
                                            <span>5 Pérdidas seguidas</span>
                                            <span class="{{ $riskData['streak_prob']['5'] > 50 ? 'text-rose-500 font-bold' : '' }}">{{ $riskData['streak_prob']['5'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100">
                                            <div class="{{ $riskData['streak_prob']['5'] > 20 ? 'bg-amber-400' : 'bg-gray-400' }} h-1.5 rounded-full"
                                                 style="width: {{ min(100, $riskData['streak_prob']['5']) }}%"></div>
                                        </div>
                                    </div>

                                    {{-- Item Racha 8 --}}
                                    <div>
                                        <div class="mb-1 flex justify-between text-[10px] font-medium text-gray-600">
                                            <span>8 Pérdidas seguidas</span>
                                            <span>{{ $riskData['streak_prob']['8'] }}%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-100">
                                            <div class="h-1.5 rounded-full bg-rose-400"
                                                 style="width: {{ min(100, $riskData['streak_prob']['8']) }}%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-3 rounded-md bg-indigo-50 p-2 text-[10px] leading-tight text-indigo-800">
                                        <i class="fa-solid fa-circle-info mr-1"></i>
                                        Si tienes un <strong>{{ $riskData['streak_prob']['5'] }}%</strong> de perder 5 veces seguidas, asegúrate de que 5 pérdidas no quemen más del 10% de tu cuenta.
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="mistakesChart(@js($mistakesData))">

                    {{-- Header --}}
                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-bug text-rose-500"></i> Ranking de Errores
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">Coste total y frecuencia.</p>
                        </div>
                    </div>

                    {{-- Contenedor Gráfico --}}
                    <div class="relative min-h-[250px] w-full flex-1">
                        {{-- Estado Vacío --}}
                        <template x-if="!hasData">
                            <div class="absolute inset-0 flex h-full flex-col items-center justify-center text-center text-gray-400">
                                <div class="mb-3 rounded-full bg-emerald-50 p-4">
                                    <i class="fa-solid fa-shield-halved text-2xl text-emerald-400"></i>
                                </div>
                                <p class="text-xs font-medium">Operativa limpia.</p>
                            </div>
                        </template>

                        {{-- Gráfico --}}
                        <div id="mistakesChart"
                             class="w-full"
                             x-show="hasData"></div>
                    </div>
                </div>








            </div>

        </div>

    </div>

    <div>
        {{-- SCRIPTS JS --}}
        <script>
            document.addEventListener('alpine:init', () => {

                // 1. EQUITY CHART
                Alpine.data('equityChart', (initialReal, initialSim) => ({
                    chart: null,
                    init() {
                        const options = {
                            series: [{
                                name: 'Realidad',
                                data: initialReal
                            }, {
                                name: 'Simulación',
                                data: initialSim
                            }],
                            chart: {
                                type: 'area',
                                height: 400,
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                },
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800
                                }
                            },
                            colors: ['#9CA3AF', '#6366F1'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.4,
                                    opacityTo: 0.05,
                                    stops: [0, 90, 100]
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            stroke: {
                                curve: 'smooth',
                                width: [2, 3],
                                dashArray: [5, 0]
                            },
                            xaxis: {
                                type: 'datetime',
                                tooltip: {
                                    enabled: false
                                },
                                axisBorder: {
                                    show: false
                                },
                                axisTicks: {
                                    show: false
                                },
                                labels: {
                                    style: {
                                        colors: '#94a3b8',
                                        fontSize: '10px'
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: (val) => val.toFixed(0) + ' $',
                                    style: {
                                        colors: '#94a3b8',
                                        fontSize: '10px'
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f3f4f6',
                                strokeDashArray: 4,
                                padding: {
                                    left: 10
                                }
                            },
                            tooltip: {
                                theme: 'light',
                                x: {
                                    format: 'dd MMM yyyy'
                                },
                                y: {
                                    formatter: (val) => val.toFixed(2) + ' $'
                                }
                            },
                            legend: {
                                show: false
                            }
                        };
                        this.chart = new ApexCharts(document.querySelector("#equityChart"), options);
                        this.chart.render();
                    },
                    updateData(newReal, newSim) {
                        if (this.chart) this.chart.updateSeries([{
                            name: 'Realidad',
                            data: newReal
                        }, {
                            name: 'Simulación',
                            data: newSim
                        }]);
                    }
                }));

                // 2. BAR CHARTS (HORA/SESIÓN)
                Alpine.data('barChart', (data, categoryKey, title) => ({
                    chart: null,
                    init() {
                        if (!data || data.length === 0) return;
                        const categories = data.map(item => item[categoryKey]);
                        const seriesData = data.map(item => item.pnl);
                        const chartId = categoryKey === 'hour' ? 'hourlyChart' : 'sessionChart';
                        const el = document.getElementById(chartId);
                        if (!el) return;

                        const options = {
                            series: [{
                                name: 'P&L',
                                data: seriesData
                            }],
                            chart: {
                                type: 'bar',
                                height: 250,
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 4,
                                    columnWidth: '60%',
                                    colors: {
                                        ranges: [{
                                            from: -1000000,
                                            to: -0.01,
                                            color: '#F43F5E'
                                        }, {
                                            from: 0,
                                            to: 1000000,
                                            color: '#10B981'
                                        }]
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            xaxis: {
                                categories: categories,
                                labels: {
                                    style: {
                                        colors: '#94a3b8',
                                        fontSize: '10px'
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: (val) => val.toFixed(0) + ' $',
                                    style: {
                                        colors: '#94a3b8',
                                        fontSize: '10px'
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f3f4f6',
                                strokeDashArray: 4
                            },
                            tooltip: {
                                theme: 'light',
                                y: {
                                    formatter: (val) => val.toFixed(2) + ' $'
                                }
                            }
                        };
                        this.chart = new ApexCharts(el, options);
                        this.chart.render();
                    }
                }));

                Alpine.data('efficiencyChart', (payload) => ({
                    chart: null,
                    init() {
                        if (!payload || !payload.categories || payload.categories.length === 0) return;

                        const options = {
                            series: payload.series,
                            chart: {
                                type: 'bar',
                                height: 350,
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                },
                                zoom: {
                                    enabled: false
                                }
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: false,
                                    columnWidth: '70%',
                                    endingShape: 'rounded',
                                    dataLabels: {
                                        position: 'top', // top, center, bottom
                                    },
                                },
                            },
                            dataLabels: {
                                enabled: false // Desactivado para limpieza, el tooltip hace el trabajo
                            },
                            stroke: {
                                show: true,
                                width: 2,
                                colors: ['transparent']
                            },
                            xaxis: {
                                categories: payload.categories,
                                labels: {
                                    style: {
                                        fontSize: '10px',
                                        colors: '#64748b'
                                    }
                                },
                                axisBorder: {
                                    show: false
                                },
                                axisTicks: {
                                    show: false
                                }
                            },
                            yaxis: {
                                title: {
                                    text: 'Valor Monetario ($)',
                                    style: {
                                        fontSize: '10px',
                                        color: '#94a3b8'
                                    }
                                },
                                labels: {
                                    formatter: (val) => val.toFixed(0),
                                    style: {
                                        colors: '#94a3b8'
                                    }
                                }
                            },
                            // Colores Semánticos:
                            // 0: MAE (Rojo Rosado)
                            // 1: PnL (Azul Oscuro - Realidad)
                            // 2: MFE (Verde Esmeralda - Potencial)
                            colors: ['#F43F5E', '#1E293B', '#10B981'],
                            fill: {
                                opacity: 1
                            },
                            tooltip: {
                                theme: 'light',
                                y: {
                                    formatter: function(val) {
                                        return val + " $"
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f1f5f9',
                                strokeDashArray: 4,
                                yaxis: {
                                    lines: {
                                        show: true
                                    }
                                }
                            },
                            legend: {
                                position: 'top',
                                horizontalAlign: 'right',
                                fontSize: '12px',
                                fontFamily: 'Inter',
                                offsetY: -20,
                                itemMargin: {
                                    horizontal: 10,
                                    vertical: 0
                                }
                            }
                        };

                        this.chart = new ApexCharts(document.querySelector("#efficiencyChart"), options);
                        this.chart.render();
                    }
                }));

                // 4. DISTRIBUTION CHART (HISTOGRAMA)
                Alpine.data('distributionChart', (payload) => ({
                    chart: null,
                    init() {
                        if (!payload || !payload.data || payload.data.length === 0) return;
                        const colors = payload.categories.map(cat => cat.includes('-') ? '#EF4444' : '#10B981');
                        const options = {
                            series: [{
                                name: 'Trades',
                                data: payload.data
                            }],
                            chart: {
                                type: 'bar',
                                height: 250,
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 2,
                                    columnWidth: '95%',
                                    distributed: true,
                                    dataLabels: {
                                        position: 'top'
                                    }
                                }
                            },
                            colors: colors,
                            dataLabels: {
                                enabled: true,
                                offsetY: -20,
                                style: {
                                    fontSize: '10px',
                                    colors: ["#64748b"]
                                }
                            },
                            xaxis: {
                                categories: payload.categories,
                                labels: {
                                    show: false
                                },
                                axisBorder: {
                                    show: false
                                },
                                axisTicks: {
                                    show: false
                                }
                            },
                            yaxis: {
                                show: false
                            },
                            grid: {
                                show: false
                            },
                            legend: {
                                show: false
                            },
                            tooltip: {
                                theme: 'light',
                                y: {
                                    formatter: (val) => val + ' trades'
                                }
                            }
                        };
                        this.chart = new ApexCharts(document.querySelector("#distChart"), options);
                        this.chart.render();
                    }
                }));

                Alpine.data('radarChart', (data) => ({
                    chart: null,
                    init() {
                        if (!data) return;

                        // Extraer etiquetas y valores del objeto PHP
                        const categories = Object.keys(data);
                        const values = Object.values(data);

                        const options = {
                            series: [{
                                name: 'Puntuación',
                                data: values,
                            }],
                            chart: {
                                height: 280, // Un poco más alto para que quepan las etiquetas
                                type: 'radar',
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                },
                                animations: {
                                    enabled: true
                                }
                            },
                            colors: ['#8B5CF6'], // Un morado vibrante (Purple-500)
                            fill: {
                                opacity: 0.2,
                                colors: ['#8B5CF6']
                            },
                            stroke: {
                                show: true,
                                width: 2,
                                colors: ['#7C3AED'], // Borde más oscuro
                                dashArray: 0
                            },
                            markers: {
                                size: 4,
                                colors: ['#fff'],
                                strokeColors: '#7C3AED',
                                strokeWidth: 2,
                                hover: {
                                    size: 6
                                }
                            },
                            xaxis: {
                                categories: categories,
                                labels: {
                                    show: true,
                                    style: {
                                        colors: ['#64748B', '#64748B', '#64748B', '#64748B', '#64748B'],
                                        fontSize: '11px',
                                        fontFamily: 'Inter, sans-serif',
                                        fontWeight: 600
                                    }
                                }
                            },
                            yaxis: {
                                show: false, // Ocultar los anillos concéntricos numéricos
                                min: 0,
                                max: 100,
                                tickAmount: 4,
                            },
                            plotOptions: {
                                radar: {
                                    polygons: {
                                        strokeColors: '#e2e8f0', // Color de la telaraña
                                        connectorColors: '#e2e8f0',
                                    }
                                }
                            },
                            tooltip: {
                                theme: 'light',
                                y: {
                                    formatter: function(val) {
                                        return val + " / 100";
                                    }
                                }
                            }
                        };

                        this.chart = new ApexCharts(document.querySelector("#radarChart"), options);
                        this.chart.render();
                    }
                }));

                Alpine.data('mistakesChart', (data) => ({
                    chart: null,
                    hasData: false,
                    init() {
                        if (!data || data.length === 0) {
                            this.hasData = false;
                            return;
                        }
                        this.hasData = true;

                        const categories = data.map(d => d.name);
                        const counts = data.map(d => d.count);
                        const costs = data.map(d => d.total_loss);

                        // Paleta de seguridad (Vibrante)
                        const palette = ['#F43F5E', '#8B5CF6', '#F59E0B', '#3B82F6', '#10B981'];
                        const colors = data.map((d, index) => d.color ? d.color : palette[index % palette.length]);

                        const options = {
                            series: [{
                                name: 'Repeticiones',
                                data: counts
                            }],
                            chart: {
                                type: 'bar',
                                height: 280, // Un poco más alto para que respire
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                },
                                animations: {
                                    enabled: true
                                }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 3,
                                    horizontal: true,
                                    distributed: true,
                                    barHeight: '70%', // Barras más gruesas para que quepa bien el texto
                                    dataLabels: {
                                        position: 'bottom' // Obliga al texto a empezar a la izquierda
                                    }
                                }
                            },
                            colors: colors,
                            dataLabels: {
                                enabled: true,
                                textAnchor: 'center', // Alineación izquierda
                                offsetX: 15, // <--- AQUÍ ESTÁ EL PADDING QUE PEDÍAS
                                style: {
                                    colors: ['#fff'],
                                    fontSize: '11px',
                                    fontWeight: 800,
                                    fontFamily: 'Inter, sans-serif',
                                    // Sombra importante para leer texto blanco sobre barras claras (ej: amarillo)
                                    textShadow: '0px 1px 2px rgba(0,0,0,0.6)'
                                },
                                formatter: function(val, opt) {
                                    // Formato: "FOMO: 5"
                                    return opt.w.globals.labels[opt.dataPointIndex] + ": " + val;
                                }
                            },
                            xaxis: {
                                categories: categories,
                                labels: {
                                    show: false
                                },
                                axisBorder: {
                                    show: false
                                },
                                axisTicks: {
                                    show: false
                                }
                            },
                            yaxis: {
                                labels: {
                                    show: false
                                }
                            },
                            grid: {
                                show: false,
                                padding: {
                                    left: 0,
                                    right: 0,
                                    top: 0,
                                    bottom: 0
                                }
                            },
                            legend: {
                                show: false
                            },

                            tooltip: {
                                theme: 'light',
                                // Tooltip FIJO en la esquina superior derecha del gráfico
                                fixed: {
                                    enabled: true,
                                    position: 'topRight',
                                    offsetX: 0,
                                    offsetY: 30, // Bajado un poco para no tapar el título si el gráfico es corto
                                },
                                custom: function({
                                    series,
                                    seriesIndex,
                                    dataPointIndex,
                                    w
                                }) {
                                    var count = w.globals.series[seriesIndex][dataPointIndex];
                                    var cost = costs[dataPointIndex];
                                    var color = w.globals.colors[dataPointIndex];
                                    var label = w.globals.labels[dataPointIndex];
                                    var costClass = cost < 0 ? 'text-rose-600' : 'text-emerald-600';

                                    return `
                        <div class="px-3 py-2 text-xs bg-white border border-gray-100 shadow-lg rounded-lg" style="border-left: 4px solid ${color}; min-width: 140px;">
                            <div class="font-bold text-gray-800 mb-1 truncate">${label}</div>
                            <div class="flex justify-between items-center text-gray-500 gap-3">
                                <span>${count} veces</span>
                                <span class="font-black ${costClass}">${cost.toFixed(0)} $</span>
                            </div>
                        </div>
                    `;
                                }
                            }
                        };

                        // Render con pequeño delay de seguridad
                        setTimeout(() => {
                            if (document.querySelector("#mistakesChart")) {
                                this.chart = new ApexCharts(document.querySelector("#mistakesChart"), options);
                                this.chart.render();
                            }
                        }, 50);
                    }
                }));


            });
        </script>
    </div>


</div>
