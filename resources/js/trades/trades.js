document.addEventListener("alpine:init", () => {
    Alpine.data("trades", () => ({
        // --- ESTADO UI ---
        search: "",
        showFilters: false,
        showBulkModal: false,
        showFormModal: false,
        showDeleteModal: false,
        showBulkDeleteModal: false,
        typeButton: "",

        selectedTrades: Alpine.$persist([])
            .as("trades_selection")
            .using(sessionStorage),

        // --- ALERTAS ---
        showAlert: false,
        bodyAlert: "",
        typeAlert: "success",
        tradeIdDelete: null,

        init() {
            Livewire.on("notify", (message) =>
                this.triggerAlert(message, "success"),
            );
            Livewire.on("error", (message) => {
                this.showFormModal = false;
                this.triggerAlert(message, "error");
            }); // <--- Listener de Errores

            Livewire.on("open-form-modal", () => {
                this.showFormModal = true;
            });
            Livewire.on("close-form-modal", () => {
                this.showFormModal = false;
            });
            Livewire.on("close-bulk-modal", () => {
                this.showBulkModal = false;
            });
        },

        get selectedCount() {
            return this.$wire.selectedTrades.length;
        },

        toggleFilters() {
            this.showFilters = !this.showFilters;
        },

        openBulkModal() {
            this.showBulkModal = true;
        },

        closeBulkModal() {
            this.showBulkModal = false;
        },

        // --- OPTIMIZACIÓN UI: Reset Client-Side ---
        openFormCreate() {
            this.resetFormClientSide();
            this.showFormModal = true;
            this.$wire.create();
        },

        resetFormClientSide() {
            this.$wire.isEditMode = false;
            this.$wire.editingTradeId = null;

            const now = new Date();
            const localIso = new Date(
                now.getTime() - now.getTimezoneOffset() * 60000,
            )
                .toISOString()
                .slice(0, 16);

            this.$wire.form.account_id = "";
            this.$wire.form.trade_asset_id = "";
            this.$wire.form.strategy_id = "";
            this.$wire.form.ticket = "";
            this.$wire.form.direction = "long";
            this.$wire.form.entry_price = "";
            this.$wire.form.exit_price = "";
            this.$wire.form.size = "";
            this.$wire.form.pnl = "";
            this.$wire.form.entry_time = localIso;
            this.$wire.form.exit_time = "";
            this.$wire.form.notes = "";
            this.$wire.form.mae_price = "";
            this.$wire.form.mfe_price = "";
        },

        closeFormModal() {
            this.showFormModal = false;
        },

        showModalDelete(tradeId) {
            this.tradeIdDelete = tradeId;
            this.showDeleteModal = true;
        },

        showModalBulkDelete() {
            this.showBulkDeleteModal = true;
        },

        executeBulkDelete() {
            if (this.$wire.selectedTrades) {
                this.$wire.executeBulkDelete();
                this.showBulkDeleteModal = false;
            }
        },

        executeDelete() {
            if (this.tradeIdDelete) {
                this.$wire.delete(this.tradeIdDelete);
                this.showDeleteModal = false;
                this.tradeIdDelete = null;
            }
        },

        triggerAlert(message, type = "success") {
            if (Array.isArray(message)) message = message[0];
            this.bodyAlert = message;
            this.typeAlert = type;
            this.showAlert = true;
            setTimeout(() => {
                this.showAlert = false;
            }, 4000);
        },
    }));
});
