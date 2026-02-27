document.addEventListener("alpine:init", () => {
    Alpine.data("playbook", () => ({
        // ── Alerta ────────────────────────────────────────────────────────
        typeAlert: "error",
        showAlert: false,
        bodyAlert: "",

        // ── UI State ──────────────────────────────────────────────────────
        showModal: false,
        isEditing: false,
        isSaving: false,

        // ── ID de la estrategia en edición (solo Alpine lo necesita) ──────
        strategyId: null,

        // ── Reglas (Alpine gestiona el array en tiempo real) ──────────────
        newRule: "",

        // ── Drag & Drop ───────────────────────────────────────────────────
        isDragging: false,

        // ── Imagen Preview (solo UI, no sube nada) ────────────────────────
        photoPreview: null,
        existingPhotoUrl: null,

        // ── Modal Análisis ────────────────────────────────────────────────
        showAnalysisModal: false,
        activeTab: "overview",
        analysisStrategy: null,
        trades: [],
        isLoadingTrades: false,

        // ── Instancias de gráficos (para destruir al cerrar) ──────────────
        charts: {},

        // ── Confirm Modal ─────────────────────────────────────────────────
        confirmModal: {
            show: false,
            title: "",
            text: "",
            action: null,
            params: null,
            type: "indigo",
        },

        // ─────────────────────────────────────────────────────────────────
        // INIT
        // ─────────────────────────────────────────────────────────────────
        init() {
            // Escucha 'show-alert' pero YA NO cierra el modal.
            // El modal solo se cierra con el evento 'strategy-saved' de Livewire.
            window.addEventListener("show-alert", (e) => {
                const data = e.detail[0] ?? e.detail;
                this.triggerAlert(data.message, data.type);
            });
        },

        // ─────────────────────────────────────────────────────────────────
        // ALERTAS
        // ─────────────────────────────────────────────────────────────────
        triggerAlert(message, type = "error") {
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            setTimeout(() => {
                this.showAlert = false;
            }, 3000);
        },

        // ─────────────────────────────────────────────────────────────────
        // MODAL CREAR
        // ─────────────────────────────────────────────────────────────────
        openCreateModal() {
            this.isEditing = false;
            this.strategyId = null;
            this.photoPreview = null;
            this.existingPhotoUrl = null;
            this.newRule = "";
            // Limpia propiedades del formulario en Livewire + resetea errorBag
            this.$wire.resetStrategyForm();
            this.showModal = true;
        },

        // ─────────────────────────────────────────────────────────────────
        // MODAL EDITAR
        // ─────────────────────────────────────────────────────────────────
        async openEditModal(strategy) {
            this.isEditing = true;
            this.strategyId = strategy.id;
            this.photoPreview = null;
            this.existingPhotoUrl = strategy.imageurl ?? null;
            this.newRule = "";
            // Carga los datos del formulario en las propiedades Livewire
            // y resetea el errorBag para no mostrar errores de una edición anterior
            await this.$wire.loadForEdit(strategy.id);
            this.showModal = true;
        },

        // ─────────────────────────────────────────────────────────────────
        // MODAL CERRAR
        // ─────────────────────────────────────────────────────────────────
        closeModal() {
            this.showModal = false;
            setTimeout(() => {
                this.$wire.resetStrategyForm();
                this.photoPreview = null;
                this.existingPhotoUrl = null;
                this.newRule = "";
                if (this.$refs.photoInput) {
                    this.$refs.photoInput.value = "";
                }
            }, 150); // Espera a que termine la animación de cierre
        },

        // ─────────────────────────────────────────────────────────────────
        // SUBMIT — Sin payload. Las propiedades viven en Livewire.
        // ─────────────────────────────────────────────────────────────────
        async submit() {
            if (this.isSaving) return;
            this.isSaving = true;
            try {
                if (this.isEditing) {
                    await this.$wire.updateStrategy(this.strategyId);
                } else {
                    await this.$wire.createStrategy();
                }
                // NO cerramos el modal aquí.
                // Si validate() falló → Livewire lanza ValidationException,
                // los @error() se pintan en el Blade y el modal permanece abierto.
                // Si todo fue bien → Livewire emite 'strategy-saved' y el
                // @strategy-saved.window en el Blade llama a closeModal().
            } catch (err) {
                // Las ValidationException las gestiona Livewire automáticamente.
                // Solo llegamos aquí si hay un error inesperado de red, etc.
                console.error("Error inesperado en submit:", err);
            } finally {
                this.isSaving = false;
            }
        },

        // ─────────────────────────────────────────────────────────────────
        // REGLAS
        // ─────────────────────────────────────────────────────────────────
        addRule() {
            const trimmed = this.newRule.trim();
            if (!trimmed) return;
            // Escribimos directamente en la propiedad Livewire para que
            // la validación de formRules.* funcione correctamente
            this.$wire.formRules = [...(this.$wire.formRules ?? []), trimmed];
            this.newRule = "";
        },

        removeRule(index) {
            const current = [...(this.$wire.formRules ?? [])];
            current.splice(index, 1);
            this.$wire.formRules = current;
        },

        // ─────────────────────────────────────────────────────────────────
        // CONFIRM MODAL
        // ─────────────────────────────────────────────────────────────────
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
                this.$wire[this.confirmModal.action](this.confirmModal.params);
            }
            this.confirmModal.show = false;
        },

        // ─────────────────────────────────────────────────────────────────
        // BORRAR — Dispatch al confirm modal (sin confirm() nativo)
        // ─────────────────────────────────────────────────────────────────
        deleteStrategy(id) {
            window.dispatchEvent(
                new CustomEvent("open-confirm-modal", {
                    detail: {
                        title: labels.t_delete_strategy,
                        text: labels.l_delete_strategy,
                        type: "red",
                        action: "deleteStrategy",
                        params: id,
                    },
                }),
            );
        },

        // ─────────────────────────────────────────────────────────────────
        // DUPLICAR — Dispatch al confirm modal
        // ─────────────────────────────────────────────────────────────────
        duplicateStrategy(id) {
            window.dispatchEvent(
                new CustomEvent("open-confirm-modal", {
                    detail: {
                        title: labels.t_clone_strategy,
                        text: labels.l_clone_strategy,
                        type: "indigo",
                        action: "duplicateStrategy",
                        params: id,
                    },
                }),
            );
        },

        // ─────────────────────────────────────────────────────────────────
        // IMAGEN — Drag & Drop
        // ─────────────────────────────────────────────────────────────────
        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer?.files;
            if (!files || files.length === 0) return;

            const file = files[0];
            if (!file.type?.startsWith("image/")) {
                this.triggerAlert("Solo se permiten imágenes.", "error");
                return;
            }

            // 1. Preview instantáneo (Alpine, sin round-trip)
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };
            reader.readAsDataURL(file);

            // 2. Inyectar en el input para que Livewire detecte el archivo
            if (this.$refs.photoInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.photoInput.files = dt.files;
                this.$refs.photoInput.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
            }
        },

        // ─────────────────────────────────────────────────────────────────
        // IMAGEN — Preview al seleccionar con el input
        // ─────────────────────────────────────────────────────────────────
        onPhotoSelected(event) {
            const file = event.target?.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        // ─────────────────────────────────────────────────────────────────
        // MODAL ANÁLISIS
        // ─────────────────────────────────────────────────────────────────
        async openAnalysis(strategy) {
            this.analysisStrategy = strategy;
            this.activeTab = "overview";
            this.showAnalysisModal = true;
            this.trades = [];
            this.isLoadingTrades = true;

            // Renderizamos los charts de overview después de que el DOM sea visible
            await this.$nextTick();
            this.renderCharts();

            try {
                const trades = await this.$wire.loadStrategyDetails(
                    strategy.id,
                );
                this.trades = trades;

                if (this.activeTab === "heatmap") {
                    await this.$nextTick();
                    this.renderHeatmap();
                }
            } catch (e) {
                console.error("Error cargando trades:", e);
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
            // Destruir instancias de ApexCharts para liberar memoria
            Object.values(this.charts).forEach((c) => c?.destroy());
            this.charts = {};
        },

        // ─────────────────────────────────────────────────────────────────
        // GRÁFICOS — ApexCharts
        // ─────────────────────────────────────────────────────────────────
        renderCharts() {
            if (!this.analysisStrategy) return;

            const data = this.analysisStrategy.chartdata ?? {
                days: {},
                hours: {},
            };
            const daysData = data.days ?? {};
            const hoursData = data.hours ?? {};

            // 1. Gráfico de Días (Barras)
            if (this.$refs.daysChart) {
                const days = ["Mon", "Tue", "Wed", "Thu", "Fri"];
                const seriesData = days.map((d) => daysData[d]?.pnl ?? 0);

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
                    yaxis: { labels: { formatter: (val) => val.toFixed(0) } },
                    colors: [
                        ({ value }) => (value >= 0 ? "#10B981" : "#EF4444"),
                    ],
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: "60%",
                            distributed: true,
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

            // 2. Gráfico de Horas (Área + Línea)
            if (this.$refs.hoursChart) {
                const hours = Array.from({ length: 24 }, (_, i) =>
                    String(i).padStart(2, "0"),
                );
                const hourlyPnl = hours.map((h) => hoursData[h]?.pnl ?? 0);
                const hourlyTotal = hours.map((h) => hoursData[h]?.total ?? 0);

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
                        categories: hours.map((h) => `${h}:00`),
                        labels: { rotate: -45, style: { fontSize: "10px" } },
                    },
                    yaxis: [
                        {
                            title: { text: "PnL" },
                            labels: { formatter: (val) => val.toFixed(0) },
                        },
                        {
                            title: { text: "Num. Trades" },
                            labels: { formatter: (val) => val.toFixed(0) },
                            opposite: true,
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
                    legend: { position: "top", horizontalAlign: "right" },
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

            const days = ["Mon", "Tue", "Wed", "Thu", "Fri"];
            const hours = Array.from({ length: 24 }, (_, i) =>
                String(i).padStart(2, "0"),
            );

            // Inicializar matriz de acumuladores
            const heatmapData = {};
            days.forEach((d) => {
                heatmapData[d] = {};
                hours.forEach((h) => {
                    heatmapData[d][h] = 0;
                });
            });

            // Rellenar con los trades
            this.trades.forEach((t) => {
                if (!t.day_iso || !t.hour) return;
                const dayName = days[t.day_iso - 1]; // 1=Mon → index 0
                if (dayName) heatmapData[dayName][t.hour] += t.pnl;
            });

            // Formatear para ApexCharts (Viernes arriba → invertimos)
            const series = [...days].reverse().map((day) => ({
                name: day,
                data: hours.map((h) => ({
                    x: `${h}:00`,
                    y: parseFloat(heatmapData[day][h].toFixed(2)),
                })),
            }));

            const options = {
                series,
                chart: {
                    type: "heatmap",
                    height: 350,
                    fontFamily: "Inter, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: false },
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
                                    color: "#F43F5E",
                                    name: "Pérdida",
                                },
                                {
                                    from: 0,
                                    to: 0,
                                    color: "#F3F4F6",
                                    name: "Sin actividad",
                                },
                                {
                                    from: 0.01,
                                    to: 1000000000,
                                    color: "#10B981",
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
                    labels: { rotate: -45, style: { fontSize: "10px" } },
                    tooltip: { enabled: false },
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: (val) =>
                            new Intl.NumberFormat("es-ES", {
                                style: "currency",
                                currency: "EUR",
                            }).format(val),
                    },
                },
            };

            if (this.charts.heatmap) this.charts.heatmap.destroy();
            this.charts.heatmap = new ApexCharts(
                this.$refs.heatmapChart,
                options,
            );
            this.charts.heatmap.render();
        },
    })); // fin Alpine.data
}); // fin alpine:init
