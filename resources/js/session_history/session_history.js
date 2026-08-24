document.addEventListener("alpine:init", () => {
    Alpine.data("sessionHistory", () => ({
        // === STATE: MODAL ===
        isOpen: false,
        isLoading: false,
        hasError: false,
        detail: null,
        lastSessionId: null,

        // === STATE: FILTROS ===
        filterAccount: null,
        filterMood: null,
        filterStrategy: null, // ← NUEVO
        dateFrom: null,
        dateTo: null,

        // === COMPUTED ===
        get hasActiveFilters() {
            return (
                this.filterAccount ||
                this.filterMood ||
                this.filterStrategy ||
                this.dateFrom ||
                this.dateTo
            );
        },

        // === INIT ===
        init() {
            this.filterAccount = this.$wire.filterAccount ?? null;
            this.filterMood = this.$wire.filterMood ?? null;
            this.filterStrategy = this.$wire.filterStrategy ?? null; // ← NUEVO
            this.dateFrom = this.$wire.dateFrom ?? null;
            this.dateTo = this.$wire.dateTo ?? null;

        },

        // === ALERT (delegada al sistema global core/notify.js) ===
        triggerAlert(message, type = "error") {
            window.tjToast(message, type);
        },

        // === ACTIONS: FILTROS ===
        resetFilters() {
            this.filterAccount = null;
            this.filterMood = null;
            this.filterStrategy = null; // ← NUEVO
            this.dateFrom = null;
            this.dateTo = null;
            this.$wire.resetFilters();
        },

        // === ACTIONS: MODAL ===
        async openSession(id) {
            this.isOpen = true;
            this.isLoading = true;
            this.hasError = false;
            this.detail = null;
            this.lastSessionId = id;

            try {
                const data = await this.$wire.getSessionDetails(id);

                if (data === null) {
                    this.hasError = true;
                    return;
                }

                this.detail = data;
            } catch (error) {
                this.hasError = true;
                this.triggerAlert(this.$e("error_loading_sesion"), "error");
            } finally {
                this.isLoading = false;
            }
        },

        close() {
            this.isOpen = false;
            setTimeout(() => {
                this.detail = null;
                this.hasError = false;
                this.lastSessionId = null;
            }, 500);
        },
    }));
});
