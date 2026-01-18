import {
    createChart,
    CandlestickSeries,
    createSeriesMarkers,
} from "lightweight-charts";

console.log("🚀 [DEBUG] dashboard.js cargado (SAFE MODE)");

class TradeChartController {
    constructor(container) {
        this.container = container;
        this.chart = null;
        this.series = null;
        this.priceLines = [];
        this.seriesMarkers = null; // 👈 manejador de marcadores
        this.init();
    }

    init() {
        if (this.container.clientWidth === 0) return;

        const options = {
            layout: {
                background: { type: "solid", color: "#111827" },
                textColor: "#9CA3AF",
            },
            grid: {
                vertLines: { color: "#374151" },
                horzLines: { color: "#374151" },
            },
            width: this.container.clientWidth,
            height: 400,
            timeScale: {
                timeVisible: true,
                secondsVisible: false,
                borderColor: "#4B5563",
            },
            rightPriceScale: { borderColor: "#4B5563" },
        };

        try {
            this.chart = createChart(this.container, options);

            // Añadimos la serie y la guardamos
            this.series = this.chart.addSeries(CandlestickSeries, {
                upColor: "#10B981",
                downColor: "#EF4444",
                borderVisible: false,
                wickUpColor: "#10B981",
                wickDownColor: "#EF4444",
            });

            this.seriesMarkers = createSeriesMarkers(this.series, []);
            // DEBUG CRÍTICO: Vamos a ver qué métodos tiene realmente esta serie
            console.log("✅ SERIE CREADA. Métodos disponibles:", this.series);

            new ResizeObserver((entries) => {
                if (!entries.length || !this.chart) return;
                const { width, height } = entries[0].contentRect;
                if (width > 0) this.chart.applyOptions({ width, height });
            }).observe(this.container);
        } catch (e) {
            console.error("🛑 Error init:", e);
        }
    }

    drawTradeLines(entryPrice, exitPrice, direction) {
        // Protección: Si no existe removePriceLine, salimos
        if (!this.series || typeof this.series.removePriceLine !== "function")
            return;

        try {
            this.priceLines.forEach((line) =>
                this.series.removePriceLine(line),
            );
            this.priceLines = [];

            if (!entryPrice || !exitPrice) return;

            const isLong = direction === "long";
            const isWin = isLong
                ? exitPrice >= entryPrice
                : exitPrice <= entryPrice;
            const exitColor = isWin ? "#10B981" : "#EF4444";

            this.priceLines.push(
                this.series.createPriceLine({
                    price: parseFloat(entryPrice),
                    color: "#3B82F6",
                    lineWidth: 2,
                    lineStyle: 2,
                    axisLabelVisible: true,
                    title: "ENTRADA",
                }),
            );

            this.priceLines.push(
                this.series.createPriceLine({
                    price: parseFloat(exitPrice),
                    color: exitColor,
                    lineWidth: 2,
                    lineStyle: 0,
                    axisLabelVisible: true,
                    title: "SALIDA",
                }),
            );
        } catch (e) {
            console.warn("⚠️ Error dibujando líneas:", e);
        }
    }

    async loadData(path, entryPrice, exitPrice, direction) {
        // Si la serie no existe, intentamos iniciar
        if (!this.series) this.init();
        if (!this.series) return false;

        if (!path) {
            this.series.setData([]);
            return false;
        }

        try {
            console.log("🔍 Cargando path:", path);

            const res = await fetch(`/storage/${path}?t=${Date.now()}`);
            console.log("📊 Response OK?", res.ok, res.status);

            if (!res.ok) throw new Error("404");
            const data = await res.json();
            console.log("📈 Datos crudos:", data);
            console.log("🕯️ Velas count:", data.candles?.length || 0);

            if (data.candles && data.candles.length > 0) {
                this.series.setData(data.candles);

                // MARCADORES
                if (
                    this.seriesMarkers &&
                    data.markers &&
                    Array.isArray(data.markers)
                ) {
                    const candleTimes = data.candles.map((c) => c.time);

                    const adjustedMarkers = data.markers
                        .map((m) => {
                            const closestTime = candleTimes.reduce(
                                (prev, curr) => {
                                    return Math.abs(curr - m.time) <
                                        Math.abs(prev - m.time)
                                        ? curr
                                        : prev;
                                },
                            );
                            return {
                                ...m,
                                time: closestTime,
                                size: 1,
                            };
                        })
                        .sort((a, b) => a.time - b.time);

                    this.seriesMarkers.setMarkers(adjustedMarkers);
                }

                this.drawTradeLines(entryPrice, exitPrice, direction);

                setTimeout(() => {
                    if (this.chart) this.chart.timeScale().fitContent();
                }, 50);

                return true;
            }
            return false;
        } catch (e) {
            console.error("🛑 Error carga:", e);
        }
        return false;
    }
}

document.addEventListener("alpine:init", () => {
    Alpine.data("dashboard", () => ({
        winRateChart: null,
        tableRecents: null,
        showLoading: false,
        showModalDetails: false,
        currentView: "list", // 'list' o 'detail'

        init() {
            const self = this;

            if (!$.fn.DataTable.isDataTable("#table_history")) {
                self.tableRecents = $("#table_history").DataTable({
                    ajax: {
                        url: "/trades/dashboard",
                        data: function (d) {
                            // Agregar parámetros adicionales para el filtro
                            d.accounts = self.$wire.selectedAccounts;
                        },
                    },
                    // lengthMenu: [5, 10, 20, 25, 50],
                    pageLength: 10,
                    order: [[1, "desc"]],
                    searching: false,
                    lengthChange: false,
                    columns: [
                        { data: "id" },
                        { data: "exit_time" },
                        { data: "trade_asset.symbol" },
                        { data: "pnl" },
                    ],
                    pagingType: "numbers",
                    language: {
                        url: "/datatable/es-ES.json",
                    },
                    columnDefs: [
                        { visible: false, targets: 0 },
                        {
                            targets: 3,
                            render: function (data, type, row) {
                                // Aseguramos que sea un número para evitar errores con toFixed
                                let val = parseFloat(data);
                                let formatted = val.toFixed(2);

                                if (val >= 0) {
                                    // ✅ CORRECTO: Usando comillas invertidas ` ` para el HTML
                                    return `<span class="text-green-600 font-bold">
                        +${formatted}
                    </span>`;
                                } else {
                                    // Para negativos, normalmente querrás ver el número en rojo
                                    return `<span class="text-red-600 font-bold">
                        ${formatted}
                    </span>`;
                                }
                            },
                        },
                        {
                            targets: "_all", // Aplica a todas las columnas
                            className: "dt-left", // Usa 'dt-left' si no usas Bootstrap
                        },
                    ],
                });
            }

            // 3. Watchers (Solo sincronizamos cuando el usuario CAMBIA algo)
            this.$watch("showModalDetails", (value) => {
                if (value) {
                    document.body.classList.add("overflow-hidden");
                } else {
                    document.body.classList.remove("overflow-hidden");
                }
            });

            // 1. Inicializar gráfico al cargar
            this.renderWinRateChart();
            // ... otros inits ...
            this.renderAvgPnLChart(); // 👈 Inicializar

            // ... otros renders ...
            this.renderDailyWinLossChart(); // 👈 Inicializar

            this.renderEvolutionChart();

            this.renderDailyPnLChart(); // 👈 Inicializar

            // 2. Escuchar cambios desde Livewire (cuando cambias el select)
            Livewire.on("dashboard-updated", () => {
                // 👇 RECARGAR DATATABLE
                this.showLoading = true;
                if (this.tableRecents) {
                    // reload(null, false) recarga los datos manteniendo la paginación actual (opcional)
                    this.tableRecents.ajax.reload(() => {
                        console.log("✅ Tabla recargada completamente");

                        // Aquí puedes ejecutar tu lógica:
                        // - Actualizar contadores externos
                        // - Reinicializar tooltips
                        // - Calcular totales en JS
                        this.showLoading = false;
                    }, false); // 'false' evita que la paginación vuelva a la página 1
                }
                this.renderWinRateChart();
                this.renderAvgPnLChart();
                this.renderDailyWinLossChart();
                this.renderEvolutionChart();
                this.renderDailyPnLChart(); // 👈 Inicializa
            });
        },

        renderWinRateChart() {
            const data = this.$wire.winRateChartData;
            const series = data?.series || [0, 0];
            const isEmpty = series[0] === 0 && series[1] === 0;

            const chartSeries = isEmpty ? [1] : series;
            const colors = isEmpty ? ["#F3F4F6"] : ["#10B981", "#F43F5E"];
            const chartLabels = isEmpty
                ? ["Sin operaciones"]
                : ["Ganadas", "Perdidas"];

            const options = {
                series: chartSeries,
                chart: {
                    type: "donut",
                    width: 140,
                    height: 140,
                    fontFamily: "Inter, sans-serif",
                    sparkline: { enabled: true },
                    animations: { enabled: true },
                },
                labels: chartLabels,

                // Tooltip personalizado
                tooltip: {
                    enabled: !isEmpty,
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            return val + " días";
                        },
                    },
                },

                colors: colors,
                stroke: { width: 0 },
                plotOptions: {
                    pie: {
                        startAngle: -90,
                        endAngle: 90,
                        offsetY: 0,

                        // 👇 ESTO EVITA QUE SE AGRANDE AL HACER CLICK
                        expandOnClick: false,

                        donut: {
                            size: "75%",
                            labels: { show: false },
                        },
                    },
                },
                dataLabels: { enabled: false },
                states: {
                    active: { filter: { type: "none" } },
                },
            };

            if (this.winRateChart) {
                this.winRateChart.updateOptions(options);
                this.winRateChart.updateSeries(chartSeries);
            } else {
                const el = this.$refs.winRateChart;
                if (el) {
                    this.winRateChart = new ApexCharts(el, options);
                    this.winRateChart.render();
                }
            }
        },

        renderDailyPnLChart() {
            const payload = this.$wire.dailyPnLChartData;
            const categories = payload?.categories || [];
            const seriesData = payload?.data || [];

            if (seriesData.length === 0) return;

            // Configuración base
            const options = {
                series: [
                    {
                        name: "PnL Diario",
                        data: seriesData,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 200,
                    fontFamily: "Inter, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: true },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        borderRadiusApplication: "end",
                        columnWidth: "50%",
                        colors: {
                            ranges: [
                                {
                                    from: -1000000000,
                                    to: -0.01,
                                    color: "#F43F5E",
                                },
                                { from: 0, to: 1000000000, color: "#10B981" },
                            ],
                        },
                    },
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: categories, // Importante para la carga inicial
                    labels: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: "#6b7280" },
                        formatter: function (val) {
                            return val.toFixed(0) + " €";
                        },
                    },
                },
                grid: {
                    borderColor: "#f3f4f6",
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } },
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            return new Intl.NumberFormat("es-ES", {
                                style: "currency",
                                currency: "EUR",
                            }).format(val);
                        },
                    },
                },
            };

            if (this.dailyPnLBarChart) {
                // 👇 SOLUCIÓN: Actualizar TODO en un solo golpe
                this.dailyPnLBarChart.updateOptions({
                    series: [
                        {
                            name: "PnL Diario", // Mantiene el nombre correcto
                            data: seriesData,
                        },
                    ],
                    xaxis: {
                        categories: categories, // Actualiza las fechas ocultas para el tooltip
                    },
                });
            } else {
                const el = this.$refs.dailyPnLBarChart;
                if (el) {
                    this.dailyPnLBarChart = new ApexCharts(el, options);
                    this.dailyPnLBarChart.render();
                }
            }
        },

        renderAvgPnLChart() {
            const data = this.$wire.avgPnLChartData;
            const avgWin = data?.avg_win || 0;
            // Aseguramos que sea negativo para que vaya a la izquierda
            let avgLoss = data?.avg_loss || 0;
            if (avgLoss > 0) avgLoss = avgLoss * -1;

            // Si está vacío, ponemos datos dummy invisibles o 0
            const isEmpty = avgWin === 0 && avgLoss === 0;

            const options = {
                series: [
                    {
                        name: "Ganancia Media",
                        data: [avgWin], // Array de 1 elemento
                    },
                    {
                        name: "Pérdida Media",
                        data: [avgLoss], // Array de 1 elemento (negativo)
                    },
                ],
                chart: {
                    type: "bar",
                    height: 150, // Altura ajustada
                    stacked: true, // 👈 ESTO CREA EL EFECTO PIRÁMIDE (Apilado)
                    fontFamily: "Inter, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: true },
                },
                colors: ["#10B981", "#F43F5E"], // Verde, Rojo
                plotOptions: {
                    bar: {
                        horizontal: true, // Barras tumbadas
                        barHeight: "40%", // Grosor de la barra (juega con esto)
                        borderRadius: 4, // Bordes redondeados
                        borderRadiusApplication: "end", // Redondear solo los extremos exteriores
                    },
                },
                dataLabels: {
                    enabled: true, // Mostrar los números dentro de la barra
                    formatter: function (val) {
                        // Quitamos el signo negativo visualmente
                        return Math.abs(val).toFixed(2) + " €";
                    },
                    style: {
                        fontSize: "12px",
                        colors: ["#fff"],
                    },
                },
                stroke: {
                    width: 1,
                    colors: ["#fff"],
                },
                grid: {
                    xaxis: {
                        lines: { show: true }, // Muestra líneas verticales de guía
                    },
                    yaxis: {
                        lines: { show: false },
                    },
                },
                yaxis: {
                    // Ocultamos el eje Y porque solo hay una categoría ("Promedio")
                    // y ya se entiende por el contexto
                    show: false,
                },
                xaxis: {
                    categories: ["Promedio"], // Una sola categoría compartida
                    labels: {
                        formatter: function (val) {
                            // El eje X también sin negativos
                            return Math.abs(Math.round(val));
                        },
                        style: {
                            colors: "#9ca3af",
                            fontSize: "11px",
                        },
                    },
                },
                tooltip: {
                    shared: false, // Tooltip individual por cada lado
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            // Tooltip sin negativos
                            return Math.abs(val).toFixed(2) + " €";
                        },
                    },
                },
                // Estado hover desactivado para que no cambie de color raro
                states: {
                    hover: { filter: { type: "none" } },
                    active: { filter: { type: "none" } },
                },
            };

            if (this.avgPnLChart) {
                this.avgPnLChart.updateOptions(options);
            } else {
                const el = this.$refs.avgPnLChart;
                if (el) {
                    this.avgPnLChart = new ApexCharts(el, options);
                    this.avgPnLChart.render();
                }
            }
        },

        renderDailyWinLossChart() {
            const data = this.$wire.dailyWinLossData;
            const series = data?.series || [0, 0];
            const isEmpty = series[0] === 0 && series[1] === 0;

            const chartSeries = isEmpty ? [1] : series;
            const colors = isEmpty ? ["#F3F4F6"] : ["#10B981", "#F43F5E"];

            // Etiquetas para el tooltip
            const chartLabels = isEmpty
                ? ["Sin datos"]
                : ["Ganadores", "Perdedores"];

            const options = {
                series: chartSeries,
                labels: chartLabels,
                chart: {
                    type: "donut",
                    width: 140, // Igual que en el HTML
                    height: 140, // Doble del HTML
                    fontFamily: "Inter, sans-serif",
                    sparkline: { enabled: true },
                    animations: { enabled: true },
                },
                colors: colors,
                stroke: { width: 0 },

                // Tooltip personalizado
                tooltip: {
                    enabled: !isEmpty,
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            return val + " días";
                        },
                    },
                },

                plotOptions: {
                    pie: {
                        startAngle: -90,
                        endAngle: 90,
                        offsetY: 0, // Ajuste para subirlo
                        expandOnClick: false,
                        donut: {
                            size: "75%",
                            labels: { show: false },
                        },
                    },
                },
                dataLabels: { enabled: false },
                states: {
                    active: { filter: { type: "none" } },
                },
            };

            if (this.dailyWinLossChart) {
                this.dailyWinLossChart.updateOptions(options);
                this.dailyWinLossChart.updateSeries(chartSeries);
            } else {
                const el = this.$refs.dailyWinLossChart;
                if (el) {
                    this.dailyWinLossChart = new ApexCharts(el, options);
                    this.dailyWinLossChart.render();
                }
            }
        },

        renderEvolutionChart() {
            const payload = this.$wire.evolutionChartData;
            const categories = payload?.categories || [];
            const seriesData = payload?.data || [];
            const isPositive = payload?.is_positive ?? true;

            if (seriesData.length === 0) {
                if (this.evolutionChart) this.evolutionChart.destroy();
                return;
            }

            const mainColor = isPositive ? "#10B981" : "#F43F5E";

            const options = {
                series: [
                    {
                        name: "PnL Acumulado",
                        data: seriesData,
                    },
                ],
                chart: {
                    type: "area",
                    height: 200,
                    width: "100%",
                    fontFamily: "Inter, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: true },
                },
                colors: [mainColor], // Color inicial
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 100],
                    },
                },
                stroke: { curve: "smooth", width: 2 },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: categories,
                    type: "category",
                    labels: { show: false },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: "#6b7280" },
                        formatter: function (value) {
                            return value.toFixed(0) + " €";
                        },
                    },
                },
                grid: {
                    borderColor: "#f3f4f6",
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } },
                    xaxis: { lines: { show: false } },
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            return new Intl.NumberFormat("es-ES", {
                                style: "currency",
                                currency: "EUR",
                            }).format(val);
                        },
                    },
                },
            };

            if (this.evolutionChart) {
                // 👇 SOLUCIÓN: Forzar actualización de Color, Datos y Fechas a la vez
                this.evolutionChart.updateOptions({
                    colors: [mainColor], // Actualiza Verde/Rojo
                    series: [
                        {
                            name: "PnL Acumulado", // Evita que salga "series-1"
                            data: seriesData,
                        },
                    ],
                    xaxis: {
                        categories: categories, // Actualiza las fechas
                    },
                });
            } else {
                const el = this.$refs.evolutionChart;
                if (el) {
                    this.evolutionChart = new ApexCharts(el, options);
                    this.evolutionChart.render();
                }
            }
        },

        closeDayModal() {
            this.showModalDetails = false;
            this.aiAnalysis = null;
            this.currentView = "list";
            this.$wire.$set("aiAnalysis", null);
        },

        openDayDetails(value) {
            this.showModalDetails = true;
            this.$wire.call("openDayDetails", value);
        },
    }));

    Alpine.data("chartViewer", () => {
        let controller = null;

        return {
            loading: false,
            hasData: false,

            init() {
                this.$nextTick(() => {
                    if (this.$refs.chartContainer) {
                        controller = new TradeChartController(
                            this.$refs.chartContainer,
                        );
                    }
                });

                window.addEventListener("trade-selected", (e) => {
                    this.load(
                        e.detail.path,
                        e.detail.entry,
                        e.detail.exit,
                        e.detail.direction,
                    );
                });
            },

            load(path, entry, exit, direction) {
                // Si no hay controller, reintentamos un poco
                if (!controller) {
                    if (this.$refs.chartContainer) {
                        controller = new TradeChartController(
                            this.$refs.chartContainer,
                        );
                    } else {
                        setTimeout(
                            () => this.load(path, entry, exit, direction),
                            200,
                        );
                        return;
                    }
                }

                this.loading = true;
                this.hasData = false;

                controller
                    .loadData(path, entry, exit, direction)
                    .then((success) => {
                        this.hasData = success;
                        this.loading = false;
                    });
            },
        };
    });
});
