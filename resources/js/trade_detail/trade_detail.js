document.addEventListener("alpine:init", () => {
    Alpine.data("tradeDetail", () => ({
        // === STATE ===
        open: false,
        isLoading: false,
        init() {
            // 1. Al pedir abrir, mostramos skeleton y pedimos datos
            window.addEventListener("open-trade-detail", (event) => {
                this.open = true;
                this.isLoading = true;
                // Llamamos a Livewire SIN esperar el .then()
                this.$wire.loadTradeData(
                    event.detail.tradeId,
                    event.detail.tradeIds,
                );
            });
        },
        close() {
            this.open = false;
            // Pequeño retardo para resetear el skeleton cuando ya no se ve
            setTimeout(() => {
                this.isLoading = true;
            }, 300);
        },
    }));
});
