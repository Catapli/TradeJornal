document.addEventListener("alpine:init", () => {
    Alpine.data("backtestingPage", () => ({
        // ── Estado modal estrategia ────────────────────────────
        showModal: false,
        isEditing: false,

        // ── Estado panel trade ─────────────────────────────────
        showTradePanel: false,
        isEditingTrade: false,

        // ── Imagen trade ───────────────────────────────────────
        photoPreview: null,
        existingPhotoUrl: null,
        isDragging: false,

        // ── Estado Alpine-First formulario trade ───────────────
        tradeDirection: "long",
        tradeRating: 3,
        tradeSession: "",
        tradeFollowedRules: true,

        // ── Estado Alpine-First filtros ────────────────────────
        filterOutcome: "",
        filterSession: "",

        // ── Modal detalle trade ────────────────────────────────
        showTradeDetail: false,
        detailTrade: null,

        // ── Modal confirm borrar ───────────────────────────────
        showDeleteConfirm: false,
        tradeToDelete: null,

        // ── Modal confirm archive ───────────────────────────────
        showArchiveConfirm: false,
        strategyToArchive: null,

        // ── Tab activo ─────────────────────────────────────────
        activeTab: "log",

        // ─────────────────────────────────────────────────────
        // FILTROS
        // ─────────────────────────────────────────────────────

        setFilterOutcome(value) {
            this.filterOutcome = value;
            this.$wire.set("filterOutcome", value);
        },

        setFilterSession(value) {
            this.filterSession = value;
            this.$wire.set("filterSession", value);
        },

        // ─────────────────────────────────────────────────────
        // MODAL ESTRATEGIA
        // ─────────────────────────────────────────────────────

        openCreate() {
            this.isEditing = false;
            this.$wire.openCreate(); // dispara 'strategy-ready' → showModal = true
        },

        openEdit(id) {
            this.isEditing = true;
            this.$wire.openEdit(id); // dispara 'strategy-ready' → showModal = true
        },

        closeModal() {
            this.showModal = false;
            setTimeout(() => this.$wire.resetForm(), 200);
        },

        // ─────────────────────────────────────────────────────
        // PANEL TRADE
        // ─────────────────────────────────────────────────────

        openTradePanel(tradeId = null) {
            this.isEditingTrade = !!tradeId;
            this.$wire.openTradePanel(tradeId);
        },

        closeTradePanel() {
            this.showTradePanel = false;
        },

        // ─────────────────────────────────────────────────────
        // SETTERS ALPINE-FIRST
        // ─────────────────────────────────────────────────────

        setDirection(value) {
            this.tradeDirection = value;
            this.$wire.set("direction_t", value);
        },

        setRating(value) {
            this.tradeRating = value;
            this.$wire.set("setup_rating", value);
        },

        setSession(value) {
            const next = this.tradeSession === value ? "" : value;
            this.tradeSession = next;
            this.$wire.set("session", next);
        },

        toggleFollowedRules() {
            this.tradeFollowedRules = !this.tradeFollowedRules;
            this.$wire.set("followed_rules", this.tradeFollowedRules);
        },

        setActiveTab(tab) {
            this.activeTab = tab;
            if (tab === "analytics") {
                this.$wire.loadAnalytics();
            }
        },

        // ─────────────────────────────────────────────────────
        // IMAGEN
        // ─────────────────────────────────────────────────────

        onPhotoSelected(event) {
            const file = event.target?.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        handleDrop(event) {
            const file = event.dataTransfer?.files?.[0];
            if (!file || !file.type.startsWith("image/")) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };
            reader.readAsDataURL(file);

            if (this.$refs.photoInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.photoInput.files = dt.files;
                this.$refs.photoInput.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
            }
        },

        clearPhoto() {
            this.photoPreview = null;
            this.existingPhotoUrl = null;
            this.$wire.set("screenshot", null);
            if (this.$refs.photoInput) this.$refs.photoInput.value = "";
        },

        // ─────────────────────────────────────────────────────
        // MODAL DETALLE TRADE
        // ─────────────────────────────────────────────────────

        openTradeDetail(trade) {
            this.detailTrade = trade;
            this.showTradeDetail = true;
        },

        closeTradeDetail() {
            this.showTradeDetail = false;
            setTimeout(() => {
                this.detailTrade = null;
            }, 200);
        },

        // ─────────────────────────────────────────────────────
        // CONFIRM BORRAR TRADE
        // ─────────────────────────────────────────────────────

        confirmDeleteTrade(tradeId) {
            this.tradeToDelete = tradeId;
            this.showDeleteConfirm = true;
        },

        cancelDelete() {
            this.tradeToDelete = null;
            this.showDeleteConfirm = false;
        },

        async executeDelete() {
            await this.$wire.deleteTrade(this.tradeToDelete);
            this.tradeToDelete = null;
            this.showDeleteConfirm = false;
        },

        // ─────────────────────────────────────────────────────
        // CONFIRM ARCHIVE STRATEGY
        // ─────────────────────────────────────────────────────

        // Métodos
        confirmArchive(id) {
            this.strategyToArchive = id;
            this.showArchiveConfirm = true;
        },

        cancelArchive() {
            this.strategyToArchive = null;
            this.showArchiveConfirm = false;
        },

        async executeArchive() {
            await this.$wire.archive(this.strategyToArchive);
            this.strategyToArchive = null;
            this.showArchiveConfirm = false;
        },

        // ─────────────────────────────────────────────────────
        // CÁLCULOS REACTIVOS
        // ─────────────────────────────────────────────────────

        get computedR() {
            const e = parseFloat(this.$wire.entry_price);
            const x = parseFloat(this.$wire.exit_price);
            const sl = parseFloat(this.$wire.stop_loss);

            if (isNaN(e) || isNaN(x) || isNaN(sl)) return null;

            const risk = Math.abs(e - sl);
            const profit = this.tradeDirection === "long" ? x - e : e - x;

            return risk > 0 ? parseFloat((profit / risk).toFixed(2)) : null;
        },

        get rColor() {
            const r = this.computedR;
            if (r === null) return "text-gray-400";
            if (r > 0) return "text-emerald-600 font-semibold";
            if (r < 0) return "text-red-500 font-semibold";
            return "text-gray-400";
        },

        // ─────────────────────────────────────────────────────
        // INIT
        // ─────────────────────────────────────────────────────

        init() {
            this.$wire.on("strategy-ready", () => {
                this.showModal = true;
            });

            this.$wire.on("strategy-saved", () => {
                this.showModal = false;
                setTimeout(() => {
                    this.$wire.resetForm();
                    this.$wire.dispatch("notify", {
                        type: "success",
                        message: "Estrategia guardada",
                    });
                }, 200);
            });

            this.$wire.on("open-trade-panel", ({ existingScreenshot }) => {
                this.tradeDirection = this.$wire.direction_t || "long";
                this.tradeRating = this.$wire.setup_rating || 3;
                this.tradeSession = this.$wire.session || "";
                this.tradeFollowedRules = this.$wire.followed_rules ?? true;
                this.photoPreview = null;
                this.existingPhotoUrl = existingScreenshot ?? null;
                this.showTradePanel = true;
            });

            this.$wire.on("close-trade-panel", ({ saved }) => {
                this.showTradePanel = false;
                this.tradeDirection = "long";
                this.tradeRating = 3;
                this.tradeSession = "";
                this.tradeFollowedRules = true;
                this.photoPreview = null;
                this.existingPhotoUrl = null;
                if (this.$refs.photoInput) this.$refs.photoInput.value = "";

                // Lanza notify solo si viene de un guardado, no de un cierre manual
                if (saved) {
                    this.$wire.dispatch("notify", {
                        type: "success",
                        message: "Trade guardado",
                    });
                }
            });

            this.$wire.on("strategy-selected", () => {
                this.filterOutcome = "";
                this.filterSession = "";
                this.activeTab = "log";
            });

            window.addEventListener("analytics-ready", (e) => {
                this.$nextTick(() => {
                    window.initBacktestCharts(e.detail.metrics, this.$wire);
                });
            });
        },
    }));
});
