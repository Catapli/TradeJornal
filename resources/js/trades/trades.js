document.addEventListener("alpine:init", () => {
    Alpine.data("trades", () => ({
        // --- ESTADO SINCRONIZADO (Entangled) ---
        // Usamos @entangle en la vista para conectar esto con PHP
        search: "",
        selectedTrades: [],
        showFilters: false, // UI local

        // --- MODALES Y UI LOCAL ---
        showBulkModal: false,

        // --- ALERTAS ---
        showAlert: false,
        bodyAlert: "",
        typeAlert: "success", // success, error

        init() {
            // Escuchar notificaciones desde Livewire (PHP: dispatch('notify', ...))
            Livewire.on("notify", (message) => {
                this.closeBulkModal(); // Por si acaso
                this.triggerAlert(message, "success");
            });

            // Escuchar errores
            Livewire.on("error", (message) => {
                this.triggerAlert(message, "error");
            });
        },

        // --- ACCIONES UI ---
        toggleFilters() {
            this.showFilters = !this.showFilters;
        },

        openBulkModal() {
            this.showBulkModal = true;
        },

        closeBulkModal() {
            this.showBulkModal = false;
        },

        clearSelection() {
            this.selectedTrades = [];
        },

        // --- GESTOR DE ALERTAS ---
        triggerAlert(message, type = "success") {
            this.bodyAlert = message; // Livewire a veces envía array, ajusta si es necesario
            this.typeAlert = type;
            this.showAlert = true;

            // Auto-ocultar a los 4 segundos
            setTimeout(() => {
                this.showAlert = false;
            }, 4000);
        },
    }));
});
