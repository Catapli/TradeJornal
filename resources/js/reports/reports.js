document.addEventListener("alpine:init", () => {
    // ============================================
    // COMPONENTE PRINCIPAL: REPORTS (ALPINE-FIRST)
    // ============================================
    Alpine.data("reports", () => ({
        activeTab: "mechanical",

        // --- ESTADO DE ESCENARIOS (Alpine es la fuente de verdad) ---
        scenarios: {
            // Mecánicos (volvemos a los antiguos)
            fixed_sl: null,
            fixed_tp: null,

            // Días (0=Dom, 1=Lun, 2=Mar, 3=Mie, 4=Jue, 5=Vie, 6=Sab)
            exclude_days: [],

            // Comportamiento
            remove_worst: false,
            max_daily_trades: null,
            only_longs: false,
            only_shorts: false,

            // Ids de errores cuyas operaciones se quitan de la curva (P2)
            exclude_mistakes: [],
        },

        // --- CONTROL DE CAMBIOS PENDIENTES ---
        hasUnsavedChanges: false, // Badge de "Cambios pendientes"
        isApplying: false, // Loading state del botón
        showAdvancedFilters: false, // Accordion para filtros avanzados

        /**
         * Inicialización del componente
         */
        init() {
            // El toast de 'show-alert' lo pinta el sistema global (core/notify.js).

            // Sincronización inicial
            this.scenarios = {
                fixed_sl: this.$wire.scenarios.fixed_sl,
                fixed_tp: this.$wire.scenarios.fixed_tp,
                exclude_days: this.$wire.scenarios.exclude_days || [],
                remove_worst: this.$wire.scenarios.remove_worst,
                max_daily_trades: this.$wire.scenarios.max_daily_trades,
                only_longs: this.$wire.scenarios.only_longs,
                only_shorts: this.$wire.scenarios.only_shorts,
                exclude_mistakes: this.$wire.scenarios.exclude_mistakes || [],
            };
        },

        /**
         * Marca o desmarca un error del escenario "sin las operaciones con X"
         */
        toggleMistake(mistakeId) {
            const id = Number(mistakeId);
            const index = this.scenarios.exclude_mistakes.indexOf(id);

            if (index > -1) {
                this.scenarios.exclude_mistakes.splice(index, 1);
            } else {
                this.scenarios.exclude_mistakes.push(id);
            }

            this.onScenarioChange();
        },

        isMistakeExcluded(mistakeId) {
            return this.scenarios.exclude_mistakes.includes(Number(mistakeId));
        },

        /**
         * Se ejecuta cuando cambia cualquier escenario
         * Marca como "cambios pendientes" pero NO sincroniza con Livewire
         */
        onScenarioChange() {
            this.hasUnsavedChanges = true;
        },

        /**
         * Toggle día de la semana (checkbox múltiple)
         */
        toggleDay(dayNumber) {
            const index = this.scenarios.exclude_days.indexOf(dayNumber);
            if (index > -1) {
                this.scenarios.exclude_days.splice(index, 1);
            } else {
                this.scenarios.exclude_days.push(dayNumber);
            }
            this.onScenarioChange();
        },

        /**
         * Verifica si un día está excluido
         */
        isDayExcluded(dayNumber) {
            return this.scenarios.exclude_days.includes(dayNumber);
        },

        /**
         * Aplica los cambios: Sincroniza Alpine → Livewire
         * Se ejecuta al hacer click en "Aplicar Simulación"
         */
        async applyScenarios() {
            this.isApplying = true;

            try {
                this.$wire.scenarios = this.scenarios;
                await this.$wire.$refresh();
                this.hasUnsavedChanges = false;
                this.triggerAlert(this.$s("symulation_apply_ok"), "success");
            } catch (error) {
                this.triggerAlert(this.$e("error_apply_simulation"), "error");
            } finally {
                this.isApplying = false;
            }
        },

        /**
         * Resetea TODOS los escenarios a valores por defecto
         * INSTANTÁNEO (solo Alpine, no toca Livewire hasta "Aplicar")
         */
        resetAllScenarios() {
            this.scenarios = {
                fixed_sl: null,
                fixed_tp: null,
                exclude_days: [],
                remove_worst: false,
                max_daily_trades: null,
                only_longs: false,
                only_shorts: false,
                exclude_mistakes: [],
            };
            this.hasUnsavedChanges = true;
        },

        /**
         * Verifica si hay al menos un escenario activo
         */
        hasActiveScenarios() {
            return (
                this.scenarios.only_longs ||
                this.scenarios.only_shorts ||
                this.scenarios.remove_worst ||
                this.scenarios.max_daily_trades ||
                this.scenarios.fixed_sl ||
                this.scenarios.fixed_tp ||
                this.scenarios.exclude_days.length > 0 ||
                this.scenarios.exclude_mistakes.length > 0
            );
        },

        /**
         * Cuenta cuántos escenarios están activos
         */
        countActiveScenarios() {
            let count = 0;
            if (this.scenarios.only_longs) count++;
            if (this.scenarios.only_shorts) count++;
            if (this.scenarios.remove_worst) count++;
            if (this.scenarios.max_daily_trades) count++;
            if (this.scenarios.fixed_sl) count++;
            if (this.scenarios.fixed_tp) count++;
            if (this.scenarios.exclude_days.length > 0) count++;
            if (this.scenarios.exclude_mistakes.length > 0) count++;
            return count;
        },

        /**
         * Muestra una alerta visual
         */
        triggerAlert(message, type = "error") {
            window.tjToast(message, type);
        },
    }));

    // 1. EQUITY CHART
    Alpine.data("equityChart", (initialReal, initialSim) => ({
        chart: null,
        init() {
            const options = {
                series: [
                    {
                        name: this.$l("reality"),
                        data: initialReal,
                    },
                    {
                        name: this.$l("simulation"),
                        data: initialSim,
                    },
                ],
                chart: {
                    type: "area",
                    height: 400,
                    fontFamily: "Inter, sans-serif",
                    toolbar: {
                        show: false,
                    },
                    animations: {
                        enabled: true,
                        easing: "easeinout",
                        speed: 800,
                    },
                },
                colors: ["#9CA3AF", "#6366F1"],
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100],
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    curve: "smooth",
                    width: [2, 3],
                    dashArray: [5, 0],
                },
                xaxis: {
                    type: "datetime",
                    tooltip: {
                        enabled: false,
                    },
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false,
                    },
                    labels: {
                        style: {
                            colors: "#94a3b8",
                            fontSize: "10px",
                        },
                    },
                },
                yaxis: {
                    labels: {
                        formatter: (val) => val.toFixed(0) + " $",
                        style: {
                            colors: "#94a3b8",
                            fontSize: "10px",
                        },
                    },
                },
                grid: {
                    borderColor: "#f3f4f6",
                    strokeDashArray: 4,
                    padding: {
                        left: 10,
                    },
                },
                tooltip: {
                    theme: "light",
                    x: {
                        format: "dd MMM yyyy",
                    },
                    y: {
                        formatter: (val) => val.toFixed(2) + " $",
                    },
                },
                legend: {
                    show: false,
                },
            };
            this.chart = window.tjChart(
                document.querySelector("#equityChart"),
                options,
            );
            this.chart.render();
        },
        updateData(newReal, newSim) {
            if (this.chart)
                this.chart.updateSeries([
                    {
                        name: this.$l("reality"),
                        data: newReal,
                    },
                    {
                        name: this.$l("simulation"),
                        data: newSim,
                    },
                ]);
        },
    }));

    // 2. BAR CHARTS (HORA/SESIÓN)
    Alpine.data("barChart", (data, categoryKey, title) => ({
        chart: null,
        init() {
            if (!data || data.length === 0) return;
            const categories = data.map((item) => item[categoryKey]);
            const seriesData = data.map((item) => item.pnl);
            const chartId =
                categoryKey === "hour" ? "hourlyChart" : "sessionChart";
            const el = document.getElementById(chartId);
            if (!el) return;

            const options = {
                series: [
                    {
                        name: this.$l("pnl"),
                        data: seriesData,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 250,
                    fontFamily: "Inter, sans-serif",
                    toolbar: {
                        show: false,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: "60%",
                        colors: {
                            ranges: [
                                {
                                    from: -1000000,
                                    to: -0.01,
                                    color: "#F43F5E",
                                },
                                {
                                    from: 0,
                                    to: 1000000,
                                    color: "#10B981",
                                },
                            ],
                        },
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                xaxis: {
                    categories: categories,
                    labels: {
                        style: {
                            colors: "#94a3b8",
                            fontSize: "10px",
                        },
                    },
                },
                yaxis: {
                    labels: {
                        formatter: (val) => val.toFixed(0) + " $",
                        style: {
                            colors: "#94a3b8",
                            fontSize: "10px",
                        },
                    },
                },
                grid: {
                    borderColor: "#f3f4f6",
                    strokeDashArray: 4,
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: (val) => val.toFixed(2) + " $",
                    },
                },
            };
            this.chart = window.tjChart(el, options);
            this.chart.render();
        },
    }));

    Alpine.data("efficiencyChart", (payload) => ({
        chart: null,
        init() {
            if (
                !payload ||
                !payload.categories ||
                payload.categories.length === 0
            )
                return;

            const options = {
                series: payload.series,
                chart: {
                    type: "bar",
                    height: 350,
                    fontFamily: "Inter, sans-serif",
                    toolbar: {
                        show: false,
                    },
                    zoom: {
                        enabled: false,
                    },
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: "70%",
                        endingShape: "rounded",
                        dataLabels: {
                            position: "top", // top, center, bottom
                        },
                    },
                },
                dataLabels: {
                    enabled: false, // Desactivado para limpieza, el tooltip hace el trabajo
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ["transparent"],
                },
                xaxis: {
                    categories: payload.categories,
                    labels: {
                        style: {
                            fontSize: "10px",
                            colors: "#64748b",
                        },
                    },
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false,
                    },
                },
                yaxis: {
                    title: {
                        text: this.$l("value_currency"),
                        style: {
                            fontSize: "10px",
                            color: "#94a3b8",
                        },
                    },
                    labels: {
                        formatter: (val) => val.toFixed(0),
                        style: {
                            colors: "#94a3b8",
                        },
                    },
                },
                // Colores Semánticos:
                // 0: MAE (Rojo Rosado)
                // 1: PnL (Azul Oscuro - Realidad)
                // 2: MFE (Verde Esmeralda - Potencial)
                colors: ["#F43F5E", "#1E293B", "#10B981"],
                fill: {
                    opacity: 1,
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            return val + " $";
                        },
                    },
                },
                grid: {
                    borderColor: "#f1f5f9",
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true,
                        },
                    },
                },
                legend: {
                    position: "top",
                    horizontalAlign: "right",
                    fontSize: "12px",
                    fontFamily: "Inter",
                    offsetY: -20,
                    itemMargin: {
                        horizontal: 10,
                        vertical: 0,
                    },
                },
            };

            this.chart = window.tjChart(
                document.querySelector("#efficiencyChart"),
                options,
            );
            this.chart.render();
        },
    }));

    // 4. DISTRIBUTION CHART (HISTOGRAMA)
    Alpine.data("distributionChart", (payload) => ({
        chart: null,
        init() {
            if (!payload || !payload.data || payload.data.length === 0) return;
            const colors = payload.categories.map((cat) =>
                cat.includes("-") ? "#EF4444" : "#10B981",
            );
            const options = {
                series: [
                    {
                        name: this.$l("trades"),
                        data: payload.data,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 250,
                    fontFamily: "Inter, sans-serif",
                    toolbar: {
                        show: false,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 2,
                        columnWidth: "95%",
                        distributed: true,
                        dataLabels: {
                            position: "top",
                        },
                    },
                },
                colors: colors,
                dataLabels: {
                    enabled: true,
                    offsetY: -20,
                    style: {
                        fontSize: "10px",
                        colors: ["#64748b"],
                    },
                },
                xaxis: {
                    categories: payload.categories,
                    labels: {
                        show: false,
                    },
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false,
                    },
                },
                yaxis: {
                    show: false,
                },
                grid: {
                    show: false,
                },
                legend: {
                    show: false,
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: (val) => val + " trades",
                    },
                },
            };
            this.chart = window.tjChart(
                document.querySelector("#distChart"),
                options,
            );
            this.chart.render();
        },
    }));

    Alpine.data("radarChart", (data) => ({
        chart: null,
        init() {
            if (!data) return;

            // Extraer etiquetas y valores del objeto PHP
            const categories = Object.keys(data);
            const values = Object.values(data);

            const options = {
                series: [
                    {
                        name: this.$l("puntuation"),
                        data: values,
                    },
                ],
                chart: {
                    height: 280, // Un poco más alto para que quepan las etiquetas
                    type: "radar",
                    fontFamily: "Inter, sans-serif",
                    toolbar: {
                        show: false,
                    },
                    animations: {
                        enabled: true,
                    },
                },
                colors: ["#8B5CF6"], // Un morado vibrante (Purple-500)
                fill: {
                    opacity: 0.2,
                    colors: ["#8B5CF6"],
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ["#7C3AED"], // Borde más oscuro
                    dashArray: 0,
                },
                markers: {
                    size: 4,
                    colors: ["#fff"],
                    strokeColors: "#7C3AED",
                    strokeWidth: 2,
                    hover: {
                        size: 6,
                    },
                },
                xaxis: {
                    categories: categories,
                    labels: {
                        show: true,
                        style: {
                            colors: [
                                "#64748B",
                                "#64748B",
                                "#64748B",
                                "#64748B",
                                "#64748B",
                            ],
                            fontSize: "11px",
                            fontFamily: "Inter, sans-serif",
                            fontWeight: 600,
                        },
                    },
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
                            strokeColors: "#e2e8f0", // Color de la telaraña
                            connectorColors: "#e2e8f0",
                        },
                    },
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: function (val) {
                            return val + " / 100";
                        },
                    },
                },
            };

            this.chart = window.tjChart(
                document.querySelector("#radarChart"),
                options,
            );
            this.chart.render();
        },
    }));

    Alpine.data("mistakesChart", (data) => ({
        chart: null,
        hasData: false,
        init() {
            if (!data || data.length === 0) {
                this.hasData = false;
                return;
            }
            this.hasData = true;

            const categories = data.map((d) => d.name);
            const counts = data.map((d) => d.count);
            const costs = data.map((d) => d.total_loss);

            // Paleta de seguridad (Vibrante)
            const palette = [
                "#F43F5E",
                "#8B5CF6",
                "#F59E0B",
                "#3B82F6",
                "#10B981",
            ];
            const colors = data.map((d, index) =>
                d.color ? d.color : palette[index % palette.length],
            );

            // ApexCharts llama a `custom` con su propio `this`: la traducción
            // hay que resolverla aquí, mientras seguimos en el componente.
            const timesLabel = this.$l("times");

            const options = {
                series: [
                    {
                        name: this.$l("repetitions"),
                        data: counts,
                    },
                ],
                chart: {
                    type: "bar",
                    height: 300,
                    // Ancho en píxeles, no al 100%: el gráfico crece con el
                    // número de errores en vez de repartir la tarjeta entera
                    // entre las barras que haya.
                    width: Math.min(880, Math.max(240, data.length * 104 + 72)),
                    fontFamily: "Inter, sans-serif",
                    toolbar: {
                        show: false,
                    },
                    animations: {
                        enabled: true,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                        distributed: true,
                        // En píxeles (ApexCharts lo trata como px cuando no
                        // lleva '%'): una barra sola mide igual que ocho.
                        columnWidth: "40px",
                        dataLabels: {
                            position: "top",
                        },
                    },
                },
                colors: colors,
                // En vertical el nombre del error va en el eje X, así que la
                // etiqueta de encima de la barra solo lleva el número.
                dataLabels: {
                    enabled: true,
                    offsetY: -18,
                    style: {
                        colors: ["#94a3b8"], // slate-400: legible en claro y en oscuro
                        fontSize: "11px",
                        fontWeight: 800,
                        fontFamily: "Inter, sans-serif",
                    },
                },
                xaxis: {
                    categories: categories,
                    labels: {
                        show: true,
                        rotate: -35,
                        rotateAlways: data.length > 4,
                        trim: true,
                        hideOverlappingLabels: false,
                        style: {
                            fontSize: "10px",
                            fontWeight: 600,
                        },
                    },
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false,
                    },
                },
                yaxis: {
                    labels: {
                        show: false,
                    },
                },
                grid: {
                    show: false,
                    // Hueco arriba para la etiqueta que va encima de la barra
                    // más alta; si no, se recorta contra el borde del gráfico.
                    padding: {
                        left: 0,
                        right: 0,
                        top: 12,
                        bottom: 0,
                    },
                },
                legend: {
                    show: false,
                },

                tooltip: {
                    theme: "light",
                    // Tooltip FIJO en la esquina superior derecha del gráfico
                    fixed: {
                        enabled: true,
                        position: "topRight",
                        offsetX: 0,
                        offsetY: 30, // Bajado un poco para no tapar el título si el gráfico es corto
                    },
                    custom: function ({
                        series,
                        seriesIndex,
                        dataPointIndex,
                        w,
                    }) {
                        var count =
                            w.globals.series[seriesIndex][dataPointIndex];
                        var cost = costs[dataPointIndex];
                        var color = w.globals.colors[dataPointIndex];
                        var label = w.globals.labels[dataPointIndex];
                        var costClass =
                            cost < 0
                                ? "text-rose-600 dark:text-rose-400"
                                : "text-emerald-600 dark:text-emerald-400";

                        return `
                        <div class="px-3 py-2 text-xs bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-lg" style="border-left: 4px solid ${color}; min-width: 140px;">
                            <div class="font-bold text-gray-800 dark:text-gray-100 mb-1 truncate">${label}</div>
                            <div class="flex justify-between items-center text-gray-500 dark:text-gray-400 gap-3">
                                <span>${count} ${timesLabel}</span>
                                <span class="font-black ${costClass}">${Number(cost).toFixed(0)} $</span>
                            </div>
                        </div>
                    `;
                    },
                },
            };

            // Render con pequeño delay de seguridad
            setTimeout(() => {
                if (document.querySelector("#mistakesChart")) {
                    this.chart = window.tjChart(
                        document.querySelector("#mistakesChart"),
                        options,
                    );
                    this.chart.render();
                }
            }, 50);
        },
    }));
});
