document.addEventListener("alpine:init", () => {
    Alpine.data("logs", () => ({
        // Estado de UI
        showDetailModal: false,
        showResolveModal: false,

        // Log seleccionado para ver/editar
        selectedLog: null,

        init() {
            // Escuchar evento de Livewire para cerrar modal tras guardar
            Livewire.on("close-resolve-modal", () => {
                this.showResolveModal = false;
                this.selectedLog = null;
            });
        },

        // Ver detalles técnicos (Trace, Request, etc)
        viewDetails(log) {
            this.selectedLog = log;
            this.showDetailModal = true;
        },

        // Abrir modal de resolución (solo errores)
        openResolve(log) {
            this.selectedLog = log;
            // Sincronizar ID con Livewire
            this.$wire.set("selectedLogId", log.id);
            this.showResolveModal = true;
        },

        // Helpers visuales
        getStatusColor(type) {
            const colors = {
                error: "bg-red-100 text-red-800",
                warning: "bg-amber-100 text-amber-800",
                success: "bg-emerald-100 text-emerald-800",
                info: "bg-blue-100 text-blue-800",
            };
            return colors[type] || "bg-gray-100 text-gray-800";
        },

        getStatusIcon(type) {
            const icons = {
                error: "fa-bug",
                warning: "fa-triangle-exclamation",
                success: "fa-check-circle",
                info: "fa-info-circle",
            };
            return icons[type] || "fa-circle";
        },
    }));
});
