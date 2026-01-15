document.addEventListener("alpine:init", () => {
    Alpine.data("dashboard", () => ({
        winRateChart: null,

        init() {
            // 1. Inicializar gráfico al cargar
            this.renderWinRateChart();
            // ... otros inits ...
            this.renderAvgPnLChart(); // 👈 Inicializar

            // ... otros renders ...
            this.renderDailyWinLossChart(); // 👈 Inicializar

            // 2. Escuchar cambios desde Livewire (cuando cambias el select)
            Livewire.on("dashboard-updated", () => {
                this.renderWinRateChart();
                this.renderAvgPnLChart();
                this.renderDailyWinLossChart();
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
    }));
});
