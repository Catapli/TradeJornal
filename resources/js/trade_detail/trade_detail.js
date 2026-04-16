document.addEventListener("alpine:init", () => {
    Alpine.data("tradeDetail", () => ({
        open: false,
        isLoading: false,

        init() {
            // Guardar referencia para poder limpiarla
            const handler = (event) => {
                this.open = true;
                this.isLoading = true;
                this.$wire.loadTradeData(
                    event.detail.tradeId,
                    event.detail.tradeIds,
                );
            };

            window.addEventListener("open-trade-detail", handler);

            // ✅ CRÍTICO: limpiar cuando wire:navigate destruye el componente
            // Esto elimina el "Could not find Livewire component in DOM tree"
            this.$cleanup(() => {
                window.removeEventListener("open-trade-detail", handler);
            });
        },

        close() {
            this.open = false;
            setTimeout(() => {
                this.isLoading = true;
            }, 300);
        },
    }));
});
