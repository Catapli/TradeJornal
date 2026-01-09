document.addEventListener("alpine:init", () => {
    Alpine.data("accounts", () => ({
        showLoading: false,
        showLoadingGrafic: false,
        typeButton: "", // Tipo de Boton para los modals
        alertTitle: "", // Titulo para la alerta
        showAlert: false, // Mostrar alerta
        typeAlert: "error", // Tipo de Alerta
        bodyAlert: "", // Mensaje para la alerta
        height: "auto",
        isInitialized: false,
        registerSelected: false,
        timeframe: "all", // ← NUEVO: Estado timeframe
        grafico: "",
        init() {
            if (this.isInitialized) return; // Coomprobar que esta inicializado
            this.isInitialized = true;
            const self = this;

            this.initChart();

            window.addEventListener("show-alert", (event) => {
                let e = event.detail[0];
                if (e.type == "success") {
                    this.showAlertSucces(e.title, e.message, e.type, e.event);
                } else {
                    this.showAlertFunc(e.title, this.$e(e.message), "error");
                }
            });

            window.addEventListener("timeframe-updated", (event) => {
                this.timeframe = event.detail.timeframe;
                console.log("Timeframe updated to:", this.timeframe);
                this.initChart();
            });

            window.addEventListener("account-updated", (event) => {
                this.timeframe = event.detail.timeframe;
                this.initChart();
            });

            window.addEventListener("show-alert", (event) => {
                let e = event.detail[0];
                if (e.type == "success") {
                    this.showAlertSucces(e.message, e.type);
                } else {
                    this.showAlertFunc(this.$e(e.message), "error");
                }
            });
        },

        // ? Mostrar alerta de éxito
        showAlertSucces(bodyAlert, typeAlert) {
            this.showAlertFunc(this.$s(bodyAlert), typeAlert);
        },

        // ? Mostrar alerta
        showAlertFunc(bodyAlert, typeAlert) {
            this.bodyAlert = bodyAlert;
            this.typeAlert = typeAlert;
            this.showAlert = true;
        },

        setTimeframe(value) {
            this.showLoadingGrafic = true;
            this.$wire.setTimeframe(value);
        },

        initChart() {
            // 1. Usa $refs en lugar de querySelector
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            // 2. Forzamos que se muestre antes de inicializar Chart.js
            // Si el canvas está oculto (display:none), Chart.js no puede calcular el tamaño
            this.showLoadingGrafic = false;

            // 3. Pequeño delay para asegurar que Alpine ha quitado el display:none del x-show
            this.$nextTick(() => {
                const ctx = canvas.getContext("2d");

                const chartData = {
                    labels: this.$wire.balanceChartData?.labels || [],
                    datasets: this.$wire.balanceChartData?.datasets || [],
                };

                if (!chartData.labels.length) return;

                if (window.balanceChart) {
                    window.balanceChart.destroy();
                }

                window.balanceChart = new Chart(ctx, {
                    type: "line",
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: "index",
                        },
                        // ... resto de tus opciones
                    },
                });
            });
        },
    }));
});
