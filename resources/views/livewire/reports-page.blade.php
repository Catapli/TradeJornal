<div class="min-h-screen bg-gray-50 p-6"
     x-data>

    {{-- HEADER --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900">Laboratorio</h1>
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

            {{-- Panel Simulador --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-5 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                    <i class="fa-solid fa-sliders text-indigo-600"></i> Filtros "What-If"
                </h3>

                <div class="space-y-5">
                    {{-- Input: Límite Diario --}}
                    <div>
                        <label class="mb-1 block text-xs font-bold text-gray-500">Fatiga Intradía</label>
                        <select class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500"
                                wire:model.live="scenarios.max_daily_trades">
                            <option value="">Todas las operaciones</option>
                            <option value="1">Solo la 1ª del día</option>
                            <option value="2">Primeras 2 del día</option>
                            <option value="3">Primeras 3 del día</option>
                        </select>
                        <p class="mt-1 text-[10px] text-gray-400">Simula parar de operar tras X trades.</p>
                    </div>

                    <div class="border-t border-gray-100"></div>

                    {{-- Toggles --}}
                    <div class="space-y-3">
                        <label class="group flex cursor-pointer items-center justify-between">
                            <span class="text-sm text-gray-600 transition group-hover:text-indigo-600">Ignorar Viernes</span>
                            <div class="relative inline-block w-10 select-none align-middle">
                                <input class="peer sr-only"
                                       type="checkbox"
                                       wire:model.live="scenarios.no_fridays" />
                                <div
                                     class="peer h-5 w-10 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white">
                                </div>
                            </div>
                        </label>

                        <label class="group flex cursor-pointer items-center justify-between">
                            <span class="text-sm text-gray-600 transition group-hover:text-indigo-600">Solo Largos (Buy)</span>
                            <div class="relative inline-block w-10 select-none align-middle">
                                <input class="peer sr-only"
                                       type="checkbox"
                                       wire:model.live="scenarios.only_longs" />
                                <div
                                     class="peer h-5 w-10 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white">
                                </div>
                            </div>
                        </label>

                        <label class="group flex cursor-pointer items-center justify-between">
                            <span class="text-sm text-gray-600 transition group-hover:text-indigo-600">Quitar 5 peores</span>
                            <div class="relative inline-block w-10 select-none align-middle">
                                <input class="peer sr-only"
                                       type="checkbox"
                                       wire:model.live="scenarios.remove_worst" />
                                <div
                                     class="peer h-5 w-10 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white">
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                @if ($simulatedStats)
                    <div class="mt-6 border-t border-gray-100 pt-4 text-center">
                        @php $diff = $simulatedStats['expectancy'] - $realStats['expectancy']; @endphp
                        <span class="{{ $diff > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            Impacto: {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }} $ / trade
                        </span>
                    </div>
                @endif
            </div>

            {{-- Tarjeta SQN --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-1 font-bold text-gray-900">System Quality (SQN)</h3>
                <div class="mb-2 mt-4 flex items-end gap-2">
                    <span class="text-4xl font-black text-gray-900">{{ $realStats['sqn'] ?? '0.0' }}</span>
                    @if ($simulatedStats)
                        <span class="flex items-center text-xl font-bold text-indigo-500">
                            <i class="fa-solid fa-arrow-right mx-1 text-sm"></i> {{ $simulatedStats['sqn'] }}
                        </span>
                    @endif
                </div>
                <div class="mb-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full bg-gray-400"
                         style="width: {{ min(100, (($realStats['sqn'] ?? 0) / 5) * 100) }}%"></div>
                </div>
                <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                    <span>Pobre (< 1.6)</span>
                            <span>Santo Grial (> 5.0)</span>
                </div>
                <div class="mt-4 rounded-lg bg-indigo-50 p-3 text-xs leading-relaxed text-indigo-800">
                    @if (($realStats['sqn'] ?? 0) < 1.6)
                        ⚠️ Tu sistema es difícil de operar. La volatilidad de resultados es alta comparada con el beneficio.
                    @elseif(($realStats['sqn'] ?? 0) < 3.0)
                        ✅ Buen sistema. Tienes una ventaja estadística clara.
                    @else
                        🏆 Sistema excelente. Considera aumentar el tamaño de posición (Position Sizing).
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

                {{-- Scatter Plot: Tiempo vs Dinero --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                     x-data="scatterChart(@js($scatterData))">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-900">
                                <i class="fa-solid fa-hourglass-end text-amber-500"></i> Tiempo vs Dinero
                            </h3>
                            <p class="mt-1 text-xs text-gray-400">¿Aguantas las pérdidas más que las ganancias?</p>
                        </div>
                    </div>
                    <div id="scatterChart"
                         class="h-[250px] w-full"></div>
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

                // 3. SCATTER CHART (TIEMPO VS DINERO)
                Alpine.data('scatterChart', (data) => ({
                    chart: null,
                    init() {
                        if (!data || data.length === 0) return;
                        const options = {
                            series: [{
                                name: "Trades",
                                data: data
                            }],
                            chart: {
                                type: 'scatter',
                                height: 250,
                                fontFamily: 'Inter, sans-serif',
                                toolbar: {
                                    show: false
                                },
                                zoom: {
                                    enabled: false
                                }
                            },
                            colors: ['#10B981'],
                            plotOptions: {
                                scatter: {
                                    markers: {
                                        size: 5,
                                        strokeWidth: 0,
                                        hover: {
                                            size: 7
                                        }
                                    }
                                }
                            },
                            markers: {
                                colors: data.map(d => d.y >= 0 ? '#10B981' : '#EF4444')
                            },
                            xaxis: {
                                type: 'numeric',
                                title: {
                                    text: 'Minutos',
                                    style: {
                                        fontSize: '10px',
                                        color: '#94a3b8'
                                    }
                                },
                                labels: {
                                    style: {
                                        colors: '#94a3b8',
                                        fontSize: '10px'
                                    }
                                }
                            },
                            yaxis: {
                                title: {
                                    text: 'PnL ($)',
                                    style: {
                                        fontSize: '10px',
                                        color: '#94a3b8'
                                    }
                                },
                                labels: {
                                    style: {
                                        colors: '#94a3b8',
                                        fontSize: '10px'
                                    }
                                }
                            },
                            grid: {
                                borderColor: '#f3f4f6',
                                xaxis: {
                                    lines: {
                                        show: true
                                    }
                                },
                                yaxis: {
                                    lines: {
                                        show: true
                                    }
                                },
                                row: {
                                    colors: undefined,
                                    opacity: 0.5
                                }
                            },
                            annotations: {
                                yaxis: [{
                                    y: 0,
                                    borderColor: '#94a3b8',
                                    strokeDashArray: 0,
                                    borderWidth: 1,
                                    opacity: 0.5
                                }]
                            },
                            tooltip: {
                                theme: 'light',
                                custom: ({
                                    series,
                                    seriesIndex,
                                    dataPointIndex,
                                    w
                                }) => {
                                    var d = w.config.series[seriesIndex].data[dataPointIndex];
                                    return `<div class="px-3 py-2 text-xs"><div class="font-bold text-gray-700">#${d.ticket}</div><div>${d.x} min</div><div class="${d.y >= 0 ? 'text-emerald-600' : 'text-rose-600'} font-bold">${d.y} $</div></div>`;
                                }
                            }
                        };
                        this.chart = new ApexCharts(document.querySelector("#scatterChart"), options);
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
            });
        </script>
    </div>


</div>
