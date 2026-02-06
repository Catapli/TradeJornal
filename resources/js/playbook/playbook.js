document.addEventListener("alpine:init", () => {
    Alpine.data("playbook", () => ({
        //?====== Variables de Alerta
        typeAlert: "error",
        showAlert: false,
        bodyAlert: "",

        //?====== UI State (Modales, Formularios)
        showModal: false,
        isEditing: false,
        isSaving: false,

        //?====== Formulario
        strategyId: null,
        name: "",
        description: "",
        timeframe: "",
        color: "#4F46E5",
        is_main: false,
        rules: [],
        newRule: "",
        isDragging: false,

        //?====== Imagen (Preview Alpine)
        photoPreview: null,
        existingPhotoUrl: null,

        //?====== Estado Modal Análisis
        showAnalysisModal: false,
        activeTab: "overview", // 'overview', 'temporal', 'heatmap', 'trades'
        analysisStrategy: null, // Objeto estrategia seleccionado

        //?====== Gráficos (instancias para destruir al cerrar)
        charts: {},

        //?====== Operaciones Cargadas
        trades: [],
        isLoadingTrades: false,

        // Alpine State
        confirmModal: {
            show: false,
            title: "",
            text: "",
            action: null, // Nombre del método Livewire a llamar
            params: null, // Parámetros para el método
            type: "indigo", // Color
        },

        init() {
            //?========= Escuchador Alerta
            window.addEventListener("show-alert", (e) => {
                const data = e.detail[0] || e.detail;
                this.showModal = false; // Cerramos modal si está abierto
                this.triggerAlert(data.message, data.type);
            });
        },

        //?========= Mostrar Alerta
        triggerAlert(message, type = "error") {
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            setTimeout(() => (this.showAlert = false), 3000);
        },

        //?========= MODAL: Crear (instantáneo)
        openCreateModal() {
            this.resetForm();
            this.isEditing = false;
            this.showModal = true;
        },

        //?========= MODAL: Editar (instantáneo, sin round-trip)
        openEditModal(strategy) {
            this.isEditing = true;
            this.strategyId = strategy.id;
            this.name = strategy.name || "";
            this.description = strategy.description || "";
            this.timeframe = strategy.timeframe || "";
            this.color = strategy.color || "#4F46E5";
            this.is_main = !!strategy.is_main;
            this.rules = Array.isArray(strategy.rules)
                ? [...strategy.rules]
                : [];
            this.existingPhotoUrl = strategy.image_url || null;
            this.photoPreview = null;
            this.newRule = "";
            this.showModal = true;
        },

        askConfirm(action, params, title, text, type = "indigo") {
            this.confirmModal = {
                show: true,
                action,
                params,
                title,
                text,
                type,
            };
        },

        executeConfirm() {
            if (this.confirmModal.action) {
                // Llamada dinámica a Livewire: $wire.method(param)
                this.$wire[this.confirmModal.action](this.confirmModal.params);
            }
            this.confirmModal.show = false;
        },

        handleDrop(event) {
            this.isDragging = false;

            const files = event.dataTransfer?.files;
            if (!files || files.length === 0) return;

            const file = files[0];

            // Opcional: validar tipo rápido en frontend
            if (!file.type?.startsWith("image/")) {
                this.triggerAlert("Solo se permiten imágenes.", "error");
                return;
            }

            // 1) Preview instantáneo (Alpine)
            const reader = new FileReader();
            reader.onload = (e) => (this.photoPreview = e.target.result);
            reader.readAsDataURL(file);

            // 2) Inyectar el archivo en el input real para que Livewire lo capture
            // (esto es clave para que wire:model.defer="photo" se entere)
            if (this.$refs.photoInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.photoInput.files = dt.files;

                // Dispara change para que Livewire detecte el nuevo file
                this.$refs.photoInput.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
            }
        },

        //?========= MODAL: Cerrar (instantáneo)
        closeModal() {
            this.showModal = false;
            setTimeout(() => this.resetForm(), 150); // Reset después de animación
        },

        //?========= Reset Formulario
        resetForm() {
            this.strategyId = null;
            this.name = "";
            this.description = "";
            this.timeframe = "";
            this.color = "#4F46E5";
            this.is_main = false;
            this.rules = [];
            this.newRule = "";
            this.photoPreview = null;
            this.existingPhotoUrl = null;

            // Reset input file si existe
            if (this.$refs.photoInput) {
                this.$refs.photoInput.value = "";
            }
        },

        //?========= Abrir Análisis
        async openAnalysis(strategy) {
            this.analysisStrategy = strategy;
            this.activeTab = "overview";
            this.showAnalysisModal = true;
            this.trades = [];
            this.isLoadingTrades = true;

            setTimeout(() => this.renderCharts(), 100);

            try {
                const trades = await this.$wire.loadStrategyDetails(
                    strategy.id,
                );
                this.trades = trades;

                // ✅ Si el usuario ya está en la tab heatmap cuando cargan los datos
                if (this.activeTab === "heatmap") {
                    setTimeout(() => this.renderHeatmap(), 50);
                }
            } catch (e) {
                console.error("Error cargando trades", e);
                this.triggerAlert(
                    "Error cargando historial de trades",
                    "error",
                );
            } finally {
                this.isLoadingTrades = false;
            }
        },

        closeAnalysis() {
            this.showAnalysisModal = false;
            this.analysisStrategy = null;
            // Destruir gráficos para ahorrar memoria
            Object.values(this.charts).forEach((c) => c.destroy());
            this.charts = {};
        },

        //?========= Renderizado de Gráficos (ApexCharts)
        renderCharts() {
            if (!this.analysisStrategy) return;

            const data = this.analysisStrategy.chart_data || {
                days: {},
                hours: {},
            };
            const daysData = data.days || {};
            const hoursData = data.hours || {};

            // 1. Gráfico Días (Barras) - YA LO TIENES
            if (this.$refs.daysChart) {
                const days = ["Mon", "Tue", "Wed", "Thu", "Fri"];
                const seriesData = days.map((d) => daysData[d]?.pnl || 0);

                const options = {
                    chart: {
                        type: "bar",
                        height: 300,
                        toolbar: { show: false },
                        fontFamily: "Inter, sans-serif",
                    },
                    series: [{ name: "PnL", data: seriesData }],
                    xaxis: {
                        categories: ["Lun", "Mar", "Mié", "Jue", "Vie"],
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        labels: {
                            formatter: (val) => val.toFixed(0) + "$",
                        },
                    },
                    colors: [
                        ({ value }) => (value >= 0 ? "#10B981" : "#EF4444"),
                    ],
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: "60%",
                            distributed: true, // Cada barra su color según valor
                        },
                    },
                    dataLabels: { enabled: false },
                    grid: { borderColor: "#f3f4f6", strokeDashArray: 4 },
                    legend: { show: false },
                };

                if (this.charts.days) this.charts.days.destroy();
                this.charts.days = new ApexCharts(
                    this.$refs.daysChart,
                    options,
                );
                this.charts.days.render();
            }

            // 2. ✅ NUEVO: Gráfico Horas (Área/Línea)
            if (this.$refs.hoursChart) {
                const hours = Array.from({ length: 24 }, (_, i) =>
                    String(i).padStart(2, "0"),
                );
                const hourlyPnl = hours.map((h) => hoursData[h]?.pnl || 0);
                const hourlyTotal = hours.map((h) => hoursData[h]?.total || 0);

                const optionsHours = {
                    chart: {
                        type: "area",
                        height: 300,
                        toolbar: { show: false },
                        fontFamily: "Inter, sans-serif",
                        zoom: { enabled: false },
                    },
                    series: [
                        { name: "PnL", data: hourlyPnl },
                        { name: "Trades", data: hourlyTotal },
                    ],
                    xaxis: {
                        categories: hours.map((h) => h + ":00"),
                        labels: {
                            rotate: -45,
                            style: { fontSize: "10px" },
                        },
                        tooltip: { enabled: false },
                    },
                    yaxis: [
                        {
                            title: { text: "PnL ($)" },
                            labels: {
                                formatter: (val) => val.toFixed(0) + "$",
                            },
                        },
                        {
                            opposite: true,
                            title: { text: "Num. Trades" },
                            labels: { formatter: (val) => val.toFixed(0) },
                        },
                    ],
                    stroke: { curve: "smooth", width: [3, 2] },
                    fill: {
                        type: "gradient",
                        gradient: {
                            opacityFrom: [0.5, 0.1],
                            opacityTo: [0, 0],
                        },
                    },
                    colors: ["#6366f1", "#f59e0b"],
                    dataLabels: { enabled: false },
                    grid: { borderColor: "#f3f4f6", strokeDashArray: 4 },
                    legend: {
                        position: "top",
                        horizontalAlign: "right",
                    },
                };

                if (this.charts.hours) this.charts.hours.destroy();
                this.charts.hours = new ApexCharts(
                    this.$refs.hoursChart,
                    optionsHours,
                );
                this.charts.hours.render();
            }
        },

        renderHeatmap() {
            if (!this.$refs.heatmapChart || this.trades.length === 0) return;

            // Procesar datos: Crear matriz 5 días x 24 horas
            const days = ["Mon", "Tue", "Wed", "Thu", "Fri"];
            const hours = Array.from({ length: 24 }, (_, i) =>
                String(i).padStart(2, "0"),
            );

            // Inicializar estructura
            let heatmapData = {};
            days.forEach((d) => {
                heatmapData[d] = {};
                hours.forEach((h) => (heatmapData[d][h] = 0)); // Acumulador de PnL
            });

            // Llenar con trades
            this.trades.forEach((t) => {
                if (!t.day_iso || !t.hour) return;
                const dayName = days[t.day_iso - 1]; // 1=Mon -> index 0
                if (dayName) {
                    heatmapData[dayName][t.hour] += t.pnl;
                }
            });

            // Formatear para ApexCharts (invertimos orden: Fri arriba, Mon abajo)
            const series = days.reverse().map((day) => ({
                name: day,
                data: hours.map((h) => ({
                    x: h + ":00",
                    y: parseFloat(heatmapData[day][h].toFixed(2)),
                })),
            }));

            const options = {
                series: series,
                chart: {
                    type: "heatmap",
                    height: 350,
                    fontFamily: "Inter, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: false }, // Mejor rendimiento
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
                                    color: "#F43F5E", // Rojo (Rose-500)
                                    name: "Pérdida",
                                },
                                {
                                    from: 0,
                                    to: 0,
                                    color: "#F3F4F6", // Gris claro (Sin actividad)
                                    name: "Sin actividad",
                                },
                                {
                                    from: 0.01,
                                    to: 1000000000,
                                    color: "#10B981", // Verde (Emerald-500)
                                    name: "Ganancia",
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
                    labels: {
                        rotate: -45,
                        style: { fontSize: "10px" },
                    },
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

            // Destruir anterior si existe
            if (this.charts.heatmap) {
                this.charts.heatmap.destroy();
            }

            // Crear nuevo
            this.charts.heatmap = new ApexCharts(
                this.$refs.heatmapChart,
                options,
            );
            this.charts.heatmap.render();
        },

        //?========= REGLAS: Añadir
        addRule() {
            const trimmed = (this.newRule || "").trim();
            if (!trimmed) return;
            this.rules.push(trimmed);
            this.newRule = "";
        },

        //?========= REGLAS: Eliminar
        removeRule(index) {
            this.rules.splice(index, 1);
        },

        //?========= IMAGEN: Preview (solo UI)
        onPhotoSelected(event) {
            const file = event.target?.files?.[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        //?========= SUBMIT: Guardar (Livewire solo BD)
        async submit() {
            if (this.isSaving) return;

            this.isSaving = true;

            const payload = {
                strategy_id: this.strategyId,
                name: this.name,
                description: this.description,
                timeframe: this.timeframe,
                color: this.color,
                is_main: this.is_main,
                rules: this.rules,
            };

            try {
                // ✅ Si hay archivo, Livewire lo sube automáticamente con wire:model
                // pero para evitar bloqueo, usa wire:model.defer en el blade
                if (this.isEditing) {
                    await this.$wire.updateStrategy(payload);
                } else {
                    await this.$wire.createStrategy(payload);
                }

                this.closeModal();
            } catch (err) {
                console.error("Error guardando playbook:", err);
                this.triggerAlert("Error guardando el playbook.", "error");
            } finally {
                this.isSaving = false;
            }
        },

        //?========= DELETE: Borrar (con confirm)
        async deleteStrategy(id) {
            if (!confirm("¿Seguro que quieres borrar este playbook?")) return;

            try {
                await this.$wire.deleteStrategy(id);
            } catch (err) {
                console.error("Error borrando playbook:", err);
                this.triggerAlert("Error al eliminar el playbook.", "error");
            }
        },
    }));
});
