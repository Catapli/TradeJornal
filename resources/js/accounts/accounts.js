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

            console.log(this.$wire.balanceChartData);
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
                this.initChart();
            });

            window.addEventListener("account-updated", (event) => {
                this.timeframe = event.detail.timeframe;
                this.initChart();
            });
        },

        setTimeframe(value) {
            this.showLoadingGrafic = true;
            this.$wire.setTimeframe(value);
        },

        initChart() {
            const canvas = document.querySelector('[x-ref="canvas"]');
            if (!canvas) return;

            const ctx = canvas.getContext("2d");

            // Destroy si existe
            if (window.balanceChart) {
                window.balanceChart.destroy();
            }

            window.balanceChart = new Chart(ctx, {
                type: "line",
                data: this.$wire.balanceChartData, // ← Datos de Livewire
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: "index",
                    },
                    plugins: {
                        legend: {
                            position: "top",
                            labels: { color: "#000000" },
                        },
                    },
                    scales: {
                        y: { beginAtZero: true },
                        x: { ticks: { color: "#000000" } },
                    },
                },
            });
            this.showLoadingGrafic = false;
        },
    }));
});
