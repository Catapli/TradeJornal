document.addEventListener("alpine:init", () => {
    // COMPONENTE 1: Lógica del Dashboard (Gráficos, Alertas, UI General)
    Alpine.data("dashboardLogic", () => ({
        showLoadingGrafic: false,
        timeframe: "all",
        showAlert: false,
        bodyAlert: "",
        typeAlert: "error",
        showModal: false, // Control del modal
        labelTitleModal: "Crear Cuenta",
        typeButton: "", // Tipo de Boton para los modals

        init() {
            // Inicializamos gráfico
            this.initChart();

            // Listeners de eventos globales
            window.addEventListener("timeframe-updated", (e) => {
                this.timeframe = e.detail.timeframe;
                this.initChart();
            });

            window.addEventListener("show-alert", (e) => {
                const data = e.detail[0] || e.detail; // Ajuste por si viene en array o no
                this.triggerAlert(data.message, data.type);
            });
        },

        triggerAlert(message, type = "error") {
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            // Opcional: auto-ocultar a los 3 seg
            setTimeout(() => (this.showAlert = false), 4000);
        },

        setTimeframe(value) {
            this.showLoadingGrafic = true;
            this.$wire.setTimeframe(value);
        },

        showOpenModalCreate() {
            this.showModal = true;
        },

        initChart() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            this.showLoadingGrafic = false;

            // Usamos nextTick para asegurar que el DOM está listo
            this.$nextTick(() => {
                if (window.balanceChart) window.balanceChart.destroy();

                const ctx = canvas.getContext("2d");
                // Accedemos a los datos de Livewire de forma segura
                const labels = this.$wire.balanceChartData?.labels || [];
                const datasets = this.$wire.balanceChartData?.datasets || [];

                if (!labels.length) return;

                window.balanceChart = new Chart(ctx, {
                    type: "line",
                    data: { labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: "index" },
                        // Tus opciones de chart...
                    },
                });
            });
        },
    }));

    // COMPONENTE 2: Lógica del Selector (Formulario Prop Firms)
    Alpine.data("accountSelector", (data) => ({
        allFirms: data || [], // Protección contra undefined

        // Variables locales de Alpine (NO usas $wire.form aquí directamente en el modelo)
        selectedFirmId: "",
        selectedProgramId: "",
        selectedSize: "",
        selectedLevelId: "",

        get programs() {
            if (!this.selectedFirmId) return [];
            const firm = this.allFirms.find((f) => f.id == this.selectedFirmId);
            return firm ? firm.programs : [];
        },

        get sizes() {
            if (!this.selectedProgramId) return [];
            const program = this.programs.find(
                (p) => p.id == this.selectedProgramId,
            );
            if (!program || !program.levels) return [];
            const sizes = program.levels.map((l) => parseFloat(l.size));
            return [...new Set(sizes)].sort((a, b) => a - b);
        },

        get currencies() {
            if (!this.selectedSize || !this.selectedProgramId) return [];
            const program = this.programs.find(
                (p) => p.id == this.selectedProgramId,
            );
            return program.levels.filter(
                (l) => parseFloat(l.size) == this.selectedSize,
            );
        },

        init() {
            // Watchers para limpiar cascada y sincronizar con Livewire
            this.$watch("selectedFirmId", () => {
                this.selectedProgramId = "";
                this.selectedSize = "";
                this.selectedLevelId = "";
                this.syncToLivewire();
            });
            this.$watch("selectedProgramId", () => {
                this.selectedSize = "";
                this.selectedLevelId = "";
                this.syncToLivewire();
            });
            this.$watch("selectedSize", () => {
                this.selectedLevelId = "";
                this.syncToLivewire();
            });
            this.$watch("selectedLevelId", () => {
                this.syncToLivewire();
            });
        },

        syncToLivewire() {
            // Inyectamos los datos en Livewire silenciosamente
            this.$wire.form.selectedPropFirmID = this.selectedFirmId;
            this.$wire.form.selectedProgramID = this.selectedProgramId;
            this.$wire.form.size = this.selectedSize;
            this.$wire.form.programLevelID = this.selectedLevelId;
        },
    }));
});
