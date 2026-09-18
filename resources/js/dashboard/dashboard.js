import {
    createChart,
    CandlestickSeries,
    createSeriesMarkers,
    HistogramSeries,
    LineSeries,
} from "lightweight-charts";


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

        // Velas del marco visible, marcadores ya pegados a su vela y momento en
        // que el precio tocó cada nivel. Los tres los recalcula renderTimeframe.
        this.candles = null;
        this.adjustedMarkers = [];
        this.levelTimes = { entry: null, exit: null, mae: null, mfe: null };

        // Reproductor barra a barra (Fase 6 - P8).
        this.replay = { active: false, cursor: 0 };

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

    // --- NUEVO METODO PARA CAMBIAR TF ---
    renderTimeframe(tf) {
        if (!this.fullData || !this.series) return;

        const candles = this.fullData.timeframes[tf];

        if (!candles || candles.length === 0) {
            console.warn(`No data for timeframe: ${tf}`);
            return;
        }

        this.candles = candles;
        this.currentTf = tf;

        // Los marcadores del JSON traen timestamps exactos, pero en M15 o H1 esa
        // vela puede no existir: cada marca se pega a la vela mas cercana.
        this.adjustedMarkers = this.snapMarkers(candles);

        // Cuando toco el precio cada nivel, en las velas de ESTE marco. Se calcula
        // aqui porque el reproductor solo puede ensenyar el MAE y el MFE cuando la
        // barra que los toco ya esta pintada: dibujarlos desde el principio seria
        // contar el final de la pelicula en el minuto uno.
        this.levelTimes = this.findLevelTimes(candles);

        if (this.replay.active) {
            // Cambiar de marco a media reproduccion: el cursor se recorta al
            // numero de velas del marco nuevo (en 1m hay muchas mas que en 4h).
            this.replay.cursor = Math.max(
                1,
                Math.min(candles.length, this.replay.cursor),
            );
            this.paint(this.replay.cursor);
            return;
        }

        this.paint(candles.length);

        setTimeout(() => {
            if (this.chart) this.chart.timeScale().fitContent();
        }, 50);
    }

    /** Pega cada marcador del JSON a la vela mas cercana del marco actual. */
    snapMarkers(candles) {
        if (!this.fullData.markers || !Array.isArray(this.fullData.markers)) {
            return [];
        }

        const times = candles.map((c) => c.time);

        return this.fullData.markers
            .map((m) => {
                const closest = times.reduce((prev, curr) =>
                    Math.abs(curr - m.time) < Math.abs(prev - m.time) ? curr : prev,
                );

                return { ...m, time: closest, size: 1 };
            })
            .sort((a, b) => a.time - b.time); // Lightweight charts exige orden
    }

    /**
     * Pinta las primeras `upTo` velas del marco actual.
     *
     * Es el unico sitio que toca las series: la vista completa es este mismo
     * metodo con todas las velas y el reproductor es este mismo metodo con el
     * cursor. Asi no hay dos caminos capaces de pintar cosas distintas.
     */
    paint(upTo) {
        if (!this.candles || !this.series) return;

        const candles = this.candles.slice(0, upTo);
        if (candles.length === 0) return;

        this.series.setData(candles);

        // Volumen con color de vela: verde si cierra arriba, rojo si cierra abajo.
        this.volumeSeries.setData(
            candles.map((c) => ({
                time: c.time,
                value: c.volume || 0,
                color:
                    c.close >= c.open
                        ? "rgba(38, 166, 154, 0.4)"
                        : "rgba(239, 83, 80, 0.4)",
            })),
        );

        this.emaSeries.setData(
            candles
                .filter((c) => c.ema !== null && c.ema !== undefined)
                .map((c) => ({ time: c.time, value: c.ema })),
        );

        const hasta = candles[candles.length - 1].time;

        this.seriesMarkers.setMarkers(
            this.adjustedMarkers.filter((m) => m.time <= hasta),
        );

        this.drawTradeLines(hasta);
    }

    /**
     * Primer instante en que el precio alcanza `nivel` en este marco.
     *
     * `campo` es la mecha que hay que mirar y `haciaAbajo` el sentido de la
     * comparacion. Devuelve null si el nivel no llega a tocarse.
     */
    firstTouch(candles, nivel, campo, haciaAbajo) {
        const precio = parseFloat(nivel);
        if (!precio) return null;

        const vela = candles.find((c) =>
            haciaAbajo ? c[campo] <= precio : c[campo] >= precio,
        );

        return vela ? vela.time : null;
    }

    /** Cuando se toco cada nivel: entrada, salida, MAE y MFE. */
    findLevelTimes(candles) {
        const marcas = this.adjustedMarkers;
        const entrada = marcas.length ? marcas[0].time : candles[0].time;
        const salida =
            marcas.length > 1
                ? marcas[marcas.length - 1].time
                : candles[candles.length - 1].time;

        const info = this.tradeInfo || {};
        const isLong = info.direction === "long";

        // La excursion solo cuenta con la posicion abierta.
        const dentro = candles.filter(
            (c) => c.time >= entrada && c.time <= salida,
        );

        return {
            entry: entrada,
            exit: salida,
            // El MAE va en tu contra: minimo si estas largo, maximo si estas corto.
            mae: this.firstTouch(dentro, info.mae, isLong ? "low" : "high", isLong),
            mfe: this.firstTouch(dentro, info.mfe, isLong ? "high" : "low", !isLong),
        };
    }

    /**
     * Lineas de entrada, salida, MAE y MFE, hasta el instante `hasta`.
     *
     * Un nivel que todavia no se ha alcanzado no se dibuja: en el reproductor eso
     * destriparia el final, y en la vista completa `hasta` es la ultima vela, asi
     * que salen todos igualmente.
     */
    drawTradeLines(hasta) {
        if (!this.series) return;

        this.priceLines.forEach((l) => this.series.removePriceLine(l));
        this.priceLines = [];

        const info = this.tradeInfo;
        if (!info || !info.entry || !info.exit) return;

        const isLong = info.direction === "long";
        const isWin = isLong ? info.exit >= info.entry : info.exit <= info.entry;

        const niveles = [
            {
                precio: info.entry,
                color: "#3B82F6",
                titulo: "ENTRY",
                estilo: 2,
                desde: this.levelTimes.entry,
            },
            {
                precio: info.mfe,
                color: "#10B981",
                titulo: "MFE",
                estilo: 3,
                desde: this.levelTimes.mfe,
            },
            {
                precio: info.mae,
                color: "#F59E0B",
                titulo: "MAE",
                estilo: 3,
                desde: this.levelTimes.mae,
            },
            {
                precio: info.exit,
                color: isWin ? "#10B981" : "#EF4444",
                titulo: "EXIT",
                estilo: 0,
                desde: this.levelTimes.exit,
            },
        ];

        niveles.forEach((n) => {
            const precio = parseFloat(n.precio);
            if (!precio) return;

            // Si el nivel no se toca en este marco, se guarda para el final.
            const desde = n.desde === null ? this.levelTimes.exit : n.desde;
            if (desde !== null && hasta < desde) return;

            this.priceLines.push(
                this.series.createPriceLine({
                    price: precio,
                    color: n.color,
                    lineWidth: 2,
                    lineStyle: n.estilo,
                    axisLabelVisible: true,
                    title: n.titulo,
                }),
            );
        });
    }

    // Reproductor barra a barra (Fase 6 - P8)

    /** Entra en modo reproduccion y devuelve el cursor inicial. */
    replayStart() {
        if (!this.candles) return 0;

        this.replay.active = true;
        // Arranca con algo de contexto: una vela suelta no cuenta ninguna historia.
        this.replay.cursor = Math.min(
            this.candles.length,
            Math.max(2, Math.ceil(this.candles.length * 0.15)),
        );
        this.paint(this.replay.cursor);

        return this.replay.cursor;
    }

    /** Sale del reproductor y vuelve a la vista completa. */
    replayStop() {
        this.replay.active = false;

        if (!this.candles) return;

        this.paint(this.candles.length);
        if (this.chart) this.chart.timeScale().fitContent();
    }

    replaySeek(cursor) {
        if (!this.replay.active || !this.candles) return;

        this.replay.cursor = Math.max(1, Math.min(this.candles.length, cursor));
        this.paint(this.replay.cursor);
    }

    replayStep(delta) {
        this.replaySeek(this.replay.cursor + delta);
    }

    replayAtEnd() {
        return !!this.candles && this.replay.cursor >= this.candles.length;
    }

    replayTotal() {
        return this.candles ? this.candles.length : 0;
    }

    async loadData(path, entryPrice, exitPrice, direction, mae, mfe) {
        if (!this.series) this.init();
        if (!path) return false;

        // Guardamos info del trade para repintar lineas al cambiar TF. El MAE y el
        // MFE vienen del trade (columnas mae_price/mfe_price), no del JSON de
        // velas: el agente los manda calculados y aqui solo hay que situarlos.
        this.tradeInfo = {
            entry: entryPrice,
            exit: exitPrice,
            direction: direction,
            mae: mae,
            mfe: mfe,
        };

        // Un trade nuevo empieza siempre en vista completa.
        this.replay = { active: false, cursor: 0 };

        try {
            const res = await fetch(path);
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
                // Repinta todos los gráficos con los datos nuevos
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
                    this.heatmapChart = window.tjChart(el, options);
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
                    this.winRateChart = window.tjChart(el, options);
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
                    this.dailyPnLBarChart = window.tjChart(el, options);
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
                    this.avgPnLChart = window.tjChart(el, options);
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
                    this.dailyWinLossChart = window.tjChart(el, options);
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
                    this.evolutionChart = window.tjChart(el, options);
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

            // Reproductor barra a barra (Fase 6 - P8). El cursor y el total viven
            // aqui para que la barra de progreso sea reactiva; el pintado lo hace
            // el controlador.
            replay: false,
            playing: false,
            speed: 2,
            cursor: 0,
            total: 0,
            timer: null,
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
                    this.stopReplay();

                    // 2. LÓGICA AUTOMÁTICA AL CAMBIAR DE TRADE
                    // Si viene path, forzamos la pestaña chart, si no, image
                    this.activeTab = e.detail.path ? "chart" : "image";
                    this.load(
                        e.detail.path,
                        e.detail.entry,
                        e.detail.exit,
                        e.detail.direction,
                        e.detail.mae,
                        e.detail.mfe,
                    );
                });

                // ESCUCHADOR: Detectar si el usuario pulsa ESC para salir
                document.addEventListener("fullscreenchange", () => {
                    this.isFullscreen = !!document.fullscreenElement;
                });
            },

            load(path, entry, exit, direction, mae, mfe) {
                // Si no hay controller, reintentamos un poco
                if (!controller) {
                    if (this.$refs.chartContainer) {
                        controller = new TradeChartController(
                            this.$refs.chartContainer,
                        );
                    } else {
                        setTimeout(
                            () =>
                                this.load(path, entry, exit, direction, mae, mfe),
                            200,
                        );
                        return;
                    }
                }

                this.loading = true;
                this.hasData = false;

                // Cuando cargue, asegurarnos de respetar el estado actual del volumen
                controller
                    .loadData(path, entry, exit, direction, mae, mfe)
                    .then((success) => {
                        this.hasData = success;
                        this.loading = false;
                        this.total = controller ? controller.replayTotal() : 0;
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

                    // Cada marco tiene su propio numero de velas: la barra de
                    // progreso se queda mintiendo si no se resincroniza.
                    this.total = controller.replayTotal();
                    this.cursor = controller.replay.cursor;
                }
            },

            // ── Reproductor barra a barra (Fase 6 - P8) ──────────────────────

            toggleReplay() {
                if (!controller || !this.hasData) return;

                if (this.replay) {
                    this.stopReplay();
                    return;
                }

                this.replay = true;
                this.cursor = controller.replayStart();
                this.total = controller.replayTotal();
            },

            stopReplay() {
                this.pauseReplay();

                if (!this.replay) return;

                this.replay = false;
                if (controller) controller.replayStop();
            },

            playPause() {
                if (!controller) return;

                if (this.playing) {
                    this.pauseReplay();
                    return;
                }

                // Volver a darle al play al final rebobina: es lo que espera
                // cualquiera que haya usado un reproductor.
                if (controller.replayAtEnd()) {
                    controller.replaySeek(1);
                    this.cursor = controller.replay.cursor;
                }

                this.playing = true;
                this.timer = setInterval(() => {
                    if (!controller || controller.replayAtEnd()) {
                        this.pauseReplay();
                        return;
                    }

                    controller.replayStep(1);
                    this.cursor = controller.replay.cursor;
                }, 700 / this.speed);
            },

            pauseReplay() {
                if (this.timer) clearInterval(this.timer);
                this.timer = null;
                this.playing = false;
            },

            stepReplay(delta) {
                if (!controller) return;

                this.pauseReplay();
                controller.replayStep(delta);
                this.cursor = controller.replay.cursor;
            },

            seekReplay(valor) {
                if (!controller) return;

                this.pauseReplay();
                controller.replaySeek(parseInt(valor, 10));
                this.cursor = controller.replay.cursor;
            },

            setSpeed(valor) {
                this.speed = valor;

                // Si estaba sonando, se rearma el intervalo con el ritmo nuevo.
                if (this.playing) {
                    this.pauseReplay();
                    this.playPause();
                }
            },
        };
    });
});
