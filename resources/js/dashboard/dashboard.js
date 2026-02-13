import {
    createChart,
    CandlestickSeries,
    createSeriesMarkers,
    HistogramSeries,
    LineSeries,
} from "lightweight-charts";

console.log("🚀 [DEBUG] Multi-TF Chart Controller Loaded");

class TradeChartController {
    constructor(container) {
        this.container = container;
        this.chart = null;
        this.series = null;
        this.seriesMarkers = null;
        this.priceLines = [];

        // ALMACEN DE DATOS
        this.fullData = null; // Aquí guardamos todo el JSON
        this.currentTf = "5m"; // Default

        this.init();
    }

    init() {
        if (this.container.clientWidth === 0) return;

        const options = {
            // 1. FONDO Y TEXTO (Color clásico TV Dark)
            layout: {
                background: { type: "solid", color: "#131722" }, // El negro azulado de TV
                textColor: "#d1d4dc",
            },

            // 2. CUADRÍCULA (GRID) - Las "líneas" que mencionas
            grid: {
                vertLines: {
                    visible: true, // Antes lo tenías en false
                    color: "#363c4e", // Gris oscuro sutil
                    style: 0, // 0 = Línea sólida, 1 = Punteada
                },
                horzLines: {
                    visible: true,
                    color: "#363c4e",
                    style: 0,
                },
            },

            // 3. CURSOR (CROSSHAIR) - Las líneas punteadas que siguen al ratón
            crosshair: {
                mode: 1, // Magnetismo (0=Normal, 1=Magnet)
                vertLine: {
                    width: 1,
                    color: "#758696",
                    style: 3, // 3 = Punteado (Dashed)
                    labelBackgroundColor: "#758696",
                },
                horzLine: {
                    width: 1,
                    color: "#758696",
                    style: 3,
                    labelBackgroundColor: "#758696",
                },
            },

            // 4. DIMENSIONES
            width: this.container.clientWidth,
            height: 400,

            // 5. ESCALA DE TIEMPO (Eje X)
            timeScale: {
                timeVisible: true,
                secondsVisible: false,
                borderColor: "#485c7b",
                barSpacing: 10, // Espacio entre velas (zoom inicial)
            },

            // 6. ESCALA DE PRECIO (Eje Y)
            rightPriceScale: {
                borderColor: "#485c7b",
                scaleMargins: {
                    top: 0.1, // Margen arriba para que no toque el techo
                    bottom: 0.1, // Margen abajo
                },
            },
        };

        try {
            this.chart = createChart(this.container, options);

            this.series = this.chart.addSeries(CandlestickSeries, {
                upColor: "#089981", // Verde TV
                downColor: "#f23645", // Rojo TV
                borderVisible: false, // Sin borde para look más limpio
                wickUpColor: "#089981", // Mecha verde
                wickDownColor: "#f23645", // Mecha roja
            });

            // --- AÑADIR ESTO ---
            this.volumeSeries = this.chart.addSeries(HistogramSeries, {
                color: "#26a69a",
                priceFormat: { type: "volume" },
                priceScaleId: "vol_scale", // Misma escala horizontal
                scaleMargins: {
                    top: 0.85, // Deja el 80% de arriba libre para las velas
                    bottom: 0,
                },
            });

            // --- NUEVA SERIE EMA ---
            this.emaSeries = this.chart.addSeries(LineSeries, {
                color: "#fb8c00", // Naranja vibrante
                lineWidth: 2,
                crosshairMarkerVisible: false, // Para no saturar el cursor
                priceScaleId: "right", // Usa la misma escala que el precio (derecha)
                lineStyle: 0, // 0 = Sólida
            });

            // 2. Configurar esa escala específica para que solo ocupe la parte baja
            this.chart.priceScale("vol_scale").applyOptions({
                scaleMargins: {
                    top: 0.75, // Deja el 75% superior vacío (el volumen ocupará el 25% inferior)
                    bottom: 0,
                },
            });

            this.seriesMarkers = createSeriesMarkers(this.series, []);

            new ResizeObserver((entries) => {
                if (!entries.length || !this.chart) return;
                const { width, height } = entries[0].contentRect;
                if (width > 0) this.chart.applyOptions({ width, height });
            }).observe(this.container);
        } catch (e) {
            console.error("🛑 Error init:", e);
        }
    }

    toggleVolume(isVisible) {
        if (this.volumeSeries) {
            this.volumeSeries.applyOptions({
                visible: isVisible,
            });
        }
    }

    toggleEma(isVisible) {
        if (this.emaSeries) {
            this.emaSeries.applyOptions({
                visible: isVisible,
            });
        }
    }

    // --- NUEVO MÉTODO PARA CAMBIAR TF ---
    renderTimeframe(tf) {
        if (!this.fullData || !this.series) return;

        // Verificar si existe el TF en el JSON
        const candles = this.fullData.timeframes[tf];

        if (!candles || candles.length === 0) {
            console.warn(`⚠️ No data for timeframe: ${tf}`);
            // Podrías mostrar un toast o alerta aquí
            return;
        }

        console.log(`🔄 Switching to ${tf} (${candles.length} candles)`);

        // 1. Actualizar Velas
        this.series.setData(candles);

        // 2. Actualizar Volumen (CON LÓGICA DE COLOR TV)
        // Si la vela sube (C >= O) -> Verde Transparente
        // Si la vela baja (C < O) -> Rojo Transparente
        const volumeData = candles.map((c) => ({
            time: c.time,
            value: c.volume || 0, // Protección por si es null
            color:
                c.close >= c.open
                    ? "rgba(38, 166, 154, 0.4)" // Verde TV muy suave
                    : "rgba(239, 83, 80, 0.4)", // Rojo TV muy suave
        }));

        this.volumeSeries.setData(volumeData); // <--- Pintar volumen

        // 3. ACTUALIZAR EMA
        const emaData = candles
            .filter((c) => c.ema !== null) // Filtramos nulos (el principio del cálculo)
            .map((c) => ({
                time: c.time,
                value: c.ema,
            }));

        this.emaSeries.setData(emaData);

        this.currentTf = tf;

        // 2. Recalcular Marcadores (Snap to nearest candle)
        // Los marcadores son timestamp exactos, pero en M15 o H1
        // la vela exacta puede no existir, hay que buscar la más cercana.
        if (this.fullData.markers && Array.isArray(this.fullData.markers)) {
            const candleTimes = candles.map((c) => c.time);

            const adjustedMarkers = this.fullData.markers
                .map((m) => {
                    // Encontrar la vela más cercana temporalmente
                    const closestTime = candleTimes.reduce((prev, curr) => {
                        return Math.abs(curr - m.time) < Math.abs(prev - m.time)
                            ? curr
                            : prev;
                    });

                    return {
                        ...m,
                        time: closestTime,
                        size: 1,
                    };
                })
                .sort((a, b) => a.time - b.time); // Lightweight charts exige orden

            this.seriesMarkers.setMarkers(adjustedMarkers);
        }

        // 3. Re-dibujar líneas de precio (Entrada/Salida)
        // Necesitamos la info del trade, que ya venía en loadData o podemos guardarla en this
        if (this.tradeInfo) {
            this.drawTradeLines(
                this.tradeInfo.entry,
                this.tradeInfo.exit,
                this.tradeInfo.direction,
            );
        }

        // 4. Ajustar Zoom
        setTimeout(() => {
            if (this.chart) this.chart.timeScale().fitContent();
        }, 50);
    }

    drawTradeLines(entryPrice, exitPrice, direction) {
        if (!this.series) return;
        // Limpiar anteriores
        this.priceLines.forEach((l) => this.series.removePriceLine(l));
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
                title: "ENTRY",
            }),
        );

        this.priceLines.push(
            this.series.createPriceLine({
                price: parseFloat(exitPrice),
                color: exitColor,
                lineWidth: 2,
                lineStyle: 0,
                axisLabelVisible: true,
                title: "EXIT",
            }),
        );
    }

    async loadData(path, entryPrice, exitPrice, direction) {
        if (!this.series) this.init();
        if (!path) return false;

        // Guardamos info del trade para repintar líneas al cambiar TF
        this.tradeInfo = {
            entry: entryPrice,
            exit: exitPrice,
            direction: direction,
        };

        try {
            const res = await fetch(`/storage/${path}?t=${Date.now()}`);
            if (!res.ok) throw new Error("404");

            const data = await res.json();

            // VALIDACIÓN: ¿Es el nuevo formato multi-tf?
            if (data.timeframes) {
                this.fullData = data; // Guardamos TODO el objeto

                // Intentar cargar '5m' por defecto, si no, el primero disponible
                const initialTf = data.timeframes["5m"]
                    ? "5m"
                    : Object.keys(data.timeframes)[0];

                this.renderTimeframe(initialTf);
                return true;
            }
            // RETROCOMPATIBILIDAD: Formato antiguo (solo candles)
            else if (data.candles) {
                this.fullData = {
                    timeframes: { default: data.candles },
                    markers: data.markers,
                };
                this.renderTimeframe("default");
                return true;
            }

            return false;
        } catch (e) {
            console.error("🛑 Error loading data:", e);
            return false;
        }
    }
}

document.addEventListener("alpine:init", () => {
    Alpine.data("dashboard", () => ({
        winRateChart: null,
        heatmapChart: null,
        showLoading: false,
        showModalDetails: false,
        currentView: "list", // 'list' o 'detail'
        isLoading: false,

        init() {
            const self = this;

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

            this.renderHeatmapChart();

            // 2. Escuchar cambios desde Livewire (cuando cambias el select)
            Livewire.on("dashboard-updated", () => {
                // 👇 RECARGAR DATATABLE
                this.showLoading = true;
                this.renderWinRateChart();
                this.renderAvgPnLChart();
                this.renderDailyWinLossChart();
                this.renderEvolutionChart();
                this.renderDailyPnLChart(); // 👈 Inicializa
                this.renderHeatmapChart();
                this.showLoading = false;
            });
        },

        renderHeatmapChart() {
            const seriesData = this.$wire.heatmapData || [];

            const options = {
                series: seriesData,
                chart: {
                    type: "heatmap",
                    height: 350,
                    fontFamily: "Inter, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: false }, // Mejor false para heatmaps grandes
                },
                plotOptions: {
                    heatmap: {
                        shadeIntensity: 0.5,
                        radius: 4,
                        useFillColorAsStroke: false,
                        colorScale: {
                            ranges: [
                                {
                                    from: -1000000000,
                                    to: -0.01,
                                    color: "#F43F5E", // Rojo
                                    name: this.$l("loss"),
                                },
                                {
                                    from: 0,
                                    to: 0,
                                    color: "#F3F4F6", // Gris claro (Sin actividad o Breakeven)
                                    name: this.$l("not_activity"),
                                },
                                {
                                    from: 0.01,
                                    to: 1000000000,
                                    color: "#10B981", // Verde
                                    name: this.$l("profit"),
                                },
                            ],
                        },
                    },
                },
                dataLabels: { enabled: false },
                stroke: { width: 1, colors: ["#fff"] },
                xaxis: {
                    type: "category",
                    tooltip: { enabled: false },
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

            if (this.heatmapChart) {
                this.heatmapChart.updateSeries(seriesData);
            } else {
                const el = this.$refs.heatmapChart;
                if (el) {
                    this.heatmapChart = new ApexCharts(el, options);
                    this.heatmapChart.render();
                }
            }
        },

        renderWinRateChart() {
            const data = this.$wire.winRateChartData;
            const series = data?.series || [0, 0];
            const isEmpty = series[0] === 0 && series[1] === 0;

            const chartSeries = isEmpty ? [1] : series;
            const colors = isEmpty ? ["#F3F4F6"] : ["#10B981", "#F43F5E"];
            console.log();
            let labelDays = this.$l("days");
            const chartLabels = isEmpty
                ? [this.$l("not_operations")]
                : [this.$l("profits"), this.$l("losses")];

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
                            return val + labelDays;
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
                        name: this.$l("pnl_daily"),
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
                            return val.toFixed(0) + " $";
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
                            name: this.$l("pnl_daily"), // Mantiene el nombre correcto
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
                        name: this.$l("avg_win"),
                        data: [avgWin], // Array de 1 elemento
                    },
                    {
                        name: this.$l("avg_loss"),
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
                        return Math.abs(val).toFixed(2) + " $";
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
                    categories: [this.$l("avg")], // Una sola categoría compartida
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
                ? [this.$l("not_data")]
                : [this.$l("winners"), this.$l("lossers")];

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
                        name: this.$l("acumulative_pnl"),
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
                            name: this.$l("acumulative_pnl"), // Evita que salga "series-1"
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

    Alpine.data("chartViewer", (initialTab = "image") => {
        let controller = null;

        return {
            loading: false,
            hasData: false,
            currentTimeframe: "5m", // Variable para controlar el botón activo
            showVolume: false,
            showEma: false,
            // 1. NUEVA VARIABLE
            isFullscreen: false,

            // 1. NUEVA VARIABLE DE ESTADO
            activeTab: initialTab,

            init() {
                this.$nextTick(() => {
                    if (this.$refs.chartContainer) {
                        controller = new TradeChartController(
                            this.$refs.chartContainer,
                        );
                    }
                });

                window.addEventListener("trade-selected", (e) => {
                    this.currentTimeframe = "5m"; // Resetear al cargar nuevo trade

                    // 2. LÓGICA AUTOMÁTICA AL CAMBIAR DE TRADE
                    // Si viene path, forzamos la pestaña chart, si no, image
                    this.activeTab = e.detail.path ? "chart" : "image";
                    this.load(
                        e.detail.path,
                        e.detail.entry,
                        e.detail.exit,
                        e.detail.direction,
                    );
                });

                // ESCUCHADOR: Detectar si el usuario pulsa ESC para salir
                document.addEventListener("fullscreenchange", () => {
                    this.isFullscreen = !!document.fullscreenElement;
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

                // Cuando cargue, asegurarnos de respetar el estado actual del volumen
                controller
                    .loadData(path, entry, exit, direction)
                    .then((success) => {
                        this.hasData = success;
                        this.loading = false;
                        // Aplicar estado del volumen al cargar
                        if (controller) {
                            controller.toggleVolume(this.showVolume);
                            controller.toggleEma(this.showEma); // <--- APLICAR
                        }
                    });
            },

            // --- NUEVA LÓGICA DE PANTALLA COMPLETA ---
            toggleFullscreen() {
                const el = this.$root; // El div principal que tiene x-data

                if (!document.fullscreenElement) {
                    // INTENTAR ENTRAR
                    if (el.requestFullscreen) {
                        el.requestFullscreen();
                    } else if (el.webkitRequestFullscreen) {
                        /* Safari */
                        el.webkitRequestFullscreen();
                    } else if (el.msRequestFullscreen) {
                        /* IE11 */
                        el.msRequestFullscreen();
                    }
                } else {
                    // SALIR
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    }
                }
            },

            toggleEma() {
                this.showEma = !this.showEma;
                if (controller) controller.toggleEma(this.showEma);
            },

            // 2. NUEVA FUNCIÓN TOGGLE
            toggleVol() {
                this.showVolume = !this.showVolume;
                if (controller) {
                    controller.toggleVolume(this.showVolume);
                }
            },
            // FUNCIÓN VINCULADA A LOS BOTONES
            changeTimeframe(tf) {
                if (controller && this.hasData) {
                    controller.renderTimeframe(tf);
                    this.currentTimeframe = tf; // Actualizar estado visual botón
                }
            },
        };
    });
});
