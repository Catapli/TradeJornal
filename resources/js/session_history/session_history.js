document.addEventListener("alpine:init", () => {
    Alpine.data("sessionHistory", () => ({
        // === STATE ===
        isOpen: false,
        isLoading: false,
        detail: null, // Aquí guardaremos el JSON que viene del servidor

        // === ACTIONS ===
        async openSession(id) {
            this.isOpen = true;
            this.isLoading = true;
            this.detail = null; // Limpiar datos anteriores para evitar "falso contenido"

            try {
                // LLAMADA AL SERVIDOR: Pedimos datos JSON puros
                // Alpine llama a la función PHP 'getSessionDetails' directamente
                const data = await this.$wire.getSessionDetails(id);
                this.detail = data;
            } catch (error) {
                console.error("Error cargando sesión:", error);
                // Opcional: Podrías añadir un estado de error visual aquí
            } finally {
                this.isLoading = false;
            }
        },

        close() {
            this.isOpen = false;
            // Pequeño delay para limpiar datos después de la animación de salida (500ms duration)
            setTimeout(() => {
                this.detail = null;
            }, 500);
        },
    }));
});
